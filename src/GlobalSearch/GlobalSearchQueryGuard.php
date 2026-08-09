<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Events\Dispatcher;
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

    private int $queryCount = 0;

    public function __construct(private readonly int $maximumQueries)
    {
        $this->guardedConnections = new WeakMap;
    }

    public function install(): void
    {
        /** @var DatabaseManager $database */
        $database = app('db');
        /** @var Dispatcher $events */
        $events = app('events');

        foreach ($database->getConnections() as $connection) {
            $this->guard($connection);
        }

        $events->listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            $this->guard($event->connection);
        });
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }

    private function guard(Connection $connection): void
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
}
