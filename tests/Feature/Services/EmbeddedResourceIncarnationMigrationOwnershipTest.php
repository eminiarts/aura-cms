<?php

use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\MigrationOwnershipLedger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CORE12_STATE_CREATE_KEY = 'create_embedded_resource_incarnations';
const CORE12_STATE_UPGRADE_KEY = 'upgrade_embedded_resource_incarnations';
const CORE12_STATE_OWNERSHIP_TABLE = 'aura_migration_ownership';

beforeEach(function (): void {
    Schema::dropIfExists(EmbeddedResourceIncarnationStore::TABLE);

    if (Schema::hasTable(CORE12_STATE_OWNERSHIP_TABLE)) {
        DB::table(CORE12_STATE_OWNERSHIP_TABLE)
            ->whereIn('migration', [CORE12_STATE_CREATE_KEY, CORE12_STATE_UPGRADE_KEY])
            ->delete();
    }

    app()->forgetInstance(MigrationOwnershipLedger::class);
});

afterEach(function (): void {
    app()->forgetInstance(MigrationOwnershipLedger::class);
});

function core12StateCreateCompleteTable(): void
{
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
            ['resource_type', 'resource_key_type', 'resource_key'],
            'aura_embedded_incarnation_guard_lookup',
        );
        $table->unique(
            ['resource_type', 'resource_key_type', 'resource_key'],
            'aura_embedded_incarnation_guard_identity_unique',
        );
    });
}

function core12StateCreateLegacyTable(): void
{
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->uuid('incarnation');
        $table->timestamps();
        $table->unique(['resource_type', 'resource_key_hash'], 'aura_embedded_incarnation_resource_unique');
    });
}

function core12StateCreateBaseTable(): void
{
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->string('resource_key_type', 16);
        $table->string('resource_key', 191);
        $table->uuid('incarnation');
        $table->unsignedBigInteger('version')->default(1);
        $table->timestamps();
    });
}

function core12StateWriteRecord(string $migration, string $ownership): void
{
    DB::table(CORE12_STATE_OWNERSHIP_TABLE)->updateOrInsert(
        ['migration' => $migration],
        ['ownership' => $ownership],
    );
}

function core12StateCurrentRecord(string $migration, string $state, array $payload): string
{
    $payload['generation'] ??= str_repeat('a', 32);

    return json_encode([
        'version' => 2,
        'migration' => $migration,
        'state' => $state,
        'payload' => $payload,
    ], JSON_THROW_ON_ERROR);
}

it('preserves an exactly recreated host table carrying copied create proof', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);
    expect($record['version'])->toBe(2);
    $generation = $record['payload']['generation'];
    $markerColumn = MigrationOwnershipLedger::markerColumn($generation);
    $marker = (array) DB::table(EmbeddedResourceIncarnationStore::TABLE)
        ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
        ->first();

    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($markerColumn): void {
        $table->char($markerColumn, 32)->nullable();
        $table->string('host_data')->nullable();
    });
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert($marker);
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'HostResource',
        'resource_key_hash' => str_repeat('c', 64),
        'resource_key_type' => 'string',
        'resource_key' => 'host-key',
        'incarnation' => '00000000-0000-4000-8000-000000000003',
        'version' => 1,
        'host_data' => 'preserve-me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->down();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->where('host_data', 'preserve-me')->exists())->toBeTrue();
});

it('preserves exactly recreated host upgrade artifacts carrying copied proof', function (): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    $migration->up();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);
    expect($record['version'])->toBe(2);
    $generation = $record['payload']['generation'];
    $markerColumn = MigrationOwnershipLedger::markerColumn($generation);
    $marker = (array) DB::table(EmbeddedResourceIncarnationStore::TABLE)
        ->where('resource_type', MigrationOwnershipLedger::MARKER_RESOURCE_TYPE)
        ->first();

    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table) use ($markerColumn): void {
        $table->char($markerColumn, 32)->nullable();
        $table->string('host_data')->nullable();
    });
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert($marker);
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'HostResource',
        'resource_key_hash' => str_repeat('d', 64),
        'resource_key_type' => 'string',
        'resource_key' => 'host-key',
        'incarnation' => '00000000-0000-4000-8000-000000000004',
        'version' => 7,
        'host_data' => 'preserve-me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue()
        ->and(Schema::hasIndex(EmbeddedResourceIncarnationStore::TABLE, 'aura_embedded_incarnation_guard_lookup'))->toBeTrue()
        ->and(Schema::hasIndex(EmbeddedResourceIncarnationStore::TABLE, 'aura_embedded_incarnation_guard_identity_unique', 'unique'))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->where('host_data', 'preserve-me')->exists())->toBeTrue();
});

