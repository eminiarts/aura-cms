<?php

namespace Aura\Base\Tests\Fixtures\Reporting;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

final class ReportingDatabase
{
    public static function connect(string $driver): ?Connection
    {
        if ($driver === 'sqlite') {
            return DB::connection((string) config('database.default'));
        }

        $prefix = match ($driver) {
            'mysql' => 'MYSQL',
            'mariadb' => 'MARIADB',
            'pgsql' => 'POSTGRES',
            default => null,
        };

        if ($prefix === null || ! getenv("AURA_TEST_{$prefix}_DATABASE")) {
            return null;
        }

        $connectionName = 'core28_reporting_'.$driver;
        $configuration = [
            'driver' => $driver,
            'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
            'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: match ($driver) {
                'pgsql' => '5432',
                default => '3306',
            },
            'database' => getenv("AURA_TEST_{$prefix}_DATABASE"),
            'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: match ($driver) {
                'pgsql' => 'postgres',
                default => 'root',
            },
            'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
            'prefix' => '',
        ];

        if ($driver === 'pgsql') {
            $configuration['search_path'] = 'public';
        } else {
            $configuration['charset'] = 'utf8mb4';
            $configuration['collation'] = 'utf8mb4_unicode_ci';
            $configuration['strict'] = true;
        }

        config()->set("database.connections.{$connectionName}", $configuration);
        DB::purge($connectionName);

        return DB::connection($connectionName);
    }

    public static function disconnect(string $driver): void
    {
        if ($driver !== 'sqlite') {
            DB::purge('core28_reporting_'.$driver);
        }
    }
}
