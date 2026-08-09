<?php

namespace Aura\Base\Services;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final class VersionedCache
{
    private const GENERATION_PREFIX = 'aura.cache.generation.';

    private const UNCACHED_GENERATION = 'uncached';

    private const VALUE_PREFIX = 'aura.cache.value.';

    /**
     * Move future reads to a fresh key without relying on a non-atomic
     * forget-then-put sequence.
     */
    public static function bump(string $namespace): void
    {
        Cache::forever(self::generationKey($namespace), self::token());
    }

    public static function isSafe(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (! is_int($key) && ! is_string($key)) {
                return false;
            }

            if (! self::isSafe($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve a cache value against a persistent namespace generation.
     *
     * If a writer bumps the generation while the value is being read or
     * resolved, the read is retried under the new generation. Values containing
     * objects, closures, or resources are returned without being cached.
     */
    public static function remember(
        string $namespace,
        string $variant,
        Closure|DateInterval|DateTimeInterface|int|null $ttl,
        Closure $resolver,
    ): mixed {
        while (true) {
            $generation = self::generation($namespace);
            $key = self::valueKey($namespace, $generation, $variant);
            $value = Cache::get($key);

            if (! is_array($value) || ! self::isSafe($value)) {
                if ($value !== null) {
                    Cache::forget($key);
                }

                $value = $resolver();

                if (is_array($value) && self::isSafe($value)) {
                    Cache::put($key, $value, $ttl);
                }
            }

            if (hash_equals($generation, self::generation($namespace))) {
                return $value;
            }
        }
    }

    private static function generation(string $namespace): string
    {
        $key = self::generationKey($namespace);
        $generation = Cache::get($key);

        if (is_string($generation) && $generation !== '') {
            return $generation;
        }

        $candidate = self::token();
        Cache::add($key, $candidate, now()->addYears(10));
        $generation = Cache::get($key);

        if (is_string($generation) && $generation !== '') {
            return $generation;
        }

        Cache::forever($key, $candidate);
        $generation = Cache::get($key);

        return is_string($generation) && $generation !== ''
            ? $generation
            : self::UNCACHED_GENERATION;
    }

    private static function generationKey(string $namespace): string
    {
        return self::GENERATION_PREFIX.hash('sha256', $namespace);
    }

    private static function token(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function valueKey(string $namespace, string $generation, string $variant): string
    {
        return self::VALUE_PREFIX.hash('sha256', $namespace."\0".$generation."\0".$variant);
    }
}