it('does not expose migration ownership proof writers as public application APIs', function (): void {
    $ledger = new ReflectionClass(MigrationOwnershipLedger::class);

    expect(collect([
        'delete',
        'deleteTargetMarker',
        'ensureRegistry',
        'newGeneration',
        'writeCreate',
        'writeTargetMarker',
        'writeUpgrade',
    ])->filter(fn (string $method): bool => $ledger->hasMethod($method) && $ledger->getMethod($method)->isPublic()))
        ->toBeEmpty();
});

it('accepts committed create ownership only when a recreated table has the exact schema', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
});

it('keeps completed ownership monotonic when its target is missing', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unexpected schema');
    core12StateCreateCompleteTable();

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
});

it('preserves create artifacts when legacy generation proof is foreign or the ledger is wrong', function (string $failure): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    if ($failure === 'wrong ledger token') {
        $record['payload']['generation'] = str_repeat('f', 32);
        core12StateWriteRecord(CORE12_STATE_CREATE_KEY, json_encode($record, JSON_THROW_ON_ERROR));
    } else {
        Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->char(MigrationOwnershipLedger::markerColumn(str_repeat('b', 32)), 32)->nullable();
        });
    }

    $migration->down();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
})->with(['copied foreign marker', 'wrong ledger token']);

it('accepts committed upgrade ownership only when recreated artifacts have exact definitions', function (): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    $migration->up();
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();

    $migration->up();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
});

it('preserves upgrade artifacts when legacy generation proof is foreign or the ledger is wrong', function (string $failure): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    $migration->up();
    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    if ($failure === 'wrong ledger token') {
        $record['payload']['generation'] = str_repeat('f', 32);
        core12StateWriteRecord(CORE12_STATE_UPGRADE_KEY, json_encode($record, JSON_THROW_ON_ERROR));
    } else {
        Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->char(MigrationOwnershipLedger::markerColumn(str_repeat('b', 32)), 32)->nullable();
        });
    }

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
})->with(['copied foreign marker', 'wrong ledger token']);

it('rejects stale create ownership before every early return and preserves a host table', function (): void {
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_marker')->nullable();
    });
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'HostResource',
        'resource_key_hash' => str_repeat('a', 64),
        'resource_key_type' => 'string',
        'resource_key' => 'host-key',
        'incarnation' => '00000000-0000-4000-8000-000000000001',
        'version' => 1,
        'host_marker' => 'preserve-me',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    core12StateWriteRecord(CORE12_STATE_CREATE_KEY, json_encode([
        'created_table' => true,
        'owns_registry' => false,
    ], JSON_THROW_ON_ERROR));
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class);
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->value('host_marker'))->toBe('preserve-me');
});

it('rejects a null ownership value instead of treating its row as absent', function (): void {
    Schema::drop(CORE12_STATE_OWNERSHIP_TABLE);
    Schema::create(CORE12_STATE_OWNERSHIP_TABLE, function (Blueprint $table): void {
        $table->string('migration')->primary();
        $table->longText('ownership')->nullable();
    });
    DB::table(CORE12_STATE_OWNERSHIP_TABLE)->insert([
        'migration' => CORE12_STATE_CREATE_KEY,
        'ownership' => null,
    ]);
    core12StateCreateCompleteTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class);
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
});

