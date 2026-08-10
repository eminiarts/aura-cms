<?php

namespace Aura\Base\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
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
            self::databaseIdentity($connection, $driver, $table),
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

    private static function absoluteSqlitePath(string $database): string
    {
        $directory = realpath(dirname($database));

        return $directory === false ? $database : $directory.DIRECTORY_SEPARATOR.basename($database);
    }

    private static function acquire(Connection $connection, string $domain): ?Closure
    {
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $pdo = $connection->getPdo();
            $name = 'aura:'.substr(hash('sha256', $domain), 0, 59);
            $acquired = self::selectScalar($pdo, 'SELECT GET_LOCK(?, ?) AS acquired', [$name, self::timeoutSeconds()]);

            if ((int) $acquired !== 1) {
                throw self::timeoutException($domain);
            }

            return static fn () => self::selectScalar($pdo, 'SELECT RELEASE_LOCK(?)', [$name]);
        }

        if ($driver === 'pgsql') {
            $pdo = $connection->getPdo();
            [$first, $second] = self::postgresLockIds($domain);
            $acquired = self::pollUntilTimeout(function () use ($pdo, $first, $second): bool {
                $result = self::selectScalar($pdo, 'SELECT pg_try_advisory_lock(?, ?) AS acquired', [$first, $second]);

                return in_array($result, [true, 1, '1', 't', 'true'], true);
            });

            if (! $acquired) {
                throw self::timeoutException($domain);
            }

            return static fn () => self::selectScalar($pdo, 'SELECT pg_advisory_unlock(?, ?)', [$first, $second]);
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

    private static function databaseIdentity(Connection $connection, string $driver, string $table): string
    {
        $database = $connection->getDatabaseName();

        if ($driver === 'sqlite') {
            $physicalTable = self::physicalTable($connection, $table);

            if ($database === ':memory:') {
                return 'memory:'.self::identityComponents((string) spl_object_id($connection->getPdo()), $physicalTable);
            }

            $path = realpath($database) ?: self::absoluteSqlitePath($database);
            $stat = @stat($path);

            return $stat === false || (int) $stat['ino'] === 0
                ? 'path:'.self::identityComponents($path, $physicalTable)
                : 'inode:'.self::identityComponents((string) $stat['dev'], (string) $stat['ino'], $physicalTable);
        }

        if ($driver === 'pgsql') {
            return self::postgresDatabaseIdentity($connection, $table);
        }

        if ($driver === 'mysql') {
            return self::mysqlDatabaseIdentity($connection, $table);
        }

        return self::identityComponents($database, self::physicalTable($connection, $table));
    }

    private static function identityComponents(string ...$components): string
    {
        return implode(':', array_map(rawurlencode(...), $components));
    }

    /** MySQL named locks are server-local, so endpoint spelling must not enter their key. */
    private static function mysqlDatabaseIdentity(Connection $connection, string $table): string
    {
        $database = (string) data_get($connection->selectOne('SELECT DATABASE() AS database_name', [], false), 'database_name');
        $physicalTable = self::physicalTable($connection, $table);

        if (str_contains($physicalTable, '.')) {
            [$database, $physicalTable] = explode('.', $physicalTable, 2);
        }

        return self::identityComponents(self::unquoteIdentifier($database), self::unquoteIdentifier($physicalTable));
    }

    private static function physicalTable(Connection $connection, string $table): string
    {
        $prefix = $connection->getTablePrefix();

        if ($prefix === '') {
            return $table;
        }

        if (! str_contains($table, '.')) {
            return $prefix.$table;
        }

        return substr_replace($table, '.'.$prefix, strrpos($table, '.'), 1);
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

    /** PostgreSQL advisory locks are database-local; resolve only the schema and relation within it. */
    private static function postgresDatabaseIdentity(Connection $connection, string $table): string
    {
        $physicalTable = self::physicalTable($connection, $table);
        $context = $connection->selectOne('SELECT current_database() AS database_name, current_schema() AS schema_name', [], false);
        $database = (string) data_get($context, 'database_name');

        if (str_contains($physicalTable, '.')) {
            [$schema, $physicalTable] = explode('.', $physicalTable, 2);
            $schema = self::unquoteIdentifier($schema);
            $physicalTable = self::unquoteIdentifier($physicalTable);
            $relation = $connection->selectOne(
                'SELECT namespace.nspname AS schema_name, relation.relname AS relation_name
                FROM pg_class AS relation
                JOIN pg_namespace AS namespace ON namespace.oid = relation.relnamespace
                WHERE namespace.nspname = ? AND relation.relname = ?',
                [$schema, $physicalTable],
                false,
            );
        } else {
            $schema = (string) (data_get($context, 'schema_name') ?: 'public');
            $physicalTable = self::unquoteIdentifier($physicalTable);
            $relation = $connection->selectOne(
                'SELECT namespace.nspname AS schema_name, relation.relname AS relation_name
                FROM pg_class AS relation
                JOIN pg_namespace AS namespace ON namespace.oid = relation.relnamespace
                WHERE namespace.nspname = ANY (current_schemas(false)) AND relation.relname = ?
                ORDER BY array_position(current_schemas(false), namespace.nspname)
                LIMIT 1',
                [$physicalTable],
                false,
            );
        }

        if ($relation !== null) {
            return self::identityComponents(
                $database,
                (string) data_get($relation, 'schema_name'),
                (string) data_get($relation, 'relation_name'),
            );
        }

        return self::identityComponents($database, $schema, $physicalTable);
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
        $lockKey = spl_object_id($connection).':'.hash('sha256', $domain);

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

    /**
     * @param  array<int, mixed>  $bindings
     */
    private static function selectScalar(PDO $pdo, string $query, array $bindings): mixed
    {
        $statement = $pdo->prepare($query);

        if ($statement === false) {
            throw new RuntimeException('Unable to prepare Aura database schema lock statement.');
        }

        $statement->execute($bindings);

        return $statement->fetchColumn();
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

    private static function unquoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if (strlen($identifier) >= 2
            && (($identifier[0] === '`' && $identifier[-1] === '`')
                || ($identifier[0] === '"' && $identifier[-1] === '"'))) {
            return substr($identifier, 1, -1);
        }

        return $identifier;
    }
}
