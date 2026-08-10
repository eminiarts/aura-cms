<?php

use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Number;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Schema\SchemaMigrationLock;
use Aura\Base\Schema\SchemaUpdatePlan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

test('number fields declare portable integer and decimal schema columns', function () {
    $number = new Number;
    $integer = $number->columnDefinition([
        'slug' => 'quantity',
        'number_type' => 'integer',
        'scale' => 2,
    ]);
    $decimal = $number->columnDefinition([
        'slug' => 'amount',
        'number_type' => 'decimal',
        'precision' => 15,
        'scale' => 4,
    ]);

    expect($integer->type)->toBe('integer')
        ->and($integer->arguments)->toBe([])
        ->and($integer->toMigration('quantity'))->toBe("\$table->integer('quantity')->nullable()")
        ->and($decimal->type)->toBe('decimal')
        ->and($decimal->arguments)->toBe([15, 4])
        ->and($decimal->toMigration('amount'))->toContain("\$table->text('amount')")
        ->and($decimal->toMigration('amount'))->toContain("\$table->decimal('amount', 15, 4)");

    Schema::create('core_10_schema_values', function (Blueprint $table) use ($decimal, $integer) {
        $table->id();
        $integer->addTo($table, 'quantity');
        $decimal->addTo($table, 'amount');
    });

    DB::table('core_10_schema_values')->insert([
        'quantity' => -2,
        'amount' => '1234.5678',
    ]);

    expect(DB::table('core_10_schema_values')->value('quantity'))->toBe(-2)
        ->and((string) DB::table('core_10_schema_values')->value('amount'))->toBe('1234.5678');
});

test('datetime fields declare portable wall clock columns', function () {
    $datetime = new Datetime;
    $definition = $datetime->columnDefinition(['slug' => 'occurred_at']);

    expect($definition->type)->toBe('dateTime')
        ->and($definition->toMigration('occurred_at'))->toContain("\$table->dateTime('occurred_at')");
});

test('schema lock domains use physical database identity rather than connection aliases', function () {
    $database = tempnam(sys_get_temp_dir(), 'aura-core10-lock-domain-');
    $configuration = [
        'driver' => 'sqlite',
        'database' => $database,
        'password' => 'do-not-leak',
        'prefix' => '',
    ];
    config()->set('database.connections.core_10_lock_alias_a', $configuration);
    config()->set('database.connections.core_10_lock_alias_b', $configuration);
    config()->set('database.connections.core_10_lock_prefixed', [...$configuration, 'prefix' => 'tenant_']);

    $first = DB::connection('core_10_lock_alias_a');
    $second = DB::connection('core_10_lock_alias_b');
    $prefixed = DB::connection('core_10_lock_prefixed');

    try {
        expect(SchemaMigrationLock::domain($first, 'orders'))
            ->toBe(SchemaMigrationLock::domain($second, 'orders'))
            ->toContain($first->getDriverName())
            ->toContain(':inode:')
            ->not->toContain('core_10_lock_alias_a')
            ->not->toContain('core_10_lock_alias_b')
            ->not->toContain('do-not-leak')
            ->toEndWith(':orders')
            ->not->toBe(SchemaMigrationLock::domain($first, 'invoices'))
            ->not->toBe(SchemaMigrationLock::domain($prefixed, 'orders'));
    } finally {
        DB::purge('core_10_lock_alias_a');
        DB::purge('core_10_lock_alias_b');
        DB::purge('core_10_lock_prefixed');
        File::delete($database);
    }
});

test('sqlite schema lock domains fall back deterministically when the database path is missing or deleted', function () {
    $directory = sys_get_temp_dir().'/aura-core10-missing-lock-'.uniqid();
    $database = $directory.'/database.sqlite';
    File::makeDirectory($directory);
    $configuration = ['driver' => 'sqlite', 'database' => $database, 'prefix' => ''];
    config()->set('database.connections.core_10_missing_lock_a', $configuration);
    config()->set('database.connections.core_10_missing_lock_b', [
        ...$configuration,
        'database' => $directory.'/./database.sqlite',
    ]);

    $first = DB::connection('core_10_missing_lock_a');
    $second = DB::connection('core_10_missing_lock_b');

    try {
        $missingDomain = SchemaMigrationLock::domain($first, 'orders');

        expect($missingDomain)
            ->toContain(':path:')
            ->toBe(SchemaMigrationLock::domain($second, 'orders'));

        File::put($database, '');
        $inodeDomain = SchemaMigrationLock::domain($first, 'orders');

        expect($inodeDomain)->toContain(':inode:')
            ->not->toBe($missingDomain);

        File::delete($database);

        expect(SchemaMigrationLock::domain($first, 'orders'))
            ->toBe($missingDomain)
            ->toBe(SchemaMigrationLock::domain($second, 'orders'));
    } finally {
        DB::purge('core_10_missing_lock_a');
        DB::purge('core_10_missing_lock_b');
        File::deleteDirectory($directory);
    }
});

