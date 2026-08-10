<?php

use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\MigrationOwnershipLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CORE12_CREATE_OWNERSHIP_KEY = 'create_embedded_resource_incarnations';
const CORE12_UPGRADE_OWNERSHIP_KEY = 'upgrade_embedded_resource_incarnations';
const CORE12_OWNERSHIP_TABLE = 'aura_migration_ownership';

class Core12IncarnationQueryProbe extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $table = 'aura_embedded_resource_incarnations';
}

class Core12IncarnationOwnerProbe extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';

    protected $table = 'core12_incarnation_owner_probes';

    public function incarnations(): HasMany
    {
        return $this->hasMany(Core12IncarnationQueryProbe::class, 'resource_key', 'id');
    }
}

beforeEach(function (): void {
    Schema::dropIfExists('core12_incarnation_owner_probes');
    Schema::dropIfExists(EmbeddedResourceIncarnationStore::TABLE);

    if (Schema::hasTable(CORE12_OWNERSHIP_TABLE)) {
        DB::table(CORE12_OWNERSHIP_TABLE)
            ->whereIn('migration', [CORE12_CREATE_OWNERSHIP_KEY, CORE12_UPGRADE_OWNERSHIP_KEY])
            ->delete();
    }
});

function core12CreateEmbeddedIncarnationTable(bool $includeIdentityIndex = true): void
{
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($includeIdentityIndex): void {
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

        if ($includeIdentityIndex) {
            $table->unique(
                ['resource_type', 'resource_key_type', 'resource_key'],
                'aura_embedded_incarnation_guard_identity_unique',
            );
        }
    });
}

it('records the incarnation table and preserves it during rollback', function () {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->up();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_type',
            'resource_key_hash',
            'resource_key_type',
            'resource_key',
            'incarnation',
            'version',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_identity_unique',
        ))->toBeTrue()
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
            ->exists())->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
            ->exists())->toBeTrue();
});

it('keeps migration ownership proof out of the runtime incarnation table', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    $migration->up();

    Schema::create('core12_incarnation_owner_probes', function (Blueprint $table): void {
        $table->string('id')->primary();
    });
    $owner = Core12IncarnationOwnerProbe::create(['id' => str_repeat('a', 32)]);
    Core12IncarnationQueryProbe::create([
        'resource_type' => Core12IncarnationOwnerProbe::class,
        'resource_key_hash' => hash('sha256', $owner->getKey()),
        'resource_key_type' => 'string',
        'resource_key' => $owner->getKey(),
        'incarnation' => '00000000-0000-4000-8000-000000000099',
        'version' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(collect(Schema::getColumnListing(EmbeddedResourceIncarnationStore::TABLE))
        ->filter(fn (string $column): bool => str_starts_with($column, 'aura_migration_owned_')))
        ->toBeEmpty()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
            ->exists())->toBeFalse()
        ->and(Core12IncarnationQueryProbe::query()->count())->toBe(1)
        ->and($owner->incarnations()->count())->toBe(1)
        ->and($owner->incarnations()->firstOrFail()->resource_key)->toBe(str_repeat('a', 32));
});

it('removes legacy marker rows without dropping a marker column that contains host data', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    $record = json_decode(DB::table(CORE12_OWNERSHIP_TABLE)
        ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);
    $markerColumn = MigrationOwnershipLedger::markerColumn($record['payload']['generation']);

    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($markerColumn): void {
        $table->char($markerColumn, 32)->nullable();
    });
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        [
            'resource_type' => MigrationOwnershipLedger::MARKER_RESOURCE_TYPE,
            'resource_key_hash' => str_repeat('a', 64),
            'resource_key_type' => 'string',
            'resource_key' => $record['payload']['generation'],
            'incarnation' => '00000000-0000-4000-8000-000000000090',
            'version' => 1,
            $markerColumn => $record['payload']['generation'],
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'resource_type' => 'HostResource',
            'resource_key_hash' => str_repeat('b', 64),
            'resource_key_type' => 'string',
            'resource_key' => 'host-key',
            'incarnation' => '00000000-0000-4000-8000-000000000091',
            'version' => 1,
            $markerColumn => 'host-data',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $migration->up();

    expect(DB::table(EmbeddedResourceIncarnationStore::TABLE)
        ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
        ->doesntExist())->toBeTrue()
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, $markerColumn))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_type', 'HostResource')
            ->value($markerColumn))->toBe('host-data');
});

it('resumes an interrupted create after the table was created', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $checkpoint): void {
            if ($checkpoint === 'create.table_created') {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'simulated crash');
    app()->forgetInstance(MigrationOwnershipLedger::class);

    $migration->up();

    $record = json_decode(DB::table(CORE12_OWNERSHIP_TABLE)
        ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned');
});

