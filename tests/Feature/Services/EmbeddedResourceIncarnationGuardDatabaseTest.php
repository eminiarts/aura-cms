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

test('portable database guards install upgrade invalidate and roll back', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_'.$driver;
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
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
        expect($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeFalse()
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_lookup',
            ))->toBeFalse()
            ->and($schema->hasIndex(
                EmbeddedResourceIncarnationStore::TABLE,
                'aura_embedded_incarnation_guard_identity_unique',
            ))->toBeFalse();
        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);

        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $migration->up();
        $migration->up();
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
        $migration->up();
        $migration->down();
        $migration->down();

        expect($schema->hasTable($prototype->getTable()))->toBeFalse()
            ->and($schema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse();
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
        } else {
            $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        }
        DB::disconnect($replacementConnectionName);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);

        if ($driver === 'sqlite' && file_exists($configuration['database'])) {
            unlink($configuration['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');

test('portable migration generation markers reject host-recreated targets', function (string $driver): void {
    if (getenv('AURA_TEST_DATABASE_GUARDS') !== '1') {
        $this->markTestSkipped('Set AURA_TEST_DATABASE_GUARDS=1 with dedicated MySQL, MariaDB, and PostgreSQL test databases.');
    }

    $connectionName = 'core12_marker_'.$driver;
    config(["database.connections.{$connectionName}" => core12ExternalGuardConnection($driver)]);
    DB::purge($connectionName);
    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connectionName);
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();
    $createCompleteTable = static function () use ($schema): void {
        $schema->create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->id();
            $table->string('resource_type');
            $table->char('resource_key_hash', 64);
            $table->string('resource_key_type', 16);
            $table->string('resource_key', 191);
            $table->uuid('incarnation');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
            $table->unique(
                ['resource_type', 'resource_key_hash'],
                'aura_embedded_incarnation_resource_unique',
            );
            $table->index(
                ['resource_type', 'resource_key_type', 'resource_key'],
                'aura_embedded_incarnation_guard_lookup',
            );
            $table->unique(
                ['resource_type', 'resource_key_type', 'resource_key'],
                'aura_embedded_incarnation_guard_identity_unique',
            );
        });
    };
    $createLegacyTable = static function () use ($schema): void {
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
    };
    $assertMarkerFailures = static function (object $migration, string $ownershipKey) use ($connection, $schema): void {
        $ownership = $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', $ownershipKey)
            ->value('ownership');
        $record = json_decode($ownership, true, flags: JSON_THROW_ON_ERROR);
        $generation = $record['payload']['generation'];
        $foreignGeneration = $generation === str_repeat('b', 32)
            ? str_repeat('c', 32)
            : str_repeat('b', 32);

        $connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->update(['resource_key' => 'tampered']);
        expect(fn () => $migration->down())->toThrow(RuntimeException::class);
        $connection->table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->update(['resource_key' => $generation]);

        $schema->table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($foreignGeneration): void {
            $table->char(MigrationOwnershipLedger::markerColumn($foreignGeneration), 32)->nullable();
        });
        expect(fn () => $migration->down())->toThrow(RuntimeException::class);
        $schema->table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($foreignGeneration): void {
            $table->dropColumn(MigrationOwnershipLedger::markerColumn($foreignGeneration));
        });

        $record['payload']['generation'] = $foreignGeneration;
        $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', $ownershipKey)
            ->update(['ownership' => json_encode($record, JSON_THROW_ON_ERROR)]);
        expect(fn () => $migration->down())->toThrow(RuntimeException::class);
        $record['payload']['generation'] = $generation;
        $connection->table(MigrationOwnershipLedger::TABLE)
            ->where('migration', $ownershipKey)
            ->update(['ownership' => json_encode($record, JSON_THROW_ON_ERROR)]);
    };

    $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
    $schema->dropIfExists(MigrationOwnershipLedger::TABLE);

    try {
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $create->up();
        $assertMarkerFailures($create, MigrationOwnershipLedger::CREATE_KEY);
        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $createCompleteTable();

        expect(fn () => $create->up())->toThrow(RuntimeException::class)
            ->and(fn () => $create->down())->toThrow(RuntimeException::class)
            ->and($schema->hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();

        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $schema->drop(MigrationOwnershipLedger::TABLE);
        $createLegacyTable();
        $upgrade = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
        $upgrade->up();
        $assertMarkerFailures($upgrade, MigrationOwnershipLedger::UPGRADE_KEY);
        $schema->drop(EmbeddedResourceIncarnationStore::TABLE);
        $createCompleteTable();

        expect(fn () => $upgrade->up())->toThrow(RuntimeException::class)
            ->and(fn () => $upgrade->down())->toThrow(RuntimeException::class)
            ->and($schema->hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
    } finally {
        $schema->dropIfExists(EmbeddedResourceIncarnationStore::TABLE);
        $schema->dropIfExists(MigrationOwnershipLedger::TABLE);
        DB::disconnect($connectionName);
        DB::setDefaultConnection($originalConnection);

        if ($driver === 'sqlite' && file_exists(core12ExternalGuardConnection('sqlite')['database'])) {
            unlink(core12ExternalGuardConnection('sqlite')['database']);
        }
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');