test('sqlite schema locks canonicalize symlinks and time out across processes before recovering after release', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the process contention contract.');
    }

    $database = tempnam(sys_get_temp_dir(), 'aura-core10-lock-db-');
    $originalDefault = config('database.default');
    $symlink = $database.'-alias';
    $hardlink = $database.'-hardlink';
    symlink(realpath($database), $symlink);
    link(realpath($database), $hardlink);
    $configuration = ['driver' => 'sqlite', 'database' => $database, 'prefix' => ''];
    config()->set('database.connections.core_10_lock_process_a', $configuration);
    config()->set('database.connections.core_10_lock_process_b', [...$configuration, 'database' => $symlink]);
    config()->set('database.connections.core_10_lock_hardlink', [...$configuration, 'database' => $hardlink]);
    config()->set('aura.schema.lock_timeout', 0.1);
    config()->set('aura.schema.lock_poll_interval_milliseconds', 10);

    [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
    $pid = pcntl_fork();

    if ($pid === 0) {
        fclose($reader);
        config()->set('database.default', 'core_10_lock_process_a');
        DB::purge('core_10_lock_process_a');
        SchemaMigrationLock::runForTable('orders', function () use ($writer): void {
            fwrite($writer, 'locked');
            fflush($writer);
            usleep(400_000);
        });
        fclose($writer);
        exit(0);
    }

    fclose($writer);

    try {
        expect(stream_get_contents($reader, 6))->toBe('locked')
            ->and(SchemaMigrationLock::domain(DB::connection('core_10_lock_process_a'), 'orders'))
            ->toBe(SchemaMigrationLock::domain(DB::connection('core_10_lock_process_b'), 'orders'))
            ->toBe(SchemaMigrationLock::domain(DB::connection('core_10_lock_hardlink'), 'orders'));

        config()->set('database.default', 'core_10_lock_process_b');
        DB::purge('core_10_lock_process_b');
        $startedAt = microtime(true);

        expect(fn () => SchemaMigrationLock::runForTable('orders', static fn () => null))
            ->toThrow(RuntimeException::class, 'Timed out acquiring Aura database schema lock');
        expect(microtime(true) - $startedAt)->toBeLessThan(0.35);

        pcntl_waitpid($pid, $status);
        expect(pcntl_wexitstatus($status))->toBe(0)
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'released'))->toBe('released');
    } finally {
        fclose($reader);
        pcntl_waitpid($pid, $status, WNOHANG);
        config()->set('database.default', $originalDefault);
        DB::purge('core_10_lock_process_a');
        DB::purge('core_10_lock_process_b');
        DB::purge('core_10_lock_hardlink');
        File::delete([$symlink, $hardlink, $database]);
    }
});

test('schema locks release when the protected callback throws', function () {
    expect(fn () => SchemaMigrationLock::runForTable('orders', static function (): void {
        throw new RuntimeException('callback failed');
    }))->toThrow(RuntimeException::class, 'callback failed');

    expect(SchemaMigrationLock::runForTable('orders', static fn (): string => 'released'))->toBe('released');
});

test('schema locks remain reentrant on the same database session', function () {
    expect(SchemaMigrationLock::runForTable(
        'orders',
        static fn (): string => SchemaMigrationLock::runForTable('orders', static fn (): string => 'nested'),
    ))->toBe('nested');
});

test('native schema locks contend across connection aliases and recover after a holder crashes', function (string $driver) {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the process contention contract.');
    }

    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} lock contract.");
    }

    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];
    $originalDefault = config('database.default');
    $configuration += $driver === 'mysql'
        ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : ['search_path' => 'public'];
    $first = "core_10_{$driver}_lock_alias_a";
    $second = "core_10_{$driver}_lock_alias_b";
    config()->set("database.connections.{$first}", $configuration);
    config()->set("database.connections.{$second}", $configuration);
    config()->set('aura.schema.lock_timeout', 0.1);
    config()->set('aura.schema.lock_poll_interval_milliseconds', 10);

    [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
    $pid = pcntl_fork();

    if ($pid === 0) {
        fclose($reader);
        config()->set('database.default', $first);
        DB::purge($first);
        SchemaMigrationLock::runForTable('orders', function () use ($writer): void {
            fwrite($writer, 'locked');
            fflush($writer);
            sleep(5);
        });
        exit(0);
    }

    fclose($writer);

    try {
        expect(stream_get_contents($reader, 6))->toBe('locked');
        config()->set('database.default', $second);
        DB::purge($second);
        expect(SchemaMigrationLock::domain(DB::connection($first), 'orders'))
            ->toBe(SchemaMigrationLock::domain(DB::connection($second), 'orders'));
        expect(fn () => SchemaMigrationLock::runForTable('orders', static fn () => null))
            ->toThrow(RuntimeException::class, 'Timed out acquiring Aura database schema lock');

        posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);
        expect(pcntl_wifsignaled($status))->toBeTrue()
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'recovered'))->toBe('recovered');
    } finally {
        fclose($reader);
        pcntl_waitpid($pid, $status, WNOHANG);
        config()->set('database.default', $originalDefault);
        DB::purge($first);
        DB::purge($second);
    }
})->with(['mysql', 'pgsql']);

test('native schema locks retain the acquiring session across holder reconnect and purge', function (string $driver) {
    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} reconnect lock contract.");
    }

    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];
    $configuration += $driver === 'mysql'
        ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : ['search_path' => 'public'];
    $first = "core_10_{$driver}_reconnect_holder";
    $second = "core_10_{$driver}_reconnect_contender";
    $readDatabase = $driver === 'mysql' ? 'information_schema' : 'postgres';
    $originalDefault = config('database.default');
    config()->set("database.connections.{$first}", [
        ...$configuration,
        'read' => ['database' => $readDatabase],
        'write' => ['database' => $database],
    ]);
    config()->set("database.connections.{$second}", $configuration);
    config()->set('database.default', $first);
    config()->set('aura.schema.lock_timeout', 0.1);
    config()->set('aura.schema.lock_poll_interval_milliseconds', 10);
    $holder = DB::connection($first);
    $databaseQuery = $driver === 'mysql'
        ? 'SELECT DATABASE() AS database_name'
        : 'SELECT current_database() AS database_name';
    $sessionQuery = $driver === 'mysql'
        ? 'SELECT CONNECTION_ID() AS session_id'
        : 'SELECT pg_backend_pid() AS session_id';

    expect(data_get($holder->selectOne($databaseQuery, [], true), 'database_name'))->toBe($readDatabase)
        ->and(data_get($holder->selectOne($databaseQuery, [], false), 'database_name'))->toBe($database);

    $holderSessionId = (int) data_get($holder->selectOne($sessionQuery, [], false), 'session_id');

    try {
        $result = SchemaMigrationLock::runForTable('orders', function () use ($first, $holder, $second): string {
            DB::disconnect($first);
            $reconnected = DB::reconnect($first);

            expect($reconnected)->toBe($holder)
                ->and(SchemaMigrationLock::runForTable(
                    'orders',
                    static fn (): string => 'nested after reconnect',
                ))->toBe('nested after reconnect');

            DB::purge($first);
            config()->set('database.default', $second);

            expect(fn () => SchemaMigrationLock::runForTable('orders', static fn () => null))
                ->toThrow(RuntimeException::class, 'Timed out acquiring Aura database schema lock');

            return 'protected';
        });
        $activeSessionQuery = $driver === 'mysql'
            ? 'SELECT COUNT(*) AS active_sessions FROM information_schema.processlist WHERE id = ?'
            : 'SELECT COUNT(*) AS active_sessions FROM pg_stat_activity WHERE pid = ?';
        $activeSessions = (int) data_get(
            DB::connection($second)->selectOne($activeSessionQuery, [$holderSessionId], false),
            'active_sessions',
        );

        expect($result)->toBe('protected')
            ->and($activeSessions)->toBe(0)
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'released'))->toBe('released');
    } finally {
        config()->set('database.default', $originalDefault);
        DB::purge($first);
        DB::purge($second);
    }
})->with(['mysql', 'pgsql']);

