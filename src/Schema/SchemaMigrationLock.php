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
        $driver = $connection->getDriverName();

        return implode(':', [
            'aura-schema',
            $driver,
            self::databaseIdentity($connection, $driver),
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
            $result = $connection->selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$name, self::timeoutSeconds()]);

            if ((int) data_get($result, 'acquired') !== 1) {
                throw self::timeoutException($domain);
            }

            return static fn () => $connection->selectOne('SELECT RELEASE_LOCK(?)', [$name]);
        }

        if ($driver === 'pgsql') {
            [$first, $second] = self::postgresLockIds($domain);
            $acquired = self::pollUntilTimeout(function () use ($connection, $first, $second): bool {
                $result = $connection->selectOne('SELECT pg_try_advisory_lock(?, ?) AS acquired', [$first, $second]);

                return in_array(data_get($result, 'acquired'), [true, 1, '1', 't', 'true'], true);
            });

            if (! $acquired) {
                throw self::timeoutException($domain);
            }

            return static fn () => $connection->selectOne('SELECT pg_advisory_unlock(?, ?)', [$first, $second]);
        }

        if ($driver === 'sqlite') {
            return self::acquireSqlite($connection, $domain);
        }

        throw new RuntimeException("Aura schema locking is not supported for database driver [{$driver}].");
    }

    private static function acquireSqlite(Connection $connection, string $domain): Closure
    {
        $path = sys_get_temp_dir().'/aura-sqlite-schema-'.hash('sha256', $domain).'.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw self::timeoutException($domain);
        }

        $acquired = self::pollUntilTimeout(static fn (): bool => flock($handle, LOCK_EX | LOCK_NB));

        if (! $acquired) {
            fclose($handle);

            throw self::timeoutException($domain);
        }

        return static function () use ($handle): void {
            flock($handle, LOCK_UN);
            fclose($handle);
        };
    }

    private static function databaseIdentity(Connection $connection, string $driver): string
    {
        $database = $connection->getDatabaseName();

        if ($driver === 'sqlite') {
            if ($database === ':memory:') {
                return 'memory:'.spl_object_id($connection->getPdo());
            }

            return realpath($database) ?: $database;
        }

        if ($driver === 'pgsql') {
            $result = $connection->selectOne('SELECT current_schema() AS schema_name');
            $schema = (string) (data_get($result, 'schema_name') ?: 'public');

            return self::serverIdentity($connection, 5432).':'.$database.':'.$schema;
        }

        if ($driver === 'mysql') {
            return self::serverIdentity($connection, 3306).':'.$database;
        }

        return $database;
    }

    private static function pollIntervalMicroseconds(): int
    {
        return max(1, (int) config('aura.schema.lock_poll_interval_milliseconds', 50)) * 1000;
    }

    private static function pollUntilTimeout(Closure $attempt): bool
    {
        $deadline = microtime(true) + self::timeoutSeconds();

        do {
            if ($attempt()) {
                return true;
            }

            $remainingMicroseconds = (int) (($deadline - microtime(true)) * 1_000_000);

            if ($remainingMicroseconds <= 0) {
                return false;
            }

            usleep(min(self::pollIntervalMicroseconds(), $remainingMicroseconds));
        } while (true);
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
        $lockKey = hash('sha256', $domain);

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

    private static function serverIdentity(Connection $connection, int $defaultPort): string
    {
        $socket = (string) ($connection->getConfig('unix_socket') ?: '');

        if ($socket !== '') {
            return 'socket:'.(realpath($socket) ?: $socket);
        }

        $host = $connection->getConfig('host') ?: '127.0.0.1';
        $hosts = is_array($host) ? implode(',', $host) : (string) $host;
        $port = (int) ($connection->getConfig('port') ?: $defaultPort);

        return "tcp:{$hosts}:{$port}";
    }

    private static function signedInt32(string $hex): int
    {
        $value = (int) hexdec($hex);

        return $value > 2147483647 ? $value - 4294967296 : $value;
    }

    private static function timeoutException(string $domain): RuntimeException
    {
        return new RuntimeException("Timed out acquiring Aura database schema lock [{$domain}].");
    }

    private static function timeoutSeconds(): float
    {
        return max(0.0, (float) config('aura.schema.lock_timeout', 30));
    }
}
