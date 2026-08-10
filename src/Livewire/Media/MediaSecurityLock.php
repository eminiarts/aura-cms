<?php

namespace Aura\Base\Livewire\Media;

use Closure;
use Illuminate\Contracts\Cache\Lock;
use InvalidArgumentException;

final readonly class MediaSecurityLock implements Lock
{
    /** @param Closure(): void $assertSafe */
    public function __construct(
        private Lock $lock,
        private Closure $assertSafe,
    ) {}

    public function block($seconds, $callback = null): mixed
    {
        ($this->assertSafe)();

        if ($callback !== null) {
            throw new InvalidArgumentException(
                'Aura media security locks do not accept callbacks because every lock operation must revalidate the cache boundary.',
            );
        }

        return $this->lock->block($seconds, $callback);
    }

    public function forceRelease(): void
    {
        ($this->assertSafe)();
        $this->lock->forceRelease();
    }

    public function get($callback = null): mixed
    {
        ($this->assertSafe)();

        if ($callback !== null) {
            throw new InvalidArgumentException(
                'Aura media security locks do not accept callbacks because every lock operation must revalidate the cache boundary.',
            );
        }

        return $this->lock->get($callback);
    }

    public function owner(): string
    {
        return $this->lock->owner();
    }

    public function release(): bool
    {
        ($this->assertSafe)();

        return $this->lock->release();
    }
}
