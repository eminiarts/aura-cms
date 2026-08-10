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

    private ?string $guardedEventDispatcherBinding = null;

    private int $queryCount = 0;

    private bool $rejectingPendingEventDispatcherReplacement = false;

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
        $this->guardedEventDispatcherBinding = self::class.'.events.'.spl_object_id($this);
        $this->restoreEventDispatcherBinding(app());
        $this->prependEventDispatcherRebindingGuard(app());
        $this->prependEventDispatcherResolvingGuard(app());

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

    private function eventDispatcherBindingIsGuarded(Container $application): bool
    {
        if (! is_string($this->guardedEventDispatcherBinding)
            || ! $this->guardedEventDispatcher instanceof GlobalSearchGuardedEventDispatcher) {
            return false;
        }

        try {
            $eventDispatcherAlias = $application->getAlias('events');
        } catch (Throwable) {
            return false;
        }

        try {
            $property = new ReflectionProperty(Container::class, 'instances');
            $instances = $property->getValue($application);

            return $eventDispatcherAlias === $this->guardedEventDispatcherBinding
                && is_array($instances)
                && ($instances[$this->guardedEventDispatcherBinding] ?? null) === $this->guardedEventDispatcher;
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }
    }

    private function guardEventDispatcherRebinding(Container $application, mixed $replacement): void
    {
        if ($this->restoringEventDispatcher) {
            return;
        }

        if ($replacement === $this->guardedEventDispatcher) {
            $rejectReplacement = $this->rejectingPendingEventDispatcherReplacement;
            $this->rejectingPendingEventDispatcherReplacement = false;
            $this->restoreEventDispatcherBinding($application);

            if ($rejectReplacement) {
                throw new GlobalSearchExecutionFailed('The global search event dispatcher was replaced.');
            }

            return;
        }

        $guardedDispatcher = $replacement instanceof Dispatcher
            ? new GlobalSearchGuardedEventDispatcher($replacement, $this)
            : $this->guardedEventDispatcher;

        if (! $guardedDispatcher instanceof GlobalSearchGuardedEventDispatcher) {
            throw new GlobalSearchExecutionFailed('Laravel has no supported global search event dispatcher.');
        }

        $this->guardedEventDispatcher = $guardedDispatcher;
        $this->restoreEventDispatcherBinding($application);

        throw new GlobalSearchExecutionFailed('The global search event dispatcher was replaced.');
    }

    private function guardEventDispatcherResolution(string $abstract, Container $application): void
    {
        if ($this->restoringEventDispatcher
            || $this->eventDispatcherBindingIsGuarded($application)
            || $this->securePendingEventDispatcherReplacement($abstract, $application)) {
            return;
        }

        $this->restoreEventDispatcherBinding($application);

        throw new GlobalSearchExecutionFailed('The global search event dispatcher binding was removed.');
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

        $abstracts = array_filter([
            'events',
            $this->guardedEventDispatcherBinding,
        ], is_string(...));

        foreach ($abstracts as $abstract) {
            $existingCallbacks = $callbacks[$abstract] ?? [];

            if (! is_array($existingCallbacks)) {
                throw new GlobalSearchExecutionFailed('The global search event dispatcher could not be secured.');
            }

            $callbacks[$abstract] = [$callback, ...$existingCallbacks];
        }

        try {
            $property->setValue($application, $callbacks);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }
    }

    private function prependEventDispatcherResolvingGuard(Container $application): void
    {
        $callback = function (string $abstract, array $parameters, Container $application): void {
            $this->guardEventDispatcherResolution($abstract, $application);
        };

        try {
            $property = new ReflectionProperty(Container::class, 'globalBeforeResolvingCallbacks');
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

        try {
            $property->setValue($application, [$callback, ...$callbacks]);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }
    }

    private function restoreEventDispatcherBinding(Container $application): void
    {
        if (! is_string($this->guardedEventDispatcherBinding)
            || ! $this->guardedEventDispatcher instanceof GlobalSearchGuardedEventDispatcher) {
            throw new GlobalSearchExecutionFailed('Laravel has no supported global search event dispatcher.');
        }

        $this->restoringEventDispatcher = true;

        try {
            $application->instance('events', $this->guardedEventDispatcher);
            $application->instance($this->guardedEventDispatcherBinding, $this->guardedEventDispatcher);
            $application->forgetInstance('events');
            $application->alias($this->guardedEventDispatcherBinding, 'events');
            Event::clearResolvedInstance('events');
        } finally {
            $this->restoringEventDispatcher = false;
        }
    }

    private function securePendingEventDispatcherReplacement(string $abstract, Container $application): bool
    {
        if ($abstract !== 'events'
            || ! is_string($this->guardedEventDispatcherBinding)
            || ! $this->guardedEventDispatcher instanceof GlobalSearchGuardedEventDispatcher) {
            return false;
        }

        try {
            $property = new ReflectionProperty(Container::class, 'instances');
            $instances = $property->getValue($application);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }

        $replacement = is_array($instances) ? ($instances['events'] ?? null) : null;

        if (! is_array($instances)
            || ($instances[$this->guardedEventDispatcherBinding] ?? null) !== $this->guardedEventDispatcher
            || ! $replacement instanceof Dispatcher) {
            return false;
        }

        $guardedDispatcher = $replacement === $this->guardedEventDispatcher
            ? $this->guardedEventDispatcher
            : new GlobalSearchGuardedEventDispatcher($replacement, $this);
        $this->guardedEventDispatcher = $guardedDispatcher;
        $this->rejectingPendingEventDispatcherReplacement = true;
        $instances['events'] = $guardedDispatcher;

        try {
            $property->setValue($application, $instances);
            Event::clearResolvedInstance('events');
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search event dispatcher could not be secured.',
                previous: $exception,
            );
        }

        return true;
    }
}