test('native retained schema locks release after a reconnected callback throws', function (string $driver) {
    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} reconnect release contract.");
    }

    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];
    $configuration += $driver === 'mysql'
        ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : ['search_path' => 'public'];
    $first = "core_10_{$driver}_reconnect_throw_holder";
    $second = "core_10_{$driver}_reconnect_throw_contender";
    $originalDefault = config('database.default');
    config()->set("database.connections.{$first}", $configuration);
    config()->set("database.connections.{$second}", $configuration);
    config()->set('database.default', $first);
    config()->set('aura.schema.lock_timeout', 0.1);
    config()->set('aura.schema.lock_poll_interval_milliseconds', 10);

    try {
        expect(fn () => SchemaMigrationLock::runForTable('orders', function () use ($first, $second): void {
            DB::disconnect($first);
            DB::reconnect($first);
            config()->set('database.default', $second);

            expect(fn () => SchemaMigrationLock::runForTable('orders', static fn () => null))
                ->toThrow(RuntimeException::class, 'Timed out acquiring Aura database schema lock');

            throw new RuntimeException('reconnected callback failed');
        }))->toThrow(RuntimeException::class, 'reconnected callback failed')
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'released'))->toBe('released');
    } finally {
        config()->set('database.default', $originalDefault);
        DB::purge($first);
        DB::purge($second);
    }
})->with(['mysql', 'pgsql']);

test('native schema lock domains canonicalize endpoint and search path aliases', function (string $driver) {
    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} physical lock identity contract.");
    }

    $port = getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432');
    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => $port,
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];
    $configuration += $driver === 'mysql'
        ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : ['search_path' => 'public'];
    $primary = "core_10_{$driver}_physical_primary";
    config()->set("database.connections.{$primary}", $configuration);
    $connection = DB::connection($primary);
    $schemaHead = 'core10_lock_identity_head';
    $schemaTarget = 'core10_lock_identity_target';
    $aliases = [];
    $unavailableAliases = [];

    try {
        if ($driver === 'pgsql') {
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaHead} CASCADE");
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaTarget} CASCADE");
            $connection->unprepared("CREATE SCHEMA {$schemaHead}");
            $connection->unprepared("CREATE SCHEMA {$schemaTarget}");
            $connection->unprepared("CREATE TABLE {$schemaTarget}.orders (id integer)");
            $connection->unprepared("CREATE TABLE {$schemaHead}.audit (id integer)");
            $connection->unprepared("CREATE TABLE {$schemaTarget}.audit (id integer)");
        }

        $endpointConfigurations = [
            'ip' => [...$configuration, 'host' => '127.0.0.1'],
            'hostname' => [...$configuration, 'host' => 'localhost'],
        ];
        $defaultPortConfiguration = [...$configuration, 'host' => '127.0.0.1'];
        unset($defaultPortConfiguration['port']);
        $endpointConfigurations['default_port'] = $defaultPortConfiguration;

        if ($driver === 'mysql') {
            $socket = (string) data_get($connection->selectOne('SELECT @@socket AS socket_path'), 'socket_path');

            if ($socket !== '' && file_exists($socket)) {
                $endpointConfigurations['socket'] = [...$configuration, 'host' => 'localhost', 'unix_socket' => $socket];
            } else {
                $unavailableAliases[] = 'socket';
            }
        } else {
            $socketDirectories = (string) data_get($connection->selectOne('SHOW unix_socket_directories'), 'unix_socket_directories');
            $socketDirectory = trim(explode(',', $socketDirectories)[0] ?? '', " \t\n\r\0\x0B'");

            if ($socketDirectory !== '' && is_dir($socketDirectory)) {
                $endpointConfigurations['socket'] = [...$configuration, 'host' => $socketDirectory];
            } else {
                $unavailableAliases[] = 'socket';
            }

            $endpointConfigurations['ip']['search_path'] = "{$schemaHead},{$schemaTarget}";
            $endpointConfigurations['hostname']['search_path'] = $schemaTarget;
            $endpointConfigurations['default_port']['search_path'] = $schemaTarget;

            if (isset($endpointConfigurations['socket'])) {
                $endpointConfigurations['socket']['search_path'] = $schemaTarget;
            }
        }

        foreach ($endpointConfigurations as $alias => $endpointConfiguration) {
            $name = "core_10_{$driver}_physical_{$alias}";
            config()->set("database.connections.{$name}", $endpointConfiguration);

            try {
                DB::connection($name)->getPdo();
                $aliases[] = $name;
            } catch (Throwable) {
                DB::purge($name);
                $unavailableAliases[] = $alias;
            }
        }

        if ($unavailableAliases !== []) {
            $this->markTestSkipped("Unavailable equivalent {$driver} endpoints: ".implode(', ', $unavailableAliases).'.');
        }

        $domains = collect($aliases)
            ->map(fn (string $name): string => SchemaMigrationLock::domain(DB::connection($name), 'orders'));

        expect($domains->unique()->values()->all())->toHaveCount(1)
            ->and(SchemaMigrationLock::domain(DB::connection($aliases[0]), 'invoices'))
            ->not->toBe($domains->first());

        if ($driver === 'pgsql') {
            expect(SchemaMigrationLock::domain(DB::connection($aliases[0]), "{$schemaHead}.audit"))
                ->not->toBe(SchemaMigrationLock::domain(DB::connection($aliases[0]), "{$schemaTarget}.audit"));
        }
    } finally {
        if ($driver === 'pgsql') {
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaHead} CASCADE");
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaTarget} CASCADE");
        }

        foreach ([$primary, ...$aliases] as $name) {
            DB::purge($name);
        }
    }
})->with(['mysql', 'pgsql']);