it('does not let a concurrent create overwrite completed ownership', function (): void {
    $database = tempnam(sys_get_temp_dir(), 'aura-core12-concurrent-');
    $originalConnection = DB::getDefaultConnection();
    $connection = [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];
    config()->set('database.connections.core12_first', $connection);
    config()->set('database.connections.core12_second', $connection);
    DB::purge('core12_first');
    DB::purge('core12_second');

    try {
        DB::setDefaultConnection('core12_first');
        $first = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $second = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $ranSecond = false;

        app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
            function (string $checkpoint) use ($second, &$ranSecond): void {
                if ($checkpoint !== 'create.registry_ready' || $ranSecond) {
                    return;
                }

                $ranSecond = true;
                DB::setDefaultConnection('core12_second');
                app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger);
                $second->up();
                DB::setDefaultConnection('core12_first');
            },
        ));

        $first->up();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $first->up();

        $record = json_decode(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
            ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

        expect($record['state'])->toBe('owned')
            ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
    } finally {
        DB::disconnect('core12_first');
        DB::disconnect('core12_second');
        DB::setDefaultConnection($originalConnection);
        @unlink($database);
    }
});

it('converges when upgrade observes a concurrent create', function (): void {
    $database = tempnam(sys_get_temp_dir(), 'aura-core12-create-upgrade-');
    $originalConnection = DB::getDefaultConnection();
    $connection = [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];
    config()->set('database.connections.core12_create', $connection);
    config()->set('database.connections.core12_upgrade', $connection);
    DB::purge('core12_create');
    DB::purge('core12_upgrade');

    try {
        DB::setDefaultConnection('core12_create');
        $create = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $upgrade = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
        $ranUpgrade = false;

        app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
            function (string $checkpoint) use ($upgrade, &$ranUpgrade): void {
                if ($checkpoint !== 'create.table_created' || $ranUpgrade) {
                    return;
                }

                $ranUpgrade = true;
                DB::setDefaultConnection('core12_upgrade');
                app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger);
                $upgrade->up();
                DB::setDefaultConnection('core12_create');
            },
        ));

        $create->up();
        app()->forgetInstance(MigrationOwnershipLedger::class);
        $upgrade->up();

        expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
            ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->count())->toBe(0);
    } finally {
        DB::disconnect('core12_create');
        DB::disconnect('core12_upgrade');
        DB::setDefaultConnection($originalConnection);
        @unlink($database);
    }
});

it('rejects a named lookup index with the wrong ordered columns', function (): void {
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->string('resource_key_type', 16);
        $table->string('resource_key', 191);
        $table->uuid('incarnation');
        $table->unsignedBigInteger('version')->default(1);
        $table->timestamps();
        $table->unique(['resource_type', 'resource_key_hash'], 'aura_embedded_incarnation_resource_unique');
        $table->index(
            ['resource_key', 'resource_key_type', 'resource_type'],
            'aura_embedded_incarnation_guard_lookup',
        );
    });
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unexpected definition');
});

it('makes create migration up and down a no-op for a host-owned table', function () {
    core12CreateEmbeddedIncarnationTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_marker')->nullable();
    });
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'HostResource',
        'resource_key_hash' => str_repeat('a', 64),
        'resource_key_type' => 'string',
        'resource_key' => 'host-key',
        'incarnation' => '00000000-0000-4000-8000-000000000001',
        'version' => 7,
        'host_marker' => 'preserve-me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_marker'))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->value('host_marker'))->toBe('preserve-me')
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
            ->exists())->toBeFalse();
});

it('fails visibly instead of accepting corrupt create migration ownership', function () {
    DB::table(CORE12_OWNERSHIP_TABLE)->updateOrInsert(
        ['migration' => CORE12_CREATE_OWNERSHIP_KEY],
        ['ownership' => '{invalid-json'],
    );
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(JsonException::class);

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse();
});

it('upgrades the table and preserves all artifacts during rollback', function () {
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->string('resource_key_type', 16)->default('string');
        $table->string('resource_key', 191)->default('');
        $table->uuid('incarnation');
        $table->timestamps();
        $table->unique(
            ['resource_type', 'resource_key_hash'],
            'aura_embedded_incarnation_resource_unique',
        );
        $table->index(
            ['resource_type', 'resource_key_type', 'resource_key'],
            'aura_embedded_incarnation_guard_lookup',
        );
    });
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->up();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_identity_unique',
        ))->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
        'resource_key_type',
        'resource_key',
    ]))->toBeTrue()
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_identity_unique',
        ))->toBeTrue();
});

it('makes a fully pre-existing upgrade schema roundtrip a no-op', function () {
    core12CreateEmbeddedIncarnationTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->down();

    expect(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
        'resource_key_type',
        'resource_key',
        'version',
    ]))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_identity_unique',
        ))->toBeTrue()
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_UPGRADE_OWNERSHIP_KEY)
            ->exists())->toBeFalse();
});

it('makes an upgrade against a missing table a rollback-safe no-op', function () {
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse()
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_UPGRADE_OWNERSHIP_KEY)
            ->exists())->toBeFalse();
});