it('rejects duplicate-capable ownership registry schemas', function (bool $hasWrongUniqueIndex): void {
    Schema::drop(CORE12_STATE_OWNERSHIP_TABLE);
    Schema::create(CORE12_STATE_OWNERSHIP_TABLE, function (Blueprint $table) use ($hasWrongUniqueIndex): void {
        $table->string('migration');
        $table->longText('ownership');
        $table->string('claim_group')->nullable();

        if ($hasWrongUniqueIndex) {
            $table->unique('claim_group', 'aura_migration_ownership_migration_unique');
        }
    });

    try {
        expect(fn () => app(MigrationOwnershipLedger::class)->registryExists())
            ->toThrow(RuntimeException::class, 'invalid schema');
    } finally {
        Schema::drop(CORE12_STATE_OWNERSHIP_TABLE);
        Schema::create(CORE12_STATE_OWNERSHIP_TABLE, function (Blueprint $table): void {
            $table->string('migration')->primary();
            $table->longText('ownership');
        });
    }
})->with([
    'no unique claim index' => [false],
    'same-purpose name on wrong column' => [true],
]);

it('rejects invalid upgrade ownership before missing or incomplete target early returns', function (string $ownership): void {
    core12StateCreateLegacyTable();
    core12StateWriteRecord(CORE12_STATE_UPGRADE_KEY, $ownership);
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(Exception::class);
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeFalse();
})->with([
    'invalid json' => '{invalid-json',
    'legacy valid json' => json_encode([
        'added_columns' => ['version'],
        'created_indexes' => [],
        'owns_registry' => false,
    ], JSON_THROW_ON_ERROR),
    'wrong version' => json_encode([
        'version' => 3,
        'migration' => CORE12_STATE_UPGRADE_KEY,
        'state' => 'owned',
        'payload' => [
            'added_columns' => ['version'],
            'created_indexes' => [],
            'owns_registry' => false,
            'generation' => str_repeat('a', 32),
        ],
    ], JSON_THROW_ON_ERROR),
    'wrong state type' => json_encode([
        'version' => 2,
        'migration' => CORE12_STATE_UPGRADE_KEY,
        'state' => true,
        'payload' => [
            'added_columns' => ['version'],
            'created_indexes' => [],
            'owns_registry' => false,
            'generation' => str_repeat('a', 32),
        ],
    ], JSON_THROW_ON_ERROR),
    'wrong payload type' => core12StateCurrentRecord(CORE12_STATE_UPGRADE_KEY, 'owned', [
        'added_columns' => 'version',
        'created_indexes' => [],
        'owns_registry' => false,
    ]),
]);

it('resumes interrupted forward create but rejects historical rollback states', function (string $state, bool $resumes): void {
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_recreated_marker')->nullable();
    });
    core12StateWriteRecord(CORE12_STATE_CREATE_KEY, core12StateCurrentRecord(
        CORE12_STATE_CREATE_KEY,
        $state,
        ['created_table' => true, 'owns_registry' => false],
    ));
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    if ($resumes) {
        $migration->up();
    } else {
        expect(fn () => $migration->up())->toThrow(RuntimeException::class);
    }
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
})->with([
    'creating' => ['creating', true],
    'table drop started' => ['table_drop_started', false],
    'registry drop started' => ['registry_drop_started', false],
]);

it('reconciles an interrupted create that exposes only the base table', function (): void {
    core12StateCreateBaseTable();
    core12StateWriteRecord(CORE12_STATE_CREATE_KEY, core12StateCurrentRecord(
        CORE12_STATE_CREATE_KEY,
        'creating',
        ['created_table' => true, 'owns_registry' => false],
    ));
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    $migration->up();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);
    $indexes = collect(Schema::getIndexes(EmbeddedResourceIncarnationStore::TABLE));

    expect($record['state'])->toBe('owned')
        ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_resource_unique')['columns'])
        ->toBe(['resource_type', 'resource_key_hash'])
        ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_lookup')['columns'])
        ->toBe(['resource_type', 'resource_key_type', 'resource_key'])
        ->and($indexes->firstWhere('name', 'aura_embedded_incarnation_guard_identity_unique')['columns'])
        ->toBe(['resource_type', 'resource_key_type', 'resource_key']);
});

