<?php

use Aura\Base\BaseResource;
use Aura\Base\Services\EmbeddedComponentContextStore;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\MigrationOwnershipLedger;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class Core12ExternalGuardResource extends BaseResource
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'select';

    protected $table = 'core12 guarded-owners';

    public static function getFields(): array
    {
        return [];
    }
}

function core12ExternalGuardConnection(string $driver): array
{
    if ($driver === 'sqlite') {
        $database = sys_get_temp_dir().'/aura-core12-guard-'.getmypid().'.sqlite';

        if (! file_exists($database)) {
            touch($database);
        }

        return [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 100,
        ];
    }

    if (in_array($driver, ['mysql', 'mariadb'], true)) {
        $environmentPrefix = $driver === 'mariadb' ? 'MARIADB' : 'MYSQL';

        return [
            'driver' => $driver,
            'host' => getenv("AURA_TEST_{$environmentPrefix}_HOST") ?: '127.0.0.1',
            'port' => getenv("AURA_TEST_{$environmentPrefix}_PORT") ?: ($driver === 'mariadb' ? '3307' : '3306'),
            'database' => getenv("AURA_TEST_{$environmentPrefix}_DATABASE") ?: 'aura_core12_guard_test',
            'username' => getenv("AURA_TEST_{$environmentPrefix}_USERNAME") ?: 'root',
            'password' => getenv("AURA_TEST_{$environmentPrefix}_PASSWORD") ?: '',
            'unix_socket' => getenv("AURA_TEST_{$environmentPrefix}_SOCKET") ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ];
    }

    return [
        'driver' => 'pgsql',
        'host' => getenv('AURA_TEST_PGSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('AURA_TEST_PGSQL_PORT') ?: '5432',
        'database' => getenv('AURA_TEST_PGSQL_DATABASE') ?: 'aura_core12_guard_test',
        'username' => getenv('AURA_TEST_PGSQL_USERNAME') ?: 'postgres',
        'password' => getenv('AURA_TEST_PGSQL_PASSWORD') ?: '',
        'charset' => 'utf8',
        'prefix' => '',
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ];
}

function core12ExpectedMariaDbUuidType(Connection $connection): string
{
    return version_compare($connection->getServerVersion(), '10.7.0', '<') ? 'char(36)' : 'uuid';
}

/**
 * @param  list<string>  $barrierCheckpoints
 * @return list<array{ok: bool, error?: string, exit_code: int|null}>
 */
function core12RunConcurrentExternalMigrations(
    string $driver,
    string $migrationPath,
    array $barrierCheckpoints,
): array {
    if (! function_exists('pcntl_fork')) {
        throw new RuntimeException('The pcntl extension is required for migration race tests.');
    }

    $directory = sys_get_temp_dir().'/aura-core12-migration-race-'.getmypid().'-'.bin2hex(random_bytes(8));

    if (! mkdir($directory, 0700)) {
        throw new RuntimeException("Unable to create migration race directory [{$directory}].");
    }

    $pids = [];

    try {
        foreach ([0, 1] as $worker) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                throw new RuntimeException('Unable to fork a migration race worker.');
            }

            if ($pid === 0) {
                $resultPath = $directory."/result-{$worker}.json";
                $readyPath = $directory."/ready-{$worker}";
                $result = ['ok' => false];

                try {
                    $connectionName = "core12_race_{$driver}_{$worker}";
                    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
                    DB::purge($connectionName);
                    DB::setDefaultConnection($connectionName);
                    Schema::clearResolvedInstance('db.schema');
                    app()->forgetInstance(MigrationOwnershipLedger::class);
                    $waited = [];
                    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
                        static function (string $checkpoint) use (
                            &$waited,
                            $barrierCheckpoints,
                            $directory,
                            $readyPath,
                        ): void {
                            $barrier = array_search($checkpoint, $barrierCheckpoints, true);

                            if ($barrier === false || isset($waited[$barrier])) {
                                return;
                            }

                            $waited[$barrier] = true;
                            $barrierReadyPath = $readyPath."-{$barrier}";
                            $releasePath = $directory."/release-{$barrier}";
                            file_put_contents($barrierReadyPath, 'ready', LOCK_EX);
                            $deadline = microtime(true) + 10;

                            while (! file_exists($releasePath)) {
                                if (microtime(true) >= $deadline) {
                                    throw new RuntimeException('Timed out waiting for the migration race barrier.');
                                }

                                usleep(10000);
                            }
                        },
                    ));

                    $migration = require $migrationPath;
                    $migration->up();
                    $result = ['ok' => true];
                } catch (Throwable $exception) {
                    $result = [
                        'ok' => false,
                        'error' => $exception::class.': '.$exception->getMessage(),
                    ];
                }

                file_put_contents($resultPath, json_encode($result, JSON_THROW_ON_ERROR), LOCK_EX);
                exit($result['ok'] ? 0 : 1);
            }

            $pids[$worker] = $pid;
        }

        foreach (array_keys($barrierCheckpoints) as $barrier) {
            $deadline = microtime(true) + 10;

            while (true) {
                $readyWorkers = collect([0, 1])
                    ->filter(fn (int $worker): bool => file_exists($directory."/ready-{$worker}-{$barrier}"))
                    ->count();
                $failedEarly = collect([0, 1])
                    ->contains(fn (int $worker): bool => file_exists($directory."/result-{$worker}.json"));

                if ($readyWorkers === 2 || $failedEarly || microtime(true) >= $deadline) {
                    break;
                }

                usleep(10000);
            }

            touch($directory."/release-{$barrier}");
        }
        $statuses = [];

        foreach ($pids as $worker => $pid) {
            pcntl_waitpid($pid, $status);
            $statuses[$worker] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null;
        }

        return collect([0, 1])->map(function (int $worker) use ($directory, $statuses): array {
            $resultPath = $directory."/result-{$worker}.json";

            if (! file_exists($resultPath)) {
                return [
                    'ok' => false,
                    'error' => 'Migration race worker did not write a result.',
                    'exit_code' => $statuses[$worker] ?? null,
                ];
            }

            $result = json_decode(file_get_contents($resultPath), true, flags: JSON_THROW_ON_ERROR);

            return [...$result, 'exit_code' => $statuses[$worker] ?? null];
        })->all();
    } finally {
        foreach (array_keys($barrierCheckpoints) as $barrier) {
            $releasePath = $directory."/release-{$barrier}";

            if (! file_exists($releasePath)) {
                touch($releasePath);
            }
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if (! in_array($entry, ['.', '..'], true)) {
                unlink($directory.'/'.$entry);
            }
        }

        rmdir($directory);
    }
}

