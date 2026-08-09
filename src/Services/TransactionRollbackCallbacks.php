<?php

namespace Aura\Base\Services;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use RuntimeException;
use WeakMap;

final class TransactionRollbackCallbacks
{
    /**
     * @var WeakMap<Connection, list<array{callback: Closure, level: int}>>
     */
    private WeakMap $callbacks;

    /**
     * @var WeakMap<Dispatcher, true>
     */
    private WeakMap $dispatchers;

    public function __construct()
    {
        $this->callbacks = new WeakMap;
        $this->dispatchers = new WeakMap;
    }

    public function add(Connection $connection, Closure $callback): void
    {
        $dispatcher = $connection->getEventDispatcher();

        if (! $dispatcher instanceof Dispatcher) {
            throw new RuntimeException('Unable to bind a rollback callback to the active database transaction.');
        }

        $this->listen($dispatcher);

        $callbacks = $this->callbacks[$connection] ?? [];
        $callbacks[] = [
            'callback' => $callback,
            'level' => $connection->transactionLevel(),
        ];
        $this->callbacks[$connection] = $callbacks;
    }

    private function committed(Connection $connection): void
    {
        if (! isset($this->callbacks[$connection])) {
            return;
        }

        $level = $connection->transactionLevel();

        if ($level === 0) {
            unset($this->callbacks[$connection]);

            return;
        }

        $this->callbacks[$connection] = array_map(
            static fn (array $entry): array => [
                'callback' => $entry['callback'],
                'level' => min($entry['level'], $level),
            ],
            $this->callbacks[$connection],
        );
    }

    private function listen(Dispatcher $dispatcher): void
    {
        if (isset($this->dispatchers[$dispatcher])) {
            return;
        }

        $dispatcher->listen(
            TransactionCommitted::class,
            function (TransactionCommitted $event): void {
                $this->committed($event->connection);
            },
        );
        $dispatcher->listen(
            TransactionRolledBack::class,
            function (TransactionRolledBack $event): void {
                $this->rolledBack($event->connection);
            },
        );

        $this->dispatchers[$dispatcher] = true;
    }

    private function rolledBack(Connection $connection): void
    {
        if (! isset($this->callbacks[$connection])) {
            return;
        }

        $level = $connection->transactionLevel();
        $pending = [];
        $rolledBack = [];

        foreach ($this->callbacks[$connection] as $entry) {
            if ($entry['level'] > $level) {
                $rolledBack[] = $entry['callback'];
            } else {
                $pending[] = $entry;
            }
        }

        if ($pending === []) {
            unset($this->callbacks[$connection]);
        } else {
            $this->callbacks[$connection] = $pending;
        }

        foreach ($rolledBack as $callback) {
            $callback();
        }
    }
}