it('fails closed on a stale create ledger with malformed column definitions', function (): void {
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('id')->primary();
        $table->string('resource_type');
        $table->char('resource_key_hash', 64);
        $table->string('resource_key_type', 16);
        $table->string('resource_key', 191);
        $table->uuid('incarnation');
        $table->unsignedBigInteger('version')->default(1);
        $table->timestamps();
    });
    core12StateWriteRecord(CORE12_STATE_CREATE_KEY, core12StateCurrentRecord(
        CORE12_STATE_CREATE_KEY,
        'creating',
        ['created_table' => true, 'owns_registry' => false],
    ));
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unexpected definition');

    expect(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('creating');
});

it('fails closed on a stale upgrade ledger with a malformed claimed column', function (): void {
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
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
    core12StateWriteRecord(CORE12_STATE_UPGRADE_KEY, core12StateCurrentRecord(
        CORE12_STATE_UPGRADE_KEY,
        'upgrading',
        [
            'added_columns' => ['version'],
            'created_indexes' => [],
            'owns_registry' => false,
        ],
    ));
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'unexpected definition');

    expect(app(MigrationOwnershipLedger::class)->readUpgrade()['state'])->toBe('upgrading');
});

it('resumes create after a crash at every DDL artifact boundary', function (
    string $checkpoint,
    int $expectedSecondaryIndexes,
): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated artifact crash');
            }
        },
    ));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'simulated artifact crash');

    $secondaryIndexes = collect(Schema::getIndexes(EmbeddedResourceIncarnationStore::TABLE))
        ->reject(fn (array $index): bool => (bool) $index['primary']);
    expect($secondaryIndexes)->toHaveCount($expectedSecondaryIndexes);

    app()->forgetInstance(MigrationOwnershipLedger::class);
    $migration->up();

    expect(app(MigrationOwnershipLedger::class)->readCreate()['state'])->toBe('owned');
})->with([
    'base table' => ['create.base_table_created', 0],
    'resource hash index' => ['create.index.aura_embedded_incarnation_resource_unique', 1],
    'lookup index' => ['create.index.aura_embedded_incarnation_guard_lookup', 2],
    'identity index' => ['create.index.aura_embedded_incarnation_guard_identity_unique', 3],
]);

it('resumes upgrade after a crash at every owned artifact boundary', function (string $checkpoint): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated artifact crash');
            }
        },
    ));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'simulated artifact crash');

    app()->forgetInstance(MigrationOwnershipLedger::class);
    $migration->up();

    expect(app(MigrationOwnershipLedger::class)->readUpgrade()['state'])->toBe('owned')
        ->and(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_key_type',
            'resource_key',
            'version',
        ]))->toBeTrue()
        ->and(Schema::hasIndex(EmbeddedResourceIncarnationStore::TABLE, 'aura_embedded_incarnation_guard_lookup'))->toBeTrue()
        ->and(Schema::hasIndex(EmbeddedResourceIncarnationStore::TABLE, 'aura_embedded_incarnation_guard_identity_unique'))->toBeTrue();
})->with([
    'resource key type column' => ['upgrade.column.resource_key_type'],
    'resource key column' => ['upgrade.column.resource_key'],
    'version column' => ['upgrade.column.version'],
    'lookup index' => ['upgrade.index.aura_embedded_incarnation_guard_lookup'],
    'identity index' => ['upgrade.index.aura_embedded_incarnation_guard_identity_unique'],
]);

it('never enters a create rollback transition or invokes destructive checkpoints', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $checkpoint): void {
            if ($checkpoint === 'create.rollback_started') {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    $migration->down();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned');

    app()->forgetInstance(MigrationOwnershipLedger::class);
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_recreated_marker')->nullable();
    });

    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
});

it('never enters an upgrade rollback transition or invokes destructive checkpoints', function (): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $checkpoint): void {
            if ($checkpoint === 'upgrade.rollback_started') {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned');

    app()->forgetInstance(MigrationOwnershipLedger::class);
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
});