test('portable database guards install upgrade invalidate and preserve migration artifacts on rollback', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_'.$driver;
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $prototype = (new Core12ExternalGuardResource)->setConnection($connectionName);
    $guard = app(EmbeddedResourceIncarnationGuard::class);

    $schema->dropIfExists($prototype->getTable());
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists('aura_migration_ownership');

    try {
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->uuid('incarnation');
            $table->timestamps();
            $table->unique(
                ['resource_type', 'resource_key_hash'],
                'aura_embedded_incarnation_resource_unique',
            );
        });
        $connection->table(EmbeddedResourceIncarnationStore::TABLE)->insert([
            'resource_type' => 'LegacyResource',
            'resource_key_hash' => str_repeat('b', 64),
            'incarnation' => '00000000-0000-4000-8000-000000000002',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $upgrade = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
        $upgrade->up();
        $upgrade->up();

        if ($driver === 'mariadb') {
            $columns = collect($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE));

            expect($columns->firstWhere('name', 'id')['type'])->toBe('bigint(20) unsigned')
                ->and($columns->firstWhere('name', 'incarnation')['type'])->toBe(core12ExpectedMariaDbUuidType($connection))
                ->and($columns->firstWhere('name', 'created_at')['default'])->toBe('NULL');
        }

        expect($schema->hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_key_type',
            'resource_key',
            'version',
        ]))->toBeTrue()
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->where('resource_type', 'LegacyResource')
                ->where('resource_key_hash', str_repeat('b', 64))
                ->where('resource_key_type', 'legacy')
                ->where('resource_key', 'legacy:1')
                ->where('version', 1)
                ->count())->toBe(1)
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_lookup',
            ))->toBeTrue()
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_identity_unique',
            ))->toBeTrue();

        $upgrade->down();
        $upgrade->down();
        expect($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue()
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_lookup',
            ))->toBeTrue()
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_identity_unique',
            ))->toBeTrue();
        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        DB::purge($connectionName);
        Schema::clearResolvedInstance('db.schema');
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();

        expect($connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
            ->doesntExist())->toBeTrue();

        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $migration->up();
        expect($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE))->not->toBeEmpty();
        $schema->create($prototype->getTable(), function (Blueprint $table): void {
            $table->string('select')->primary();
            $table->string('title')->nullable();
        });

        $guard->install($prototype);
        $guard->install($prototype);
        expect($guard->isInstalled($prototype))->toBeTrue();

        if ($driver === 'sqlite') {
            $deleteTrigger = $connection->table('sqlite_master')
                ->where('type', 'trigger')
                ->where('tbl_name', $prototype->getTable())
                ->whereRaw("lower(sql) like '% after delete %'")
                ->value('name');
            $connection->unprepared('drop trigger "'.str_replace('"', '""', $deleteTrigger).'"');
            $connection->unprepared(sprintf(
                'create trigger "%s" before delete on "core12 guarded-owners" for each row begin select 1; end',
                str_replace('"', '""', $deleteTrigger),
            ));
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $deleteTrigger = $connection->table('information_schema.TRIGGERS')
                ->where('TRIGGER_SCHEMA', $connection->getDatabaseName())
                ->where('EVENT_OBJECT_TABLE', $prototype->getTable())
                ->where('EVENT_MANIPULATION', 'DELETE')
                ->value('TRIGGER_NAME');
            $connection->unprepared('drop trigger `'.str_replace('`', '``', $deleteTrigger).'`');
            $connection->unprepared(sprintf(
                'create trigger `%s` before delete on `core12 guarded-owners` for each row set @aura_core12_noop = 1',
                str_replace('`', '``', $deleteTrigger),
            ));
        } else {
            $trigger = $connection->selectOne(
                <<<'SQL'
                    select t.tgname as name, p.proname as function_name
                    from pg_catalog.pg_trigger t
                    join pg_catalog.pg_class c on c.oid = t.tgrelid
                    join pg_catalog.pg_proc p on p.oid = t.tgfoid
                    where not t.tgisinternal
                      and c.oid = pg_catalog.to_regclass(?)
                      and (t.tgtype & 8) = 8
                    SQL,
                ['"core12 guarded-owners"'],
            );
            $connection->unprepared('drop trigger "'.str_replace('"', '""', $trigger->name).'" on "core12 guarded-owners"');
            $connection->unprepared(sprintf(
                'create or replace function "%s"() returns trigger as $aura$ begin return OLD; end; $aura$ language plpgsql',
                str_replace('"', '""', $trigger->function_name),
            ));
            $connection->unprepared(sprintf(
                'create trigger "%s" before delete on "core12 guarded-owners" for each row execute function "%s"()',
                str_replace('"', '""', $trigger->name),
                str_replace('"', '""', $trigger->function_name),
            ));
        }

        expect($guard->isInstalled($prototype))->toBeFalse();
        $guard->install($prototype);
        expect($guard->isInstalled($prototype))->toBeTrue();

        $connection->table($prototype->getTable())->insert([
            'select' => 'portable-key',
            'title' => 'Original',
        ]);
        $resource = $prototype->newQuery()->findOrFail('portable-key');
        $incarnations = app(EmbeddedResourceIncarnationStore::class);
        $token = $incarnations->token($resource);
        $version = $incarnations->version($resource);
        $attributes = $resource->getRawOriginal();
        expect($connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->doesntExist())->toBeTrue()
            ->and($token)->not->toBeEmpty()
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->where('resource_type', Core12ExternalGuardResource::class)
                ->count())->toBe(1);

        $connection->table($prototype->getTable())->where('select', 'portable-key')->delete();
        $connection->table($prototype->getTable())->insert($attributes);
        $incarnations->flush();

        expect($incarnations->token($resource))->toBe($token)
            ->and($incarnations->version($resource))->toBeGreaterThan($version);

        $version = $incarnations->version($resource);
        $connection->table($prototype->getTable())
            ->where('select', 'portable-key')
            ->update(['select' => 'moved-key']);
        $connection->table($prototype->getTable())->insert($attributes);
        $incarnations->flush();

        expect($incarnations->version($resource))->toBeGreaterThan($version);

        $connection->table($prototype->getTable())->insert([
            'select' => 'destination-key',
            'title' => 'Destination',
        ]);
        $destination = $prototype->newQuery()->findOrFail('destination-key');
        $destinationVersion = $incarnations->version($destination);

        $guard->uninstall($prototype);
        $connection->table($prototype->getTable())->where('select', 'destination-key')->delete();
        $guard->install($prototype);
        $connection->table($prototype->getTable())->insert([
            'select' => 'source-key',
            'title' => 'Destination',
        ]);
        $connection->table($prototype->getTable())
            ->where('select', 'source-key')
            ->update(['select' => 'destination-key']);
        $incarnations->flush();

        expect($incarnations->version($destination))->toBeGreaterThan($destinationVersion);

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        expect(fn () => $connection->table($prototype->getTable())->where('select', 'portable-key')->delete())
            ->toThrow(QueryException::class)
            ->and($connection->table($prototype->getTable())->where('select', 'portable-key')->exists())
            ->toBeTrue();

        $guard->uninstall($prototype);
        $guard->uninstall($prototype);
        $schema->drop($prototype->getTable());
        $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
            ->delete();
        $migration->up();
        $migration->down();
        $migration->down();

        expect($schema->hasTable($prototype->getTable()))->toBeFalse()
            ->and($schema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
    } finally {
        if ($schema->hasTable($prototype->getTable())) {
            try {
                $guard->uninstall($prototype);
            } catch (Throwable) {
            }

            $schema->drop($prototype->getTable());
        }

        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');

        if ($driver === 'sqlite') {
            unlink(core12ExternalGuardConnection('sqlite')['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('postgres guard ignores same-named triggers on another owner table', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated PostgreSQL test database.');
    }

    $connectionName = 'core12_pgsql_foreign_trigger';
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection('pgsql')]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $prototype = (new Core12ExternalGuardResource)->setConnection($connectionName);
    $guard = app(EmbeddedResourceIncarnationGuard::class);
    $foreignTable = 'core12 foreign-owners';
    $foreignFunction = 'aura_core12_foreign_noop';

    $schema->dropIfExists($prototype->getTable());
    $schema->dropIfExists($foreignTable);
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
    $connection->unprepared('drop function if exists "'.$foreignFunction.'"()');

    try {
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $migration->up();
        $schema->create($prototype->getTable(), function (Blueprint $table): void {
            $table->string('select')->primary();
        });
        $schema->create($foreignTable, function (Blueprint $table): void {
            $table->string('select')->primary();
        });
        $guard->install($prototype);
        $deleteTrigger = (string) $connection->selectOne(
            <<<'SQL'
                select t.tgname as name
                from pg_catalog.pg_trigger t
                join pg_catalog.pg_class c on c.oid = t.tgrelid
                where not t.tgisinternal
                  and c.oid = pg_catalog.to_regclass(?)
                  and (t.tgtype & 8) = 8
                SQL,
            ['"core12 guarded-owners"'],
        )->name;
        $guard->uninstall($prototype);
        $connection->unprepared(
            'create function "'.$foreignFunction.'"() returns trigger as $aura$ begin return OLD; end; $aura$ language plpgsql',
        );
        $connection->unprepared(sprintf(
            'create trigger "%s" after delete on "core12 foreign-owners" for each row execute function "%s"()',
            str_replace('"', '""', $deleteTrigger),
            $foreignFunction,
        ));

        expect($guard->isInstalled($prototype))->toBeFalse();
        $guard->install($prototype);

        expect($guard->isInstalled($prototype))->toBeTrue()
            ->and($connection->selectOne(
                'select count(*) as aggregate from pg_catalog.pg_trigger where not tgisinternal and tgname = ?',
                [$deleteTrigger],
            )->aggregate)->toBe(2);
    } finally {
        if ($schema->hasTable($prototype->getTable())) {
            $guard->uninstall($prototype);
            $schema->drop($prototype->getTable());
        }

        $schema->dropIfExists($foreignTable);
        $connection->unprepared('drop function if exists "'.$foreignFunction.'"()');
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('first canonical prime locks the owner while a second connection replaces it', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_prime_'.$driver;
    $replacementConnectionName = $connectionName.'_replacement';
    $configuration = core12ExternalGuardConnection($driver);
    config([
        "database.connections.{$connectionName}" => $configuration,
        "database.connections.{$replacementConnectionName}" => $configuration,
    ]);
    DB::purge($connectionName);
    DB::purge($replacementConnectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $replacementConnection = DB::connection($replacementConnectionName);
    $schema = $connection->getSchemaBuilder();
    $prototype = (new Core12ExternalGuardResource)->setConnection($connectionName);
    $guard = app(EmbeddedResourceIncarnationGuard::class);
    $migration = null;

    $schema->dropIfExists($prototype->getTable());
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists('aura_migration_ownership');

    try {
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $migration->up();

        if ($driver === 'mariadb') {
            $columns = collect($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE));

            expect($columns->firstWhere('name', 'id')['type'])->toBe('bigint(20) unsigned')
                ->and($columns->firstWhere('name', 'incarnation')['type'])->toBe(core12ExpectedMariaDbUuidType($connection))
                ->and($columns->firstWhere('name', 'created_at')['default'])->toBe('NULL');
        }

        $schema->create($prototype->getTable(), function (Blueprint $table): void {
            $table->string('select')->primary();
            $table->string('title')->nullable();
        });
        $attributes = [
            'select' => 'first-prime-key',
            'title' => 'Byte-identical owner',
        ];
        $connection->table($prototype->getTable())->insert($attributes);
        $resource = $prototype->newQuery()->findOrFail('first-prime-key');
        $guard->install($prototype);

        match ($driver) {
            'sqlite' => $replacementConnection->statement('PRAGMA busy_timeout = 100'),
            'mysql', 'mariadb' => $replacementConnection->statement('SET SESSION innodb_lock_wait_timeout = 1'),
            'pgsql' => $replacementConnection->statement("SET lock_timeout = '250ms'"),
        };

        $attemptedDuringCanonicalRead = false;
        $replacementWasBlocked = false;
        $listenerEnabled = true;
        $replaceOwner = function () use ($replacementConnection, $prototype, $attributes): void {
            $replacementConnection->transaction(function () use ($replacementConnection, $prototype, $attributes): void {
                $replacementConnection->table($prototype->getTable())
                    ->where('select', 'first-prime-key')
                    ->delete();
                $replacementConnection->table($prototype->getTable())->insert($attributes);
            });
        };

        DB::listen(function (QueryExecuted $query) use (
            &$attemptedDuringCanonicalRead,
            &$listenerEnabled,
            &$replacementWasBlocked,
            $connectionName,
            $prototype,
            $replaceOwner,
        ): void {
            if (! $listenerEnabled
                || $attemptedDuringCanonicalRead
                || $query->connectionName !== $connectionName
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')
                || ! str_contains($query->sql, $prototype->getTable())
            ) {
                return;
            }

            $attemptedDuringCanonicalRead = true;

            try {
                $replaceOwner();
            } catch (QueryException) {
                $replacementWasBlocked = true;
            }
        });

        $canonical = app(EmbeddedComponentContextStore::class)->canonical($resource);
        $issuedVersion = app(EmbeddedResourceIncarnationStore::class)->version($canonical);
        $listenerEnabled = false;

        if ($replacementWasBlocked) {
            $replaceOwner();
        }

        app()->forgetScopedInstances();
        $replacement = $prototype->newQuery()->findOrFail('first-prime-key');
        $currentVersion = app(EmbeddedResourceIncarnationStore::class)->version($replacement);

        expect($attemptedDuringCanonicalRead)->toBeTrue()
            ->and($replacementWasBlocked)->toBeTrue()
            ->and($currentVersion)->toBeGreaterThan($issuedVersion);
    } finally {
        if ($schema->hasTable($prototype->getTable())) {
            try {
                $guard->uninstall($prototype);
            } catch (Throwable) {
            }

            $schema->drop($prototype->getTable());
        }

        if ($migration) {
            $migration->down();
        }
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        DB::disconnect($replacementConnectionName);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');

        if ($driver === 'sqlite' && file_exists($configuration['database'])) {
            unlink($configuration['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('portable migration ownership stays outside runtime rows and validates ordered indexes', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_migration_ownership_'.$driver;
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    try {
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $create->up();

        if ($driver === 'mariadb') {
            $columns = collect($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE));

            expect($columns->firstWhere('name', 'id')['type'])->toBe('bigint(20) unsigned')
                ->and($columns->firstWhere('name', 'incarnation')['type'])->toBe(core12ExpectedMariaDbUuidType($connection))
                ->and($columns->firstWhere('name', 'created_at')['default'])->toBe('NULL');
        }

        expect($connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->doesntExist())->toBeTrue()
            ->and(collect($schema->getColumnListing(EmbeddedResourceIncarnationStore::TABLE))
                ->contains(fn (string $column): bool => str_contains($column, 'aura_migration_owned_')))->toBeFalse();

        $generation = app(MigrationOwnershipLedger::class)->readCreate()['generation'];
        $markerColumn = MigrationOwnershipLedger::markerColumn($generation);
        $schema->table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($markerColumn): void {
            $table->char($markerColumn, 32)->nullable();
        });
        $connection->table(EmbeddedResourceIncarnationStore::TABLE)->insert([
            'resource_type' => MigrationOwnershipLedger::MARKER_RESOURCE_TYPE,
            'resource_key_hash' => hash('sha256', $generation),
            'resource_key_type' => 'internal',
            'resource_key' => $generation,
            'incarnation' => (string) Str::uuid(),
            'version' => 1,
            $markerColumn => $generation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $create->up();

        expect($connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_key', $generation)
            ->where($markerColumn, $generation)
            ->exists())->toBeTrue()
            ->and($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, $markerColumn))->toBeTrue();

        $schema->table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->dropUnique('aura_embedded_incarnation_resource_unique');
            $table->unique(
                ['resource_key_hash', 'resource_type'],
                'aura_embedded_incarnation_resource_unique',
            );
        });

        expect(fn () => $create->up())->toThrow(RuntimeException::class);
    } finally {
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');

        if ($driver === 'sqlite' && file_exists(core12ExternalGuardConnection('sqlite')['database'])) {
            unlink(core12ExternalGuardConnection('sqlite')['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('mariadb stale create requires canonical UUID storage and rejects hostile grammar rebindings without mutation', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MariaDB test database.');
    }

    $connectionName = 'core12_mariadb_uuid_capability';
    $configuration = core12ExternalGuardConnection('mariadb');
    config(["database.connections.{$connectionName}" => $configuration]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $capabilityTable = 'aura_core12_uuid_capability_probe';
    $schema->dropIfExists($capabilityTable);
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    $createTable = function (int $incarnationLength) use ($schema): void {
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($incarnationLength): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->string('resource_key_type', 16);
            $table->string('resource_key', 191);
            $table->char('incarnation', $incarnationLength);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });
    };
    $claimCreate = function (string $generation) use ($connection): void {
        $connection->table(MigrationOwnershipLedger::TABLE)->insert([
            'migration' => MigrationOwnershipLedger::CREATE_KEY,
            'ownership' => json_encode([
                'version' => 2,
                'migration' => MigrationOwnershipLedger::CREATE_KEY,
                'state' => 'creating',
                'payload' => [
                    'created_table' => true,
                    'owns_registry' => false,
                    'generation' => $generation,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
    };
    $insertRow = function (string $incarnation) use ($connection): void {
        $connection->table(EmbeddedResourceIncarnationStore::TABLE)->insert([
            'resource_type' => 'UuidCapabilityResource',
            'resource_key_hash' => str_repeat('a', 64),
            'resource_key_type' => 'string',
            'resource_key' => 'preserve-me',
            'incarnation' => $incarnation,
            'version' => 1,
            'created_at' => null,
            'updated_at' => null,
        ]);
    };
    $assertRejectedWithoutMutation = function () use ($connection, $schema): void {
        $columns = $schema->getColumns(EmbeddedResourceIncarnationStore::TABLE);
        $indexes = $schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE);
        $rows = $connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->orderBy('id')
            ->get()
            ->map(static fn (stdClass $row): array => (array) $row)
            ->all();
        $ownership = $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
            ->value('ownership');
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

        expect(fn () => $migration->up())->toThrow(RuntimeException::class)
            ->and($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE))->toBe($columns)
            ->and($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE))->toBe($indexes)
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->orderBy('id')
                ->get()
                ->map(static fn (stdClass $row): array => (array) $row)
                ->all())->toBe($rows)
            ->and($connection->table(MigrationOwnershipLedger::TABLE)
                ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
                ->value('ownership'))->toBe($ownership)
            ->and(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('creating');
    };

    try {
        $schema->create($capabilityTable, function (Blueprint $table): void {
            $table->uuid('incarnation');
        });
        $grammarUuidType = collect($schema->getColumns($capabilityTable))
            ->firstWhere('name', 'incarnation')['type'];
        $schema->drop($capabilityTable);

        expect($connection->getServerVersion())->toMatch('/\A\d+\.\d+\.\d+\z/D')
            ->and($grammarUuidType)->toBeIn(['uuid', 'char(36)']);

        $schema->create(MigrationOwnershipLedger::TABLE, function (Blueprint $table): void {
            $table->string('migration')->primary();
            $table->longText('ownership');
        });
        $createTable(36);
        $insertRow('00000000-0000-4000-8000-000000000091');
        $claimCreate(str_repeat('d', 32));

        if ($grammarUuidType === 'uuid') {
            $assertRejectedWithoutMutation();
        } else {
            $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
            $migration->up();

            expect(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('owned')
                ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                    ->where('resource_key', 'preserve-me')
                    ->value('incarnation'))->toBe('00000000-0000-4000-8000-000000000091');
        }

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $connection->table(MigrationOwnershipLedger::TABLE)->delete();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $createTable(36);
        $insertRow('00000000-0000-4000-8000-000000000092');
        $claimCreate(str_repeat('f', 32));
        $originalGrammar = $connection->getSchemaGrammar();
        $connection->setSchemaGrammar(new class($connection) extends MariaDbGrammar
        {
            protected function typeUuid(Fluent $column): string
            {
                return 'char(36)';
            }
        });

        try {
            $assertRejectedWithoutMutation();
        } finally {
            $connection->setSchemaGrammar($originalGrammar);
        }

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $connection->table(MigrationOwnershipLedger::TABLE)->delete();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $createTable(36);
        $insertRow('00000000-0000-4000-8000-000000000093');
        $claimCreate(str_repeat('b', 32));
        $reboundConnectionName = 'core12_mariadb_uuid_rebound';
        config(["database.connections.{$reboundConnectionName}" => $configuration]);
        DB::purge($reboundConnectionName);
        $reboundConnection = DB::connection($reboundConnectionName);
        $reboundConnection->getSchemaBuilder();
        $connection->setSchemaGrammar(new MariaDbGrammar($reboundConnection));

        try {
            $assertRejectedWithoutMutation();
        } finally {
            $connection->setSchemaGrammar($originalGrammar);
            DB::disconnect($reboundConnectionName);
        }

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $connection->table(MigrationOwnershipLedger::TABLE)->delete();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $createTable(35);
        $insertRow(str_repeat('a', 35));
        $claimCreate(str_repeat('e', 32));

        $assertRejectedWithoutMutation();
    } finally {
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $schema->dropIfExists($capabilityTable);
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('mariadb canonical capability preserves configured table prefixes', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MariaDB test database.');
    }

    $connectionName = 'core12_mariadb_prefixed_capability';
    $configuration = core12ExternalGuardConnection('mariadb');
    $configuration['prefix'] = 'core12_prefix_';
    config(["database.connections.{$connectionName}" => $configuration]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $schema = DB::connection($connectionName)->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    try {
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $create->up();

        expect($schema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
            ->and($schema->hasTable(MigrationOwnershipLedger::TABLE))->toBeTrue()
            ->and(collect($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE))
                ->firstWhere('name', 'incarnation')['type'])
            ->toBe(core12ExpectedMariaDbUuidType(DB::connection($connectionName)));
    } finally {
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('mariadb proxied connection preflight fails closed before creating artifacts', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MariaDB test database.');
    }

    $sourceConnectionName = 'core12_mariadb_proxy_source';
    $proxyConnectionName = 'core12_mariadb_proxy';
    $configuration = core12ExternalGuardConnection('mariadb');
    config([
        "database.connections.{$sourceConnectionName}" => $configuration,
        "database.connections.{$proxyConnectionName}" => $configuration,
    ]);
    DB::purge($sourceConnectionName);
    DB::purge($proxyConnectionName);
    $sourceConnection = DB::connection($sourceConnectionName);
    DB::extend($proxyConnectionName, static function (array $config) use ($sourceConnection): MariaDbConnection {
        return new class($sourceConnection->getPdo(), $config['database'], $config['prefix'], $config) extends MariaDbConnection {};
    });
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($sourceConnectionName);
    Schema::clearResolvedInstance('db.schema');
    $sourceSchema = $sourceConnection->getSchemaBuilder();
    $sourceSchema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $sourceSchema->dropIfExists(MigrationOwnershipLedger::TABLE);
    DB::setDefaultConnection($proxyConnectionName);
    Schema::clearResolvedInstance('db.schema');
    $proxySchema = DB::connection($proxyConnectionName)->getSchemaBuilder();

    try {
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

        expect(fn () => $create->up())
            ->toThrow(RuntimeException::class, 'trusted framework connection metadata')
            ->and($proxySchema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse()
            ->and($proxySchema->hasTable(MigrationOwnershipLedger::TABLE))->toBeFalse();
    } finally {
        DB::setDefaultConnection($sourceConnectionName);
        Schema::clearResolvedInstance('db.schema');
        $sourceSchema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $sourceSchema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($proxyConnectionName);
        DB::disconnect($sourceConnectionName);
        DB::forgetExtension($proxyConnectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('mariadb hostile grammar preflight rejects create and upgrade before any mutation', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MariaDB test database.');
    }

    $connectionName = 'core12_mariadb_hostile_preflight';
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection('mariadb')]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
    $originalGrammar = $connection->getSchemaGrammar();
    $hostileGrammar = new class($connection) extends MariaDbGrammar
    {
        protected function typeUuid(Fluent $column): string
        {
            return 'char(36)';
        }
    };

    try {
        $connection->setSchemaGrammar($hostileGrammar);
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

        expect(fn () => $create->up())
            ->toThrow(RuntimeException::class, 'trusted framework connection metadata')
            ->and($schema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse()
            ->and($schema->hasTable(MigrationOwnershipLedger::TABLE))->toBeFalse();

        $connection->setSchemaGrammar($originalGrammar);
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->char('incarnation', 36);
            $table->timestamps();
            $table->unique(
                ['resource_type', 'resource_key_hash'],
                'aura_embedded_incarnation_resource_unique',
            );
        });
        $connection->table(EmbeddedResourceIncarnationStore::TABLE)->insert([
            'resource_type' => 'HostilePreflightResource',
            'resource_key_hash' => str_repeat('c', 64),
            'incarnation' => '00000000-0000-4000-8000-000000000094',
            'created_at' => null,
            'updated_at' => null,
        ]);
        $columns = $schema->getColumns(EmbeddedResourceIncarnationStore::TABLE);
        $indexes = $schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE);
        $rows = $connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->get()
            ->map(static fn (stdClass $row): array => (array) $row)
            ->all();
        $connection->setSchemaGrammar($hostileGrammar);
        $upgrade = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

        expect(fn () => $upgrade->up())
            ->toThrow(RuntimeException::class, 'trusted framework connection metadata')
            ->and($schema->hasTable(MigrationOwnershipLedger::TABLE))->toBeFalse()
            ->and($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE))->toBe($columns)
            ->and($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE))->toBe($indexes)
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->get()
                ->map(static fn (stdClass $row): array => (array) $row)
                ->all())->toBe($rows);
    } finally {
        $connection->setSchemaGrammar($originalGrammar);
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('portable migrations fail closed on malformed stale claimed schemas', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_malformed_claim_'.$driver;
    $configuration = core12ExternalGuardConnection($driver);
    config(["database.connections.{$connectionName}" => $configuration]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    try {
        $schema->create(MigrationOwnershipLedger::TABLE, function (Blueprint $table): void {
            $table->string('migration')->primary();
            $table->longText('ownership');
        });
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($driver): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->string('resource_key_type', 16);

            if ($driver === 'sqlite') {
                $table->text('resource_key');
            } else {
                $table->string('resource_key', 190);
            }

            $table->uuid('incarnation');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
        });
        $connection->table(MigrationOwnershipLedger::TABLE)->insert([
            'migration' => MigrationOwnershipLedger::CREATE_KEY,
            'ownership' => json_encode([
                'version' => 2,
                'migration' => MigrationOwnershipLedger::CREATE_KEY,
                'state' => 'creating',
                'payload' => [
                    'created_table' => true,
                    'owns_registry' => false,
                    'generation' => str_repeat('a', 32),
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

        expect(fn () => $create->up())->toThrow(RuntimeException::class, 'unexpected definition')
            ->and(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('creating');

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
            ->delete();
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->string('resource_key_type', 16);
            $table->string('resource_key', 191);
            $table->uuid('incarnation');
            $table->unsignedBigInteger('version')->nullable()->default(2);
            $table->timestamps();
            $table->unique(['resource_type', 'resource_key_hash'], 'aura_embedded_incarnation_resource_unique');
            $table->index(
                ['resource_type', 'resource_key_type', 'resource_key'],
                'aura_embedded_incarnation_guard_lookup',
            );
            $table->unique(
                ['resource_type', 'resource_key_type', 'resource_key'],
                'aura_embedded_incarnation_guard_identity_unique',
            );
        });
        $connection->table(MigrationOwnershipLedger::TABLE)->insert([
            'migration' => MigrationOwnershipLedger::UPGRADE_KEY,
            'ownership' => json_encode([
                'version' => 2,
                'migration' => MigrationOwnershipLedger::UPGRADE_KEY,
                'state' => 'upgrading',
                'payload' => [
                    'added_columns' => ['version'],
                    'created_indexes' => [],
                    'owns_registry' => false,
                    'generation' => str_repeat('b', 32),
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $upgrade = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

        expect(fn () => $upgrade->up())->toThrow(RuntimeException::class, 'unexpected definition')
            ->and(app(MigrationOwnershipLedger::class)->readUpgrade()['state'])->toBe('upgrading');

        if ($driver === 'mariadb') {
            $malformedDefinitions = [
                'non-canonical integer display width' => [
                    'MODIFY `id` BIGINT(19) UNSIGNED NOT NULL AUTO_INCREMENT',
                ],
                'wrong integer type' => [
                    'MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                ],
                'signed primary key' => [
                    'MODIFY `id` BIGINT(20) NOT NULL AUTO_INCREMENT',
                ],
                'non-auto-incrementing primary key' => [
                    'MODIFY `id` BIGINT(20) UNSIGNED NOT NULL',
                ],
                'non-primary auto-incrementing key' => [
                    'MODIFY `id` BIGINT(20) UNSIGNED NOT NULL',
                    'DROP PRIMARY KEY, ADD UNIQUE KEY `aura_incarnation_id_unique` (`id`)',
                    'MODIFY `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                ],
                'nullable required column' => [
                    'MODIFY `resource_type` VARCHAR(255) NULL',
                ],
                'wrong non-null default' => [
                    'MODIFY `version` BIGINT(20) UNSIGNED NOT NULL DEFAULT 2',
                ],
                'wrong UUID length' => [
                    'MODIFY `incarnation` CHAR(35) NOT NULL',
                ],
                'unrelated UUID representation' => [
                    'MODIFY `incarnation` VARCHAR(36) NOT NULL',
                ],
                'non-null timestamp default' => [
                    'MODIFY `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
                ],
            ];

            foreach ($malformedDefinitions as $malformation => $alterDefinitions) {
                $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
                $connection->table(MigrationOwnershipLedger::TABLE)->delete();
                $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
                    $table->id();
                    $table->string('resource_type');
                    $table->char('resource_key_hash', 64);
                    $table->string('resource_key_type', 16);
                    $table->string('resource_key', 191);
                    $table->uuid('incarnation');
                    $table->unsignedBigInteger('version')->default(1);
                    $table->timestamps();
                });

                foreach ($alterDefinitions as $alterDefinition) {
                    $connection->statement(
                        'ALTER TABLE `'.EmbeddedResourceIncarnationStore::TABLE.'` '.$alterDefinition,
                    );
                }

                $connection->table(MigrationOwnershipLedger::TABLE)->insert([
                    'migration' => MigrationOwnershipLedger::CREATE_KEY,
                    'ownership' => json_encode([
                        'version' => 2,
                        'migration' => MigrationOwnershipLedger::CREATE_KEY,
                        'state' => 'creating',
                        'payload' => [
                            'created_table' => true,
                            'owns_registry' => false,
                            'generation' => str_repeat('c', 32),
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]);
                app()->forgetInstance(MigrationOwnershipLedger::class);

                try {
                    $create->up();
                    $this->fail("MariaDB accepted the malformed schema: {$malformation}.");
                } catch (RuntimeException $exception) {
                    expect($exception->getMessage())->toContain('unexpected definition')
                        ->and(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('creating');
                }
            }
        }
    } finally {
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');

        if ($driver === 'sqlite' && file_exists($configuration['database'])) {
            unlink($configuration['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('portable migrations reject duplicate-capable registries and resume after a DDL crash', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_migration_resume_'.$driver;
    $configuration = core12ExternalGuardConnection($driver);
    config(["database.connections.{$connectionName}" => $configuration]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    try {
        $schema->create(MigrationOwnershipLedger::TABLE, function (Blueprint $table): void {
            $table->string('migration');
            $table->longText('ownership');
            $table->string('claim_group')->nullable();
            $table->unique('claim_group', 'aura_migration_ownership_migration_unique');
        });

        expect(fn () => app(MigrationOwnershipLedger::class)->registryExists())
            ->toThrow(RuntimeException::class, 'invalid schema');

        $schema->drop(MigrationOwnershipLedger::TABLE);
        app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
            static function (string $checkpoint): void {
                if ($checkpoint === 'create.base_table_created') {
                    throw new RuntimeException('simulated native DDL crash');
                }
            },
        ));
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

        expect(fn () => $migration->up())
            ->toThrow(RuntimeException::class, 'simulated native DDL crash');

        app()->forgetInstance(MigrationOwnershipLedger::class);
        $migration->up();
        $indexes = collect($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE));

        if ($driver === 'mariadb') {
            $columns = collect($schema->getColumns(EmbeddedResourceIncarnationStore::TABLE));

            expect($columns->firstWhere('name', 'id')['type'])->toBe('bigint(20) unsigned')
                ->and($columns->firstWhere('name', 'incarnation')['type'])->toBe(core12ExpectedMariaDbUuidType($connection))
                ->and($columns->firstWhere('name', 'created_at')['default'])->toBe('NULL');
        }

        expect(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('owned')
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_resource_unique')['columns'])
            ->toBe(['resource_type', 'resource_key_hash'])
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_lookup')['columns'])
            ->toBe(['resource_type', 'resource_key_type', 'resource_key'])
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_identity_unique')['unique'])
            ->toBeTrue();
    } finally {
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');

        if ($driver === 'sqlite' && file_exists($configuration['database'])) {
            unlink($configuration['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('mysql concurrent create migrations converge on every DDL artifact', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MySQL test database.');
    }

    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The pcntl extension is required for native migration race tests.');
    }

    $driver = 'mysql';
    $connectionName = 'core12_create_race_mysql';
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $schema = DB::connection($connectionName)->getSchemaBuilder();
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
    DB::purge($connectionName);
    Schema::clearResolvedInstance('db.schema');

    try {
        $results = core12RunConcurrentExternalMigrations(
            $driver,
            dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub',
            ['create.ownership_started'],
        );

        DB::setDefaultConnection($connectionName);
        DB::purge($connectionName);
        Schema::clearResolvedInstance('db.schema');
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $indexes = collect($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE));

        expect(collect($results)->pluck('ok')->all())->toBe([true, true])
            ->and(collect($results)->pluck('exit_code')->all())->toBe([0, 0])
            ->and(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('owned')
            ->and($connection->table(MigrationOwnershipLedger::TABLE)
                ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
                ->count())->toBe(1)
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_resource_unique')['columns'])
            ->toBe(['resource_type', 'resource_key_hash'])
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_lookup')['columns'])
            ->toBe(['resource_type', 'resource_key_type', 'resource_key'])
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_identity_unique')['unique'])
            ->toBeTrue();
    } finally {
        app()->forgetInstance(MigrationOwnershipLedger::class);
        DB::setDefaultConnection($connectionName);
        DB::purge($connectionName);
        Schema::clearResolvedInstance('db.schema');
        $schema = DB::connection($connectionName)->getSchemaBuilder();
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');

test('mysql concurrent upgrade migrations converge on every claimed artifact', function (): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with a dedicated MySQL test database.');
    }

    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The pcntl extension is required for native migration race tests.');
    }

    $driver = 'mysql';
    $connectionName = 'core12_upgrade_race_mysql';
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    Schema::clearResolvedInstance('db.schema');
    $schema = DB::connection($connectionName)->getSchemaBuilder();
    $legacyGeneration = str_repeat('c', 32);
    $legacyMarkerColumn = MigrationOwnershipLedger::markerColumn($legacyGeneration);
    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
    $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($legacyMarkerColumn): void {
        $table->id();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->uuid('incarnation');
        $table->char($legacyMarkerColumn, 32)->nullable();
        $table->timestamps();
        $table->unique(
            ['resource_type', 'resource_key_hash'],
            'aura_embedded_incarnation_resource_unique',
        );
    });
    DB::connection($connectionName)->table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => MigrationOwnershipLedger::MARKER_RESOURCE_TYPE,
        'resource_key_hash' => hash('sha256', $legacyGeneration),
        'incarnation' => substr($legacyGeneration, 0, 8)
            .'-'.substr($legacyGeneration, 8, 4)
            .'-'.substr($legacyGeneration, 12, 4)
            .'-'.substr($legacyGeneration, 16, 4)
            .'-'.substr($legacyGeneration, 20, 12),
        $legacyMarkerColumn => $legacyGeneration,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::purge($connectionName);
    Schema::clearResolvedInstance('db.schema');

    try {
        $results = core12RunConcurrentExternalMigrations(
            $driver,
            dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub',
            ['upgrade.ownership_started', 'upgrade.legacy_rows_isolated'],
        );

        DB::setDefaultConnection($connectionName);
        DB::purge($connectionName);
        Schema::clearResolvedInstance('db.schema');
        $connection = DB::connection($connectionName);
        $schema = $connection->getSchemaBuilder();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $indexes = collect($schema->getIndexes(EmbeddedResourceIncarnationStore::TABLE));

        expect(collect($results)->pluck('ok')->all())->toBe([true, true])
            ->and(collect($results)->pluck('exit_code')->all())->toBe([0, 0])
            ->and(app(MigrationOwnershipLedger::class)->readUpgrade()['state'])->toBe('owned')
            ->and($connection->table(MigrationOwnershipLedger::TABLE)
                ->where('migration', MigrationOwnershipLedger::UPGRADE_KEY)
                ->count())->toBe(1)
            ->and($schema->hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
                'resource_key_type',
                'resource_key',
                'version',
            ]))->toBeTrue()
            ->and($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, $legacyMarkerColumn))->toBeTrue()
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->where('resource_key_hash', hash('sha256', $legacyGeneration))
                ->where('resource_key_type', 'legacy')
                ->exists())->toBeTrue()
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_lookup')['columns'])
            ->toBe(['resource_type', 'resource_key_type', 'resource_key'])
            ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_identity_unique')['unique'])
            ->toBeTrue();
    } finally {
        app()->forgetInstance(MigrationOwnershipLedger::class);
        DB::setDefaultConnection($connectionName);
        DB::purge($connectionName);
        Schema::clearResolvedInstance('db.schema');
        $schema = DB::connection($connectionName)->getSchemaBuilder();
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);
        Schema::clearResolvedInstance('db.schema');
    }
})->group('database-guards');