test('native endpoint aliases contend while distinct tables remain independent', function (string $driver) {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the endpoint contention contract.');
    }

    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} endpoint contention contract.");
    }

    $configuration = [
        'driver' => $driver,
        'host' => '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];
    $configuration += $driver === 'mysql'
        ? ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true]
        : ['search_path' => 'public'];
    $first = "core_10_{$driver}_endpoint_ip";
    $second = "core_10_{$driver}_endpoint_hostname";
    $differentDatabase = "core_10_{$driver}_different_database";
    $splitConnection = "core_10_{$driver}_read_write_split";
    $otherDatabase = $driver === 'mysql' ? 'information_schema' : 'postgres';

    if ($otherDatabase === $database) {
        $otherDatabase = $driver === 'mysql' ? 'mysql' : 'template1';
    }

    $schemaHead = 'core10_lock_contention_head';
    $schemaTarget = 'core10_lock_contention_target';
    $originalDefault = config('database.default');
    config()->set("database.connections.{$first}", $configuration);
    config()->set("database.connections.{$second}", [...$configuration, 'host' => 'localhost']);
    config()->set("database.connections.{$differentDatabase}", [...$configuration, 'database' => $otherDatabase]);
    config()->set("database.connections.{$splitConnection}", [
        ...$configuration,
        'read' => ['database' => $otherDatabase],
        'write' => ['database' => $database],
    ]);
    config()->set('aura.schema.lock_timeout', 0.1);
    config()->set('aura.schema.lock_poll_interval_milliseconds', 10);
    $connection = DB::connection($first);

    try {
        DB::connection($second)->getPdo();
        DB::connection($differentDatabase)->getPdo();
        $split = DB::connection($splitConnection);
        $split->getReadPdo();
        $split->getPdo();
    } catch (Throwable) {
        DB::purge($first);
        DB::purge($second);
        DB::purge($differentDatabase);
        DB::purge($splitConnection);
        $this->markTestSkipped("Equivalent endpoints and a second database must be reachable on the {$driver} test server.");
    }

    $databaseQuery = $driver === 'mysql'
        ? 'SELECT DATABASE() AS database_name'
        : 'SELECT current_database() AS database_name';
    expect(data_get($split->selectOne($databaseQuery, [], true), 'database_name'))->toBe($otherDatabase)
        ->and(data_get($split->selectOne($databaseQuery, [], false), 'database_name'))->toBe($database)
        ->and(SchemaMigrationLock::domain($split, 'writer_lock_probe'))
        ->toBe(SchemaMigrationLock::domain($connection, 'writer_lock_probe'));

    if ($driver === 'pgsql') {
        $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaHead} CASCADE");
        $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaTarget} CASCADE");
        $connection->unprepared("CREATE SCHEMA {$schemaHead}");
        $connection->unprepared("CREATE SCHEMA {$schemaTarget}");
        $connection->unprepared("CREATE TABLE {$schemaTarget}.orders (id integer)");
        config()->set("database.connections.{$first}.search_path", "{$schemaHead},{$schemaTarget}");
        config()->set("database.connections.{$second}.search_path", $schemaTarget);
        DB::purge($first);
        DB::purge($second);
    }

    [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
    $pid = pcntl_fork();

    if ($pid === 0) {
        fclose($reader);
        config()->set('database.default', $first);
        DB::purge($first);
        SchemaMigrationLock::runForTable('orders', function () use ($writer): void {
            fwrite($writer, 'locked');
            fflush($writer);
            sleep(5);
        });
        exit(0);
    }

    fclose($writer);

    try {
        expect(stream_get_contents($reader, 6))->toBe('locked');
        config()->set('database.default', $second);
        DB::purge($second);
        expect(SchemaMigrationLock::domain(DB::connection($first), 'orders'))
            ->toBe(SchemaMigrationLock::domain(DB::connection($second), 'orders'))
            ->and(SchemaMigrationLock::runForTable('invoices', static fn (): string => 'independent'))
            ->toBe('independent');
        config()->set('database.default', $differentDatabase);
        expect(SchemaMigrationLock::domain(DB::connection($differentDatabase), 'orders'))
            ->not->toBe(SchemaMigrationLock::domain(DB::connection($second), 'orders'))
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'independent'))
            ->toBe('independent');
        config()->set('database.default', $second);
        expect(fn () => SchemaMigrationLock::runForTable('orders', static fn () => null))
            ->toThrow(RuntimeException::class, 'Timed out acquiring Aura database schema lock');

        posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);
        expect(pcntl_wifsignaled($status))->toBeTrue()
            ->and(SchemaMigrationLock::runForTable('orders', static fn (): string => 'recovered'))->toBe('recovered');
    } finally {
        fclose($reader);

        if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }

        if ($driver === 'pgsql') {
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaHead} CASCADE");
            $connection->unprepared("DROP SCHEMA IF EXISTS {$schemaTarget} CASCADE");
        }

        config()->set('database.default', $originalDefault);
        DB::purge($first);
        DB::purge($second);
        DB::purge($differentDatabase);
        DB::purge($splitConnection);
    }
})->with(['mysql', 'pgsql']);

