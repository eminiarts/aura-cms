<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use ReflectionProperty;
use Throwable;
use WeakMap;

/**
 * Meters statements executed through Laravel-managed database connections.
 *
 * Global-search extension points prohibit native PDO and independently created
 * connections because PHP cannot portably intercept those statements. The
 * fresh-process deadline remains the containment boundary for violating code.
 */
final class GlobalSearchQueryGuard
{
    /** @var WeakMap<Connection, true> */
    private WeakMap $guardedConnections;

    private ?GlobalSearchGuardedEventDispatcher $guardedEventDispatcher = null;

    private int $queryCount = 0;

    private bool $restoringEventDispatcher = false;

    public function __construct(private readonly int $maximumQueries)
    {
        $this->guardedConnections = new WeakMap;
    }

    public function guard(Connection $connection): void
    {
        if (isset($this->guardedConnections[$connection])) {
            return;
        }

        $this->guardedConnections[$connection] = true;
        $connection->beforeExecuting(function (): void {
            if ($this->queryCount >= $this->maximumQueries) {
                throw new GlobalSearchExecutionFailed('The global search query budget was exhausted.');
            }

            $this->queryCount++;
        });
    }

    public function install(): void
    {
        $dispatcher = app('events');

        if (! $dispatcher instanceof Dispatcher) {
            throw new GlobalSearchExecutionFailed('Laravel has no supported global search event dispatcher.');
        }

        $guardedDispatcher = new GlobalSearchGuardedEventDispatcher($dispatcher, $this);
        $this->guardedEventDispatcher = $guardedDispatcher;
        app()->instance('events', $guardedDispatcher);
        Event::swap($guardedDispatcher);
        $this->prependEventDispatcherRebindingGuard(app());

        /** @var DatabaseManager $database */
        $database = app('db');
        $guardedDatabase = new GlobalSearchGuardedDatabaseManager($database, $this);

        app()->instance('db', $guardedDatabase);
        DB::swap($guardedDatabase);
        Model::setConnectionResolver($guardedDatabase);
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }

    private function guardEventDispatcherRebinding(Container $application, mixed $replacement): void
    {
        if ($this->restoringEventDispatcher) {
            return;
        }

        if ($replacement === $this->guardedEventDispatcher) {
            Event::swap($replacement);

            return;
        }

        $guardedDispatcher = $replacement instanceof Dispatcher
            ? new GlobalSearchGuardedEventDispatcher($replacement, $this)
            : $this->guardedEventDispatcher;

        if (! $guardedDispatcher instanceof GlobalSearchGuardedEventDispatcher) {
            throw new GlobalSearchExecutionFailed('Laravel has no supported global search event dispatcher.');
        }

        $this->guardedEventDispatcher = $guardedDispatcher;
        $this->restoringEventDispatcher = true;

        try {
            $application->instance('events', $guardedDispatcher);
            Event::swap($guardedDispatcher);
        } finally {
            $this->restoringEventDispatcher = false;
        }

        throw new GlobalSearchExecutionFailed('The global search event dispatcher was replaced.');
    }

    private function prependEventDispatcherRebindingGuard(Container $application): void
    {
        $callback = function (Container $application, mixed $replacement): void {
            $this->guardEventDispatcherRebinding($application, $replacement);
        };

        try {
            $property = new ReflectionProperty(Container::class, 'reboundCallbacks');
            $callbacks = $property->getValue($application);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }

        if (! is_array($callbacks)) {
            throw new GlobalSearchExecutionFailed('The global search event dispatcher could not be secured.');
        }

        $abstract = $application->getAlias('events');
        $existingCallbacks = $callbacks[$abstract] ?? [];

        if (! is_array($existingCallbacks)) {
            throw new GlobalSearchExecutionFailed('The global search event dispatcher could not be secured.');
        }

        $callbacks[$abstract] = [$callback, ...$existingCallbacks];

        try {
            $property->setValue($application, $callbacks);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }
    }
}
