<?php

namespace Aura\Base\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class SchemaMigrationLock
{
    /** @var array<string, int> */
    private static array $heldLocks = [];

    public static function domain(Connection $connection, string $table): string
    {
        return implode(':', [
            'aura-schema',
            $connection->getDriverName(),
            $connection->getName(),
            $connection->getDatabaseName(),
            $table,
        ]);
    }

    public static function run(string $key, Closure $callback): mixed
    {
        return self::runOnConnection(DB::connection(), $key, $callback);
    }

    public static function runForTable(string $table, Closure $callback): mixed
    {
        $connection = DB::connection();

        return self::runOnConnection($connection, self::domain($connection, $table), $callback);
    }

    private static function acquire(Connection $connection, string $domain): ?Closure
    {
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $name = 'aura:'.substr(hash('sha256', $domain), 0, 59);
            $result = $connection->selectOne('SELECT GET_LOCK(?, 30) AS acquired', [$name]);

            if ((int) data_get($result, 'acquired') !== 1) {
                throw new RuntimeException("Unable to acquire Aura database schema lock [{$domain}].");
            }

            return static fn () => $connection->selectOne('SELECT RELEASE_LOCK(?)', [$name]);
        }

        if ($driver === 'pgsql') {
            [$first, $second] = self::postgresLockIds($domain);
            $connection->selectOne('SELECT pg_advisory_lock(?, ?)', [$first, $second]);

            return static fn () => $connection->selectOne('SELECT pg_advisory_unlock(?, ?)', [$first, $second]);
        }

        if ($driver === 'sqlite') {
            return self::acquireSqlite($connection, $domain);
        }

        throw new RuntimeException("Aura schema locking is not supported for database driver [{$driver}].");
    }

    private static function acquireSqlite(Connection $connection, string $domain): Closure
    {
        $database = $connection->getDatabaseName();
        $identity = $database === ':memory:' ? $domain.':process:'.getmypid() : (realpath($database) ?: $database).':'.$domain;
        $path = sys_get_temp_dir().'/aura-sqlite-schema-'.hash('sha256', $identity).'.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false || ! flock($handle, LOCK_EX)) {
            throw new RuntimeException("Unable to acquire Aura SQLite schema lock [{$domain}].");
        }

        return static function () use ($handle): void {
            flock($handle, LOCK_UN);
            fclose($handle);
        };
    }

    /** @return array{0: int, 1: int} */
    private static function postgresLockIds(string $domain): array
    {
        $hash = hash('sha256', $domain);

        return [
            self::signedInt32(substr($hash, 0, 8)),
            self::signedInt32(substr($hash, 8, 8)),
        ];
    }

    private static function runOnConnection(Connection $connection, string $domain, Closure $callback): mixed
    {
        $lockKey = $connection->getName().':'.hash('sha256', $domain);

        if (isset(self::$heldLocks[$lockKey])) {
            self::$heldLocks[$lockKey]++;

            try {
                return $callback();
            } finally {
                self::$heldLocks[$lockKey]--;
            }
        }

        $release = self::acquire($connection, $domain);
        self::$heldLocks[$lockKey] = 1;

        try {
            return $callback();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            unset(self::$heldLocks[$lockKey]);
            $release();
        }
    }

    private static function signedInt32(string $hex): int
    {
        $value = (int) hexdec($hex);

        return $value > 2147483647 ? $value - 4294967296 : $value;
    }
}
