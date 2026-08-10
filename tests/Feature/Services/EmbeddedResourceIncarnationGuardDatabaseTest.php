<?php

use Aura\Base\BaseResource;
use Aura\Base\Services\EmbeddedComponentContextStore;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\MigrationOwnershipLedger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

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

        expect($schema->hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_key_type',
            'resource_key',
            'version',
        ]))->toBeTrue()
            ->and($connection->table(EmbeddedResourceIncarnationStore::TABLE)
                ->where('resource_type', '!=', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
                ->count())->toBe(0)
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
        expect($connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->doesntExist())->toBeTrue()
            ->and(collect($schema->getColumnListing(EmbeddedResourceIncarnationStore::TABLE))
                ->contains(fn (string $column): bool => str_contains($column, 'aura_migration_owned_')))->toBeFalse();

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
    $schema = DB::connection($connectionName)->getSchemaBuilder();
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
            ['upgrade.ownership_started', 'upgrade.legacy_marker_cleanup_started'],
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
            ->and($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, $legacyMarkerColumn))->toBeFalse()
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
