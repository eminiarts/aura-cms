<?php

namespace Aura\Base\Services;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

final class VersionedCache
{
    private const GENERATION_PREFIX = 'aura.cache.generation.';

    private const MAX_RESOLUTION_ATTEMPTS = 3;

    private const VALUE_PREFIX = 'aura.cache.value.';

    /**
     * Register process-local cleanup against the transaction record owned by
     * the supplied connection. Laravel's manager-level callback registration
     * is global across connections, so it cannot safely select this record.
     */
    public static function afterRollback(Connection $connection, Closure $callback): void
    {
        if ($connection->transactionLevel() === 0) {
            return;
        }

        if ($transaction = self::transactionRecord($connection)) {
            $transaction->addCallbackForRollback($callback);

            return;
        }

        if (self::hasActiveTransaction($connection)) {
            throw new RuntimeException('Unable to bind a rollback callback to the active database transaction.');
        }
    }

    /**
     * Move future reads to a fresh key without relying on a non-atomic
     * forget-then-put sequence. Transactional writes defer this until the
     * outer commit and discard it on rollback.
     */
    public static function bump(string $namespace, ?Connection $connection = null): void
    {
        if ($connection && self::deferBumpUntilCommit($namespace, $connection)) {
            return;
        }

        $key = self::generationKey($namespace);
        $failedGeneration = null;

        try {
            $candidate = self::token();
            $written = Cache::forever($key, $candidate);
            $generation = Cache::get($key);

            if ($written && is_string($generation) && hash_equals($candidate, $generation)) {
                return;
            }

            if (is_string($generation) && $generation !== '') {
                $failedGeneration = $generation;
            }
        } catch (Throwable) {
            // The namespace is disabled below if the store cannot persist or
            // verify a fresh generation.
        }

        try {
            Cache::forget($key);
            $generation = Cache::get($key);
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to invalidate the cache namespace.', previous: $exception);
        }

        if (is_string($generation) && $generation !== '') {
            if ($failedGeneration !== null && ! hash_equals($failedGeneration, $generation)) {
                return;
            }

            throw new RuntimeException('Unable to invalidate the cache namespace.');
        }
    }

    public static function identity(string $domain, string|int ...$segments): string
    {
        $payload = self::identitySegment($domain);

        foreach ($segments as $segment) {
            $payload .= self::identitySegment($segment);
        }

        return hash('sha256', $payload);
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
     * Active database transactions resolve without persistent cache access. If
     * a writer bumps the generation while a value is resolved, the read retries
     * under the new generation before falling back to an uncached resolution.
     * Objects, closures, and resources are returned without being cached.
     */
    public static function remember(
        string $namespace,
        string $variant,
        Closure|DateInterval|DateTimeInterface|int|null $ttl,
        Closure $resolver,
        ?Connection $connection = null,
    ): mixed {
        if ($connection && self::hasActiveTransaction($connection)) {
            return $resolver();
        }

        for ($attempt = 0; $attempt < self::MAX_RESOLUTION_ATTEMPTS; $attempt++) {
            $generation = self::generation($namespace);

            if ($generation === null) {
                return $resolver();
            }

            $key = self::valueKey($namespace, $generation, $variant);
            $resolved = false;

            try {
                $value = Cache::get($key);
            } catch (Throwable) {
                return $resolver();
            }

            if (! is_array($value) || ! self::isSafe($value)) {
                if ($value !== null) {
                    try {
                        Cache::forget($key);
                    } catch (Throwable) {
                        return $resolver();
                    }
                }

                $value = $resolver();
                $resolved = true;

                if (is_array($value) && self::isSafe($value)) {
                    try {
                        Cache::put($key, $value, $ttl);
                    } catch (Throwable) {
                        return $value;
                    }
                }
            }

            $currentGeneration = self::generation($namespace);

            if ($currentGeneration === null) {
                try {
                    Cache::forget($key);
                } catch (Throwable) {
                    // A failed generation check already makes this read uncached.
                }

                return $resolved ? $value : $resolver();
            }

            if (hash_equals($generation, $currentGeneration)) {
                return $value;
            }
        }

        return $resolver();
    }

    private static function deferBumpUntilCommit(string $namespace, Connection $connection): bool
    {
        if ($connection->transactionLevel() === 0) {
            return false;
        }

        if ($transaction = self::transactionRecord($connection)) {
            $transaction->addCallback(fn () => self::bump($namespace));

            return true;
        }

        if (self::hasActiveTransaction($connection)) {
            throw new RuntimeException('Unable to bind cache invalidation to the active database transaction.');
        }

        return false;
    }

    private static function generation(string $namespace): ?string
    {
        try {
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

            return is_string($generation) && $generation !== '' ? $generation : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function generationKey(string $namespace): string
    {
        return self::GENERATION_PREFIX.hash('sha256', $namespace);
    }

    private static function hasActiveTransaction(Connection $connection): bool
    {
        if ($connection->transactionLevel() === 0) {
            return false;
        }

        if (! app()->bound('db.transactions')) {
            return true;
        }

        $transactions = app('db.transactions');

        if (! method_exists($transactions, 'callbackApplicableTransactions')
            || ! method_exists($transactions, 'getPendingTransactions')) {
            return true;
        }

        if (self::transactionRecord($connection)) {
            return true;
        }

        $matchesConnection = fn ($transaction): bool => $transaction->connection === $connection->getName();

        return ! $transactions->getPendingTransactions()->contains($matchesConnection);
    }

    private static function identitySegment(string|int $segment): string
    {
        $type = is_int($segment) ? 'integer' : 'string';
        $value = (string) $segment;

        return strlen($type).':'.$type.':'.strlen($value).':'.$value;
    }

    private static function token(): string
    {
        return bin2hex(random_bytes(16));
    }

    private static function transactionRecord(Connection $connection): ?DatabaseTransactionRecord
    {
        if (! app()->bound('db.transactions')) {
            return null;
        }

        $transactions = app('db.transactions');

        if (! method_exists($transactions, 'callbackApplicableTransactions')) {
            return null;
        }

        $record = $transactions->callbackApplicableTransactions()
            ->filter(fn ($transaction): bool => $transaction->connection === $connection->getName())
            ->last();

        return $record instanceof DatabaseTransactionRecord ? $record : null;
    }

    private static function valueKey(string $namespace, string $generation, string $variant): string
    {
        return self::VALUE_PREFIX.hash('sha256', $namespace."\0".$generation."\0".$variant);
    }
}
