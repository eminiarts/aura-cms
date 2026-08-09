<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

final class DatabaseStatementDeadline
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Connection $connection, int $milliseconds, Closure $callback): mixed
    {
        $restore = $this->apply($connection, $milliseconds);

        try {
            return $callback();
        } finally {
            try {
                $restore();
            } catch (Throwable $exception) {
                $connection->disconnect();
                Log::warning('Aura global search disconnected a database connection after deadline restoration failed.', [
                    'driver' => $connection->getDriverName(),
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    /** @return Closure(): void */
    private function apply(Connection $connection, int $milliseconds): Closure
    {
        return match ($connection->getDriverName()) {
            'mysql' => $this->applyMySql($connection, $milliseconds),
            'pgsql' => $this->applyPostgreSql($connection, $milliseconds),
            'sqlite' => $this->applySqlite($connection, $milliseconds),
            'sqlsrv' => $this->applySqlServer($connection, $milliseconds),
            default => throw new GlobalSearchExecutionUnavailable('The database driver has no supported global search statement deadline.'),
        };
    }

    /** @return Closure(): void */
    private function applyMySql(Connection $connection, int $milliseconds): Closure
    {
        $version = (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        if (str_contains(strtolower($version), 'mariadb')) {
            $row = (array) $connection->selectOne('SELECT @@SESSION.max_statement_time AS value');
            $originalLiteral = (string) ($row['value'] ?? '0');

            if (! is_numeric($originalLiteral)) {
                throw new GlobalSearchExecutionUnavailable('The MariaDB statement deadline could not be read.');
            }

            $original = (float) $originalLiteral;
            $requested = $milliseconds / 1000;

            if ($original > 0 && $original <= $requested) {
                return static function (): void {};
            }

            $this->executeStatement(
                $connection,
                'SET SESSION max_statement_time = '.number_format($requested, 3, '.', ''),
            );

            return function () use ($connection, $originalLiteral): void {
                $this->executeStatement(
                    $connection,
                    "SET SESSION max_statement_time = {$originalLiteral}",
                );
            };
        }

        $row = (array) $connection->selectOne('SELECT @@SESSION.MAX_EXECUTION_TIME AS value');
        $original = (int) ($row['value'] ?? 0);

        if ($original > 0 && $original <= $milliseconds) {
            return static function (): void {};
        }

        $this->executeStatement($connection, "SET SESSION MAX_EXECUTION_TIME = {$milliseconds}");

        return function () use ($connection, $original): void {
            $this->executeStatement($connection, "SET SESSION MAX_EXECUTION_TIME = {$original}");
        };
    }

    /** @return Closure(): void */
    private function applyPostgreSql(Connection $connection, int $milliseconds): Closure
    {
        $row = (array) $connection->selectOne("SELECT current_setting('statement_timeout') AS value");
        $original = (string) ($row['value'] ?? '0');
        $originalMilliseconds = $this->postgreSqlMilliseconds($original);

        if ($originalMilliseconds === null) {
            throw new GlobalSearchExecutionUnavailable('The PostgreSQL statement deadline could not be read.');
        }

        if ($originalMilliseconds > 0 && $originalMilliseconds <= $milliseconds) {
            return static function (): void {};
        }

        if ($connection->selectOne("SELECT set_config('statement_timeout', ?, false)", ["{$milliseconds}ms"]) === null) {
            throw new GlobalSearchExecutionUnavailable('The PostgreSQL driver rejected the global search statement deadline.');
        }

        return function () use ($connection, $original): void {
            if ($connection->selectOne("SELECT set_config('statement_timeout', ?, false)", [$original]) === null) {
                throw new GlobalSearchExecutionUnavailable('The PostgreSQL driver could not restore its statement deadline.');
            }
        };
    }

    /** @return Closure(): void */
    private function applySqlite(Connection $connection, int $milliseconds): Closure
    {
        $row = (array) $connection->selectOne('PRAGMA busy_timeout');
        $original = (int) (reset($row) ?: 0);

        if ($original <= $milliseconds) {
            return static function (): void {};
        }

        $this->executeStatement($connection, "PRAGMA busy_timeout = {$milliseconds}");

        return function () use ($connection, $original): void {
            $this->executeStatement($connection, "PRAGMA busy_timeout = {$original}");
        };
    }

    /** @return Closure(): void */
    private function applySqlServer(Connection $connection, int $milliseconds): Closure
    {
        if ($milliseconds < 1_000 || ! defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) {
            throw new GlobalSearchExecutionUnavailable('The SQL Server driver cannot enforce the configured sub-second statement deadline.');
        }

        $attribute = (int) constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT');
        $pdo = $connection->getPdo();
        $original = (int) $pdo->getAttribute($attribute);
        $requestedSeconds = max(1, intdiv($milliseconds, 1_000));

        if ($original > 0 && $original <= $requestedSeconds) {
            return static function (): void {};
        }

        if (! $pdo->setAttribute($attribute, $requestedSeconds)) {
            throw new GlobalSearchExecutionUnavailable('The SQL Server driver rejected the global search statement deadline.');
        }

        return function () use ($attribute, $original, $pdo): void {
            if (! $pdo->setAttribute($attribute, $original)) {
                throw new GlobalSearchExecutionUnavailable('The SQL Server driver could not restore its statement deadline.');
            }
        };
    }

    private function executeStatement(Connection $connection, string $statement): void
    {
        if (! $connection->statement($statement)) {
            throw new GlobalSearchExecutionUnavailable('The database driver rejected a global search statement deadline change.');
        }
    }

    private function postgreSqlMilliseconds(string $value): ?int
    {
        if (! preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*(us|ms|s|min|h|d)?$/', trim($value), $matches)) {
            return null;
        }

        $multiplier = match ($matches[2] ?? 'ms') {
            'us' => 0.001,
            's' => 1_000,
            'min' => 60_000,
            'h' => 3_600_000,
            'd' => 86_400_000,
            default => 1,
        };

        $milliseconds = ((float) $matches[1]) * $multiplier;

        return $milliseconds > 0 ? max(1, (int) ceil($milliseconds)) : 0;
    }
}