test('schema definitions compare canonical types defaults nullability and attributes', function () {
    $definition = new FieldColumn('dateTime', nullable: false, default: 'CURRENT_TIMESTAMP');

    expect($definition->matchesDatabaseColumn([
        'type_name' => 'datetime',
        'type' => 'datetime',
        'nullable' => false,
        'default' => '(current_timestamp)',
    ], 'mysql'))->toBeTrue()
        ->and($definition->matchesDatabaseColumn([
            'type_name' => 'timestamp',
            'type' => 'timestamp',
            'nullable' => false,
            'default' => '(current_timestamp)',
        ], 'mysql'))->toBeFalse()
        ->and((new FieldColumn('integer'))->matchesDatabaseColumn([
            'type_name' => 'int',
            'type' => 'int unsigned',
            'nullable' => true,
            'default' => null,
        ], 'mysql'))->toBeFalse();
});

test('structured schema plans preserve driver-specific decimal precision and scale', function () {
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-plan-');
    $definition = new FieldColumn('decimal', [12, 4], driverTypes: ['sqlite' => 'text']);
    $plan = new SchemaUpdatePlan('core_10_plan_values', ['amount' => $definition]);

    File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

    try {
        $restored = SchemaUpdatePlan::fromMigrationFile($path);

        expect($restored->columns['amount']->forDriver('mysql')->type)->toBe('decimal')
            ->and($restored->columns['amount']->forDriver('mysql')->arguments)->toBe([12, 4])
            ->and($restored->columns['amount']->forDriver('pgsql')->arguments)->toBe([12, 4])
            ->and($restored->columns['amount']->forDriver('sqlite')->type)->toBe('text')
            ->and($restored->columns['amount']->forDriver('sqlite')->arguments)->toBe([]);
    } finally {
        File::delete($path);
    }
});

test('schema update preflights every conversion before additions and drops', function () {
    $tableName = 'core_10_preflight_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->string('amount')->nullable();
        $table->string('drop_after_failure')->nullable();
    });
    DB::table($tableName)->insert(['amount' => 'not-an-integer']);

    $path = tempnam(sys_get_temp_dir(), 'aura-core10-preflight-');
    $plan = new SchemaUpdatePlan($tableName, [
        'amount' => new FieldColumn('integer'),
        'added_before_failure' => new FieldColumn('string'),
    ]);
    File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

    try {
        $exitCode = Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]);

        expect($exitCode)->not->toBe(0)
            ->and(Artisan::output())->toContain('Refusing lossy conversion')
            ->and(Schema::hasColumn($tableName, 'added_before_failure'))->toBeFalse()
            ->and(Schema::hasColumn($tableName, 'drop_after_failure'))->toBeTrue()
            ->and(DB::table($tableName)->value('amount'))->toBe('not-an-integer');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('schema update only preflights columns whose canonical definition changed', function () {
    $tableName = 'core_10_true_diff_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->date('legacy_date')->nullable();
    });
    DB::table($tableName)->insert(['legacy_date' => 'not-a-date']);

    $path = tempnam(sys_get_temp_dir(), 'aura-core10-true-diff-');
    $plan = new SchemaUpdatePlan($tableName, [
        'legacy_date' => new FieldColumn('date'),
        'new_value' => new FieldColumn('string'),
    ]);
    File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

    try {
        expect(Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]))->toBe(0, Artisan::output())
            ->and(Schema::hasColumn($tableName, 'new_value'))->toBeTrue()
            ->and(DB::table($tableName)->value('legacy_date'))->toBe('not-a-date');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('teams disabled schema plans omit team ownership and persist normal resources', function () {
    config()->set('aura.teams', false);
    $tableName = 'core_10_without_teams_values';
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-without-teams-').'.php';
    $plan = new SchemaUpdatePlan($tableName, ['name' => new FieldColumn('string')]);
    $migration = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('__TABLE__', function (Blueprint $table) { $table->id(); }); }
    public function down(): void { Schema::dropIfExists('__TABLE__'); }
};
PHP);
    File::put($path, $plan->embedIn($migration));

    try {
        expect(Artisan::call('aura:schema-update', ['migration' => $path, '--no-interaction' => true]))
            ->toBe(0, Artisan::output())
            ->and(Schema::hasColumn($tableName, 'team_id'))->toBeFalse();

        $id = DB::table($tableName)->insertGetId([
            'name' => 'Normal resource',
            'user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        expect($id)->toBeInt();
    } finally {
        Schema::dropIfExists($tableName);
        File::delete($path);
    }
});

test('schema update reports missing and unparseable migration plans as failures', function () {
    $missing = sys_get_temp_dir().'/aura-core10-missing-'.uniqid().'.php';
    $invalid = tempnam(sys_get_temp_dir(), 'aura-core10-invalid-');
    $nested = tempnam(sys_get_temp_dir(), 'aura-core10-nested-');
    File::put($invalid, "<?php\n\nreturn new class { public function broken( };\n");
    File::put($nested, <<<'PHP'
<?php

return new class
{
    public function up(): void
    {
        if (true) {
            // A syntactically valid nested migration without a structured plan.
        }
    }
};
PHP);

    try {
        expect(Artisan::call('aura:schema-update', [
            'migration' => $missing,
            '--no-interaction' => true,
        ]))->not->toBe(0)
            ->and(Artisan::call('aura:schema-update', [
                'migration' => $invalid,
                '--no-interaction' => true,
            ]))->not->toBe(0)
            ->and(Artisan::call('aura:schema-update', [
                'migration' => $nested,
                '--no-interaction' => true,
            ]))->not->toBe(0);
    } finally {
        File::delete([$invalid, $nested]);
    }
});

