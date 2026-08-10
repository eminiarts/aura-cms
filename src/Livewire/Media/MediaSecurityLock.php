<?php

namespace Aura\Base\Livewire\Media;

use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Sleep;
use InvalidArgumentException;

final readonly class MediaSecurityLock implements Lock
{
    /** @param Closure(Closure(): mixed): mixed $executeSafely */
    public function __construct(
        private Lock $lock,
        private Closure $executeSafely,
    ) {}

    public function block($seconds, $callback = null): mixed
    {
        if ($callback !== null) {
            throw new InvalidArgumentException(
                'Aura media security locks do not accept callbacks because every lock operation must revalidate the cache boundary.',
            );
        }

        $startedAt = hrtime(true);
        $timeoutNanoseconds = max(0, (int) $seconds) * 1_000_000_000;

        while (($this->executeSafely)(fn (): mixed => $this->lock->get()) !== true) {
            if (hrtime(true) - $startedAt >= $timeoutNanoseconds) {
                throw new LockTimeoutException;
            }

            Sleep::usleep(250_000);
        }

        return true;
    }

    public function forceRelease(): void
    {
        ($this->executeSafely)(function (): void {
            $this->lock->forceRelease();
        });
    }

    public function get($callback = null): mixed
    {
        if ($callback !== null) {
            throw new InvalidArgumentException(
                'Aura media security locks do not accept callbacks because every lock operation must revalidate the cache boundary.',
            );
        }

        return ($this->executeSafely)(
            fn (): mixed => $this->lock->get($callback),
        );
    }

    public function owner(): string
    {
        return $this->lock->owner();
    }

    public function release(): bool
    {
        return ($this->executeSafely)(
            fn (): bool => $this->lock->release(),
        );
    }
}