it('records every create up crash phase without granting stale destructive ownership', function (
    string $checkpoint,
    ?string $expectedState,
    bool $tableExists,
): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'simulated crash')
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBe($tableExists);

    $record = DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership');

    if ($expectedState === null) {
        expect($record)->toBeNull();
    } else {
        expect(json_decode($record, true, flags: JSON_THROW_ON_ERROR)['state'])->toBe($expectedState);
    }

    app()->forgetInstance(MigrationOwnershipLedger::class);

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
})->with([
    'registry ready' => ['create.registry_ready', null, false],
    'ownership started' => ['create.ownership_started', 'creating', false],
    'table created' => ['create.table_created', 'creating', true],
    'ownership committed' => ['create.ownership_committed', 'owned', true],
]);

it('records every upgrade up crash phase and never infers ownership from schema', function (
    string $checkpoint,
    ?string $expectedState,
): void {
    core12StateCreateLegacyTable();
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'LegacyResource',
        'resource_key_hash' => str_repeat('b', 64),
        'incarnation' => '00000000-0000-4000-8000-000000000002',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'simulated crash');

    $record = DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership');

    if ($expectedState === null) {
        expect($record)->toBeNull();
    } else {
        expect(json_decode($record, true, flags: JSON_THROW_ON_ERROR)['state'])->toBe($expectedState);
    }

    app()->forgetInstance(MigrationOwnershipLedger::class);

    $migration->up();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)
            ->where('resource_key_hash', str_repeat('b', 64))
            ->exists())->toBeTrue();
})->with([
    'registry ready' => ['upgrade.registry_ready', null],
    'ownership started' => ['upgrade.ownership_started', 'upgrading'],
    'columns added' => ['upgrade.columns_added', 'upgrading'],
    'legacy rows isolated' => ['upgrade.legacy_rows_isolated', 'upgrading'],
    'indexes created' => ['upgrade.indexes_created', 'upgrading'],
    'ownership committed' => ['upgrade.ownership_committed', 'owned'],
]);

it('does not invoke former destructive create down crash phases', function (string $checkpoint): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    $migration->down();
    $migration->down();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned')
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();

    app()->forgetInstance(MigrationOwnershipLedger::class);

    if (Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE)) {
        Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    }

    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_recreated_marker')->nullable();
    });

    $migration->up();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
})->with([
    'rollback started' => ['create.rollback_started'],
    'table dropped' => ['create.table_dropped'],
]);

it('does not invoke former destructive upgrade down crash phases', function (
    string $checkpoint,
): void {
    core12StateCreateLegacyTable();
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    $migration->down();
    $migration->down();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned')
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
    app()->forgetInstance(MigrationOwnershipLedger::class);

    if (! Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version')) {
        Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('version')->default(1);
            $table->string('host_recreated_marker')->nullable();
        });
    }

    $migration->up();
    $migration->down();

    expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
})->with([
    'rollback started' => ['upgrade.rollback_started'],
    'marker deleted' => ['upgrade.marker_deleted'],
    'indexes dropped' => ['upgrade.indexes_dropped'],
    'columns dropped' => ['upgrade.columns_dropped'],
]);

it('never drops the ownership registry during rollback', function (string $migrationType): void {
    if ($migrationType === 'create') {
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $migration->up();
        $ownershipKey = CORE12_STATE_CREATE_KEY;
        $checkpoint = 'create.registry_drop_started';
    } else {
        core12StateCreateLegacyTable();
        $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
        $migration->up();
        $ownershipKey = CORE12_STATE_UPGRADE_KEY;
        $checkpoint = 'upgrade.registry_drop_started';
    }

    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    $migration->down();
    $migration->down();

    expect(Schema::hasTable(CORE12_STATE_OWNERSHIP_TABLE))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', $ownershipKey)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('owned');

    app()->forgetInstance(MigrationOwnershipLedger::class);

    $migration->up();
    $migration->down();

    expect(Schema::hasTable(CORE12_STATE_OWNERSHIP_TABLE))->toBeTrue()
        ->and(DB::table(CORE12_STATE_OWNERSHIP_TABLE)->where('migration', $ownershipKey)->exists())->toBeTrue();
})->with(['create', 'upgrade']);