test('schema update never executes migration effects that disagree with the structured plan', function () {
    $plannedTable = 'core_10_planned_schema_values';
    $unexpectedTable = 'core_10_unexpected_schema_values';
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-mismatched-plan-').'.php';
    $plan = new SchemaUpdatePlan($plannedTable, [
        'name' => new FieldColumn('string'),
    ]);
    $migration = str_replace('__UNEXPECTED_TABLE__', $unexpectedTable, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('__UNEXPECTED_TABLE__', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__UNEXPECTED_TABLE__');
    }
};
PHP);
    File::put($path, $plan->embedIn($migration));

    try {
        expect(Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]))->not->toBe(0)
            ->and(Artisan::output())->toContain('does not match')
            ->and(Schema::hasTable($plannedTable))->toBeFalse()
            ->and(Schema::hasTable($unexpectedTable))->toBeFalse();
    } finally {
        Schema::dropIfExists($unexpectedTable);
        Schema::dropIfExists($plannedTable);
        DB::table('migrations')->where('migration', pathinfo($path, PATHINFO_FILENAME))->delete();
        File::delete($path);
    }
});

test('the structured plan is the only executable source for a missing table', function () {
    $tableName = 'core_10_plan_created_values';
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-plan-create-').'.php';
    $migrationName = pathinfo($path, PATHINFO_FILENAME);
    $plan = new SchemaUpdatePlan($tableName, [
        'from_plan' => new FieldColumn('integer'),
    ]);
    $migration = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('__TABLE__', function (Blueprint $table) {
            $table->id();
            $table->string('from_php');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__TABLE__');
    }
};
PHP);
    File::put($path, $plan->embedIn($migration));

    try {
        $exitCode = Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]);

        expect($exitCode)->toBe(0, Artisan::output())
            ->and(Schema::hasColumn($tableName, 'from_plan'))->toBeTrue()
            ->and(Schema::hasColumn($tableName, 'from_php'))->toBeFalse()
            ->and(DB::table('migrations')->where('migration', $migrationName)->exists())->toBeTrue();
    } finally {
        Schema::dropIfExists($tableName);
        DB::table('migrations')->where('migration', $migrationName)->delete();
        File::delete($path);
    }
});

test('configured large numbers use exact storage on sqlite', function () {
    $number = new Number;
    $integer = $number->columnDefinition([
        'slug' => 'large_integer',
        'number_type' => 'integer',
        'precision' => 65,
    ]);
    $decimal = $number->columnDefinition([
        'slug' => 'large_decimal',
        'number_type' => 'decimal',
        'precision' => 65,
        'scale' => 30,
    ]);

    Schema::create('core_10_exact_schema_values', function (Blueprint $table) use ($decimal, $integer) {
        $table->id();
        $integer->addTo($table, 'large_integer');
        $decimal->addTo($table, 'large_decimal');
    });

    $largeInteger = '12345678901234567890123456789012345678901234567890123456789012345';
    $largeDecimal = '12345678901234567890123456789012345.123456789012345678901234567890';

    DB::table('core_10_exact_schema_values')->insert([
        'large_integer' => $largeInteger,
        'large_decimal' => $largeDecimal,
    ]);

    expect($integer->type)->toBe('decimal')
        ->and($integer->arguments)->toBe([65, 0])
        ->and(DB::table('core_10_exact_schema_values')->value('large_integer'))->toBe($largeInteger)
        ->and(DB::table('core_10_exact_schema_values')->value('large_decimal'))->toBe($largeDecimal);
});

