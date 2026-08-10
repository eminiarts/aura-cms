<?php

namespace Aura\Base\GlobalSearch;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\ConnectionEstablished;

/** @mixin \Illuminate\Events\Dispatcher */
final class GlobalSearchGuardedEventDispatcher implements Dispatcher
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly GlobalSearchQueryGuard $queryGuard,
    ) {}

    /** @param  array<int, mixed>  $parameters */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->dispatcher->{$method}(...$parameters);
    }

    public function dispatch($event, $payload = [], $halt = false): mixed
    {
        $this->guardConnectionEstablished($event);

        return $this->dispatcher->dispatch($event, $payload, $halt);
    }

    public function flush($event): void
    {
        $this->dispatcher->flush($event);
    }

    public function forget($event): void
    {
        $this->dispatcher->forget($event);
    }

    public function forgetPushed(): void
    {
        $this->dispatcher->forgetPushed();
    }

    public function hasListeners($eventName): bool
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    public function listen($events, $listener = null): void
    {
        $this->dispatcher->listen($events, $listener);
    }

    public function push($event, $payload = []): void
    {
        $this->dispatcher->push($event, $payload);
    }

    public function subscribe($subscriber): void
    {
        $this->dispatcher->subscribe($subscriber);
    }

    public function until($event, $payload = []): mixed
    {
        $this->guardConnectionEstablished($event);

        return $this->dispatcher->until($event, $payload);
    }

    private function guardConnectionEstablished(mixed $event): void
    {
        if ($event instanceof ConnectionEstablished) {
            $this->queryGuard->guard($event->connection);
        }
    }
}