test('configured large numbers use native exact decimal storage on mysql when available', function () {
    $database = getenv('AURA_TEST_MYSQL_DATABASE') ?: null;

    if (! $database) {
        $this->markTestSkipped('Set AURA_TEST_MYSQL_DATABASE to run the MySQL exactness contract.');
    }

    $connection = 'core_10_mysql';
    $originalDefault = config('database.default');
    $tableName = 'aura_core_10_exact_'.getmypid();

    config()->set("database.connections.{$connection}", [
        'driver' => 'mysql',
        'host' => getenv('AURA_TEST_MYSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('AURA_TEST_MYSQL_PORT') ?: '3306',
        'database' => $database,
        'username' => getenv('AURA_TEST_MYSQL_USERNAME') ?: 'root',
        'password' => getenv('AURA_TEST_MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    config()->set('database.default', $connection);
    DB::purge($connection);

    try {
        Schema::create($tableName, fn (Blueprint $table) => $table->id());
        $generator = new CreateDatabaseMigration(app(Filesystem::class));
        $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
        $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
        $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
        $fields = collect([
            [
                'slug' => 'large_integer',
                'type' => Number::class,
                'number_type' => 'integer',
                'precision' => 65,
            ],
            [
                'slug' => 'large_decimal',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 65,
                'scale' => 30,
            ],
        ]);
        $up = $generate->invoke($generator, $fields, 'add');
        $down = $generateDown->invoke($generator, $fields, 'add');
        $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
        $content = $update->invoke($generator, $template, $up, '', '', $down, '', '', '');
        $path = tempnam(sys_get_temp_dir(), 'aura-core10-mysql-migration-');
        File::put($path, $content);
        $migration = require $path;
        $migration->up();

        $largeInteger = '12345678901234567890123456789012345678901234567890123456789012345';
        $largeDecimal = '12345678901234567890123456789012345.123456789012345678901234567890';
        DB::table($tableName)->insert([
            'large_integer' => $largeInteger,
            'large_decimal' => $largeDecimal,
        ]);

        expect(DB::table($tableName)->value('large_integer'))->toBe($largeInteger)
            ->and(DB::table($tableName)->value('large_decimal'))->toBe($largeDecimal)
            ->and(Schema::getColumnType($tableName, 'large_integer'))->toBe('decimal')
            ->and(Schema::getColumnType($tableName, 'large_decimal'))->toBe('decimal');

        $migration->down();
    } finally {
        if (isset($path)) {
            File::delete($path);
        }

        Schema::dropIfExists($tableName);
        DB::disconnect($connection);
        config()->set('database.default', $originalDefault);
    }
});

test('structured schema updates retain decimals and preflight every mutation on real databases', function (string $driver) {
    $prefix = $driver === 'pgsql' ? 'POSTGRES' : 'MYSQL';
    $database = getenv("AURA_TEST_{$prefix}_DATABASE") ?: null;

    if (! $database) {
        $this->markTestSkipped("Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} schema-update contract.");
    }

    $connection = 'core_10_schema_update_'.$driver;
    $originalDefault = config('database.default');
    $tableName = 'aura_c10_update_'.$driver.'_'.getmypid();
    $failureTable = 'aura_c10_failure_'.$driver.'_'.getmypid();
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-schema-update-');
    $failurePath = tempnam(sys_get_temp_dir(), 'aura-core10-schema-failure-');
    $hostileTable = 'aura_c10_hostile_'.$driver.'_'.getmypid();
    $hostilePath = tempnam(sys_get_temp_dir(), 'aura-core10-schema-hostile-');
    $configuration = [
        'driver' => $driver,
        'host' => getenv("AURA_TEST_{$prefix}_HOST") ?: '127.0.0.1',
        'port' => getenv("AURA_TEST_{$prefix}_PORT") ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv("AURA_TEST_{$prefix}_USERNAME") ?: ($driver === 'mysql' ? 'root' : getenv('USER')),
        'password' => getenv("AURA_TEST_{$prefix}_PASSWORD") ?: '',
        'prefix' => '',
    ];

    if ($driver === 'mysql') {
        $configuration += [
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict' => true,
        ];
    } else {
        $configuration += ['search_path' => 'public'];
    }

    config()->set("database.connections.{$connection}", $configuration);
    config()->set('database.default', $connection);
    DB::purge($connection);
    $captureDdl = false;
    $executedDdl = [];
    DB::listen(function ($query) use (&$captureDdl, &$executedDdl): void {
        if ($captureDdl
            && ! $query->connection->pretending()
            && preg_match('/^\s*(?:alter|create|drop|rename)\b/i', $query->sql) === 1) {
            $executedDdl[] = $query->sql;
        }
    });

    try {
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 4)->nullable();
        });
        DB::table($tableName)->insert(['amount' => '12345678.9012']);
        $plan = new SchemaUpdatePlan($tableName, [
            'amount' => new FieldColumn('decimal', [12, 4], driverTypes: ['sqlite' => 'text']),
            'added_after_preflight' => new FieldColumn('string'),
        ]);
        File::put($path, $plan->embedIn("<?php\n\nreturn new class {};\n"));

        $captureDdl = true;
        $successExitCode = Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]);
        $captureDdl = false;

        expect($successExitCode)->toBe(0)
            ->and(Schema::hasColumn($tableName, 'added_after_preflight'))->toBeTrue()
            ->and(DB::table($tableName)->value('amount'))->toBe('12345678.9012');

        if ($driver === 'mysql') {
            expect($executedDdl)->toHaveCount(1)
                ->and(strtolower($executedDdl[0]))->not->toContain('modify `amount`')
                ->and(strtolower($executedDdl[0]))->toContain('add `added_after_preflight`');
        }

        $amount = collect(Schema::getColumns($tableName))->firstWhere('name', 'amount');

        expect(strtolower($amount['type'] ?? ''))->toContain($driver === 'mysql' ? 'decimal(12,4)' : 'numeric(12,4)');

        $changedPlan = new SchemaUpdatePlan($tableName, [
            'amount' => new FieldColumn('decimal', [12, 4], nullable: false, driverTypes: ['sqlite' => 'text']),
            'added_after_preflight' => new FieldColumn('string'),
        ]);
        File::put($path, $changedPlan->embedIn("<?php\n\nreturn new class {};\n"));
        $executedDdl = [];
        $captureDdl = true;
        expect(Artisan::call('aura:schema-update', [
            'migration' => $path,
            '--no-interaction' => true,
        ]))->toBe(0);
        $captureDdl = false;

        $amount = collect(Schema::getColumns($tableName))->firstWhere('name', 'amount');
        expect(strtolower($amount['type'] ?? ''))->toContain($driver === 'mysql' ? 'decimal(12,4)' : 'numeric(12,4)')
            ->and((bool) ($amount['nullable'] ?? true))->toBeFalse();

        if ($driver === 'mysql') {
            expect(collect($executedDdl)->contains(
                fn (string $sql): bool => str_contains(strtolower($sql), 'modify `amount`'),
            ))->toBeTrue();
        }

        Schema::create($failureTable, function (Blueprint $table) {
            $table->id();
            $table->string('amount')->nullable();
            $table->string('drop_after_failure')->nullable();
        });
        DB::table($failureTable)->insert(['amount' => 'not-an-integer']);
        $failurePlan = new SchemaUpdatePlan($failureTable, [
            'amount' => new FieldColumn('integer'),
            'added_before_failure' => new FieldColumn('string'),
        ]);
        File::put($failurePath, $failurePlan->embedIn("<?php\n\nreturn new class {};\n"));

        expect(Artisan::call('aura:schema-update', [
            'migration' => $failurePath,
            '--no-interaction' => true,
        ]))->not->toBe(0)
            ->and(Schema::hasColumn($failureTable, 'added_before_failure'))->toBeFalse()
            ->and(Schema::hasColumn($failureTable, 'drop_after_failure'))->toBeTrue();

        Schema::create($hostileTable, function (Blueprint $table) {
            $table->id();
            $table->string('safe_value', 20)->nullable();
            $table->string('unsupported_date')->nullable();
        });
        DB::table($hostileTable)->insert([
            'safe_value' => 'unchanged',
            'unsupported_date' => 'not-a-date',
        ]);
        $hostilePlan = new SchemaUpdatePlan($hostileTable, [
            'safe_value' => new FieldColumn('string', [255]),
            'unsupported_date' => new FieldColumn('date'),
        ]);
        File::put($hostilePath, $hostilePlan->embedIn("<?php\n\nreturn new class {};\n"));

        $executedDdl = [];
        $captureDdl = true;
        $hostileExitCode = Artisan::call('aura:schema-update', [
            'migration' => $hostilePath,
            '--no-interaction' => true,
        ]);
        $captureDdl = false;

        expect($hostileExitCode)->not->toBe(0)
            ->and(Artisan::output())->toContain('Refusing lossy conversion')
            ->and($executedDdl)->toBe([])
            ->and(DB::table($hostileTable)->value('safe_value'))->toBe('unchanged')
            ->and(DB::table($hostileTable)->value('unsupported_date'))->toBe('not-a-date');

        $safeColumn = collect(Schema::getColumns($hostileTable))->firstWhere('name', 'safe_value');

        expect(strtolower($safeColumn['type'] ?? ''))->toContain(
            $driver === 'mysql' ? 'varchar(20)' : 'character varying(20)',
        );
    } finally {
        Schema::dropIfExists($hostileTable);
        Schema::dropIfExists($failureTable);
        Schema::dropIfExists($tableName);
        File::delete([$path, $failurePath, $hostilePath]);
        DB::disconnect($connection);
        config()->set('database.default', $originalDefault);
    }
})->with(['mysql', 'pgsql']);

test('every migration generator includes decimal precision and scale', function (string $generatorClass) {
    $generator = new $generatorClass(app(Filesystem::class));
    $method = new ReflectionMethod($generatorClass, 'generateColumn');

    $migration = $method->invoke($generator, [
        'slug' => 'amount',
        'type' => Number::class,
        'number_type' => 'decimal',
        'precision' => 18,
        'scale' => 6,
    ]);

    expect($migration)->toContain("\$table->decimal('amount', 18, 6)");
})->with([
    CreateResourceMigration::class,
    CreateDatabaseMigration::class,
    ModifyDatabaseMigration::class,
]);

test('resource editor migrations change an existing integer column to its decimal definition', function () {
    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $up = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $down = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $fields = collect([
        [
            'old' => [
                'slug' => 'amount',
                'type' => Number::class,
                'number_type' => 'integer',
            ],
            'new' => [
                'slug' => 'amount',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 19,
                'scale' => 2,
            ],
        ],
    ]);

    expect($up->invoke($generator, $fields, 'update'))
        ->toContain("\$table->decimal('amount', 19, 2)")
        ->toContain(')->nullable()->change()')
        ->and($down->invoke($generator, $fields, 'update'))
        ->toContain("\$table->integer('amount')->nullable()->change()");
});

test('generated resource editor migration code executes against sqlite', function () {
    $tableName = 'core_10_generated_migration_values';
    Schema::create($tableName, fn (Blueprint $table) => $table->id());

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
        'slug' => 'amount',
        'type' => Number::class,
        'number_type' => 'decimal',
        'precision' => 65,
        'scale' => 30,
    ]]);
    $up = $generate->invoke($generator, $fields, 'add');
    $down = $generateDown->invoke($generator, $fields, 'add');
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, $up, '', '', $down, '', '', '');
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-migration-');
    File::put($path, $content);
    $migration = require $path;
    $value = '12345678901234567890123456789012345.123456789012345678901234567890';

    try {
        expect($content)->toContain('\\Aura\\Base\\Schema\\AtomicSchemaUpdate::table(');

        $migration->up();
        DB::table($tableName)->insert(['amount' => $value]);

        expect(DB::table($tableName)->value('amount'))->toBe($value);

        $migration->down();

        expect(Schema::hasColumn($tableName, 'amount'))->toBeFalse();
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('generated decimal rollback refuses fractional rows before changing to integer', function () {
    $tableName = 'core_10_lossy_rollback_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->integer('amount')->nullable();
    });

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $generatePreflight = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownPreflight');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
        'old' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'integer',
        ],
        'new' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'decimal',
            'precision' => 19,
            'scale' => 2,
        ],
    ]]);
    $up = $generate->invoke($generator, $fields, 'update');
    $down = $generateDown->invoke($generator, $fields, 'update');
    $preflight = $generatePreflight->invoke($generator, $fields, 'update', $tableName);
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, '', $up, '', '', $down, '', $preflight);
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-rollback-');
    File::put($path, $content);
    $migration = require $path;

    try {
        $migration->up();
        DB::table($tableName)->insert(['amount' => '1.50']);

        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'lossy')
            ->and(DB::table($tableName)->value('amount'))->toBe('1.50')
            ->and(Schema::getColumnType($tableName, 'amount'))->toBe('text');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});

test('generated decimal rollback refuses exact integers outside database integer bounds', function () {
    $tableName = 'core_10_out_of_range_rollback_values';
    Schema::create($tableName, function (Blueprint $table) {
        $table->id();
        $table->integer('amount')->nullable();
    });

    $generator = new CreateDatabaseMigration(app(Filesystem::class));
    $generate = new ReflectionMethod(CreateDatabaseMigration::class, 'generateSchema');
    $generateDown = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownSchema');
    $generatePreflight = new ReflectionMethod(CreateDatabaseMigration::class, 'generateDownPreflight');
    $update = new ReflectionMethod(CreateDatabaseMigration::class, 'updateMigrationContent');
    $fields = collect([[
        'old' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'integer',
        ],
        'new' => [
            'slug' => 'amount',
            'type' => Number::class,
            'number_type' => 'decimal',
            'precision' => 65,
            'scale' => 2,
        ],
    ]]);
    $up = $generate->invoke($generator, $fields, 'update');
    $down = $generateDown->invoke($generator, $fields, 'update');
    $preflight = $generatePreflight->invoke($generator, $fields, 'update', $tableName);
    $template = str_replace('__TABLE__', $tableName, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table) {
            //
        });
    }
};
PHP);
    $content = $update->invoke($generator, $template, '', $up, '', '', $down, '', $preflight);
    $path = tempnam(sys_get_temp_dir(), 'aura-core10-bounds-');
    File::put($path, $content);
    $migration = require $path;

    try {
        $migration->up();
        $value = '1234567890123456789012345678901234567890.00';
        DB::table($tableName)->insert(['amount' => $value]);

        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'outside integer bounds')
            ->and(DB::table($tableName)->value('amount'))->toBe($value)
            ->and(Schema::getColumnType($tableName, 'amount'))->toBe('text');
    } finally {
        File::delete($path);
        Schema::dropIfExists($tableName);
    }
});
