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

function core12StateWriteRecord(string $migration, string $ownership): void
{
    DB::table(CORE12_STATE_OWNERSHIP_TABLE)->updateOrInsert(
        ['migration' => $migration],
        ['ownership' => $ownership],
    );
}

function core12StateCurrentRecord(string $migration, string $state, array $payload): string
{
    return json_encode([
        'version' => 1,
        'migration' => $migration,
        'state' => $state,
        'payload' => $payload,
    ], JSON_THROW_ON_ERROR);
}

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

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
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

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();
});

it('rejects invalid upgrade ownership before missing or incomplete target early returns', function (string $ownership): void {
    core12StateCreateLegacyTable();
    core12StateWriteRecord(CORE12_STATE_UPGRADE_KEY, $ownership);
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    expect(fn () => $migration->up())->toThrow(Exception::class)
        ->and(fn () => $migration->down())->toThrow(Exception::class)
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeFalse();
})->with([
    'invalid json' => '{invalid-json',
    'legacy valid json' => json_encode([
        'added_columns' => ['version'],
        'created_indexes' => [],
        'owns_registry' => false,
    ], JSON_THROW_ON_ERROR),
    'wrong version' => json_encode([
        'version' => 2,
        'migration' => CORE12_STATE_UPGRADE_KEY,
        'state' => 'owned',
        'payload' => [
            'added_columns' => ['version'],
            'created_indexes' => [],
            'owns_registry' => false,
        ],
    ], JSON_THROW_ON_ERROR),
    'wrong state type' => json_encode([
        'version' => 1,
        'migration' => CORE12_STATE_UPGRADE_KEY,
        'state' => true,
        'payload' => [
            'added_columns' => ['version'],
            'created_indexes' => [],
            'owns_registry' => false,
        ],
    ], JSON_THROW_ON_ERROR),
    'wrong payload type' => core12StateCurrentRecord(CORE12_STATE_UPGRADE_KEY, 'owned', [
        'added_columns' => 'version',
        'created_indexes' => [],
        'owns_registry' => false,
    ]),
]);

it('treats interrupted create rollback records as permanently non-droppable', function (string $state): void {
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

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue()
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
})->with(['creating', 'table_drop_started', 'registry_drop_started']);

it('persists a non-droppable create transition before table rollback DDL', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $checkpoint): void {
            if ($checkpoint === 'create.rollback_started') {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'simulated crash')
        ->and(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('table_drop_started');

    app()->forgetInstance(MigrationOwnershipLedger::class);
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_recreated_marker')->nullable();
    });

    expect(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
});

it('persists a non-droppable upgrade transition before rollback DDL', function (): void {
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

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'simulated crash')
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('rollback_started');

    app()->forgetInstance(MigrationOwnershipLedger::class);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
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

    if ($expectedState === null || $expectedState === 'owned') {
        $migration->up();
        $migration->down();

        expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse();
    } else {
        expect(fn () => $migration->up())->toThrow(RuntimeException::class)
            ->and(fn () => $migration->down())->toThrow(RuntimeException::class);
    }
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

    if ($expectedState === null || $expectedState === 'owned') {
        $migration->up();
        $migration->down();

        expect(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeFalse();
    } else {
        expect(fn () => $migration->up())->toThrow(RuntimeException::class)
            ->and(fn () => $migration->down())->toThrow(RuntimeException::class);
    }
})->with([
    'registry ready' => ['upgrade.registry_ready', null],
    'ownership started' => ['upgrade.ownership_started', 'upgrading'],
    'columns added' => ['upgrade.columns_added', 'upgrading'],
    'rows cleared' => ['upgrade.rows_cleared', 'upgrading'],
    'indexes created' => ['upgrade.indexes_created', 'upgrading'],
    'ownership committed' => ['upgrade.ownership_committed', 'owned'],
]);

it('keeps a host-recreated table safe after every destructive create down crash phase', function (
    string $checkpoint,
    string $expectedState,
): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
    $migration->up();
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'simulated crash');

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_CREATE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe($expectedState);

    app()->forgetInstance(MigrationOwnershipLedger::class);

    if (Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE)) {
        Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    }

    core12StateCreateCompleteTable();
    Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
        $table->string('host_recreated_marker')->nullable();
    });

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'host_recreated_marker'))->toBeTrue();
})->with([
    'rollback started' => ['create.rollback_started', 'table_drop_started'],
    'table dropped' => ['create.table_dropped', 'table_drop_started'],
]);

it('keeps recreated upgrade artifacts safe after every destructive down crash phase', function (
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

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'simulated crash');

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', CORE12_STATE_UPGRADE_KEY)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('rollback_started');
    app()->forgetInstance(MigrationOwnershipLedger::class);

    if (! Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version')) {
        Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('version')->default(1);
            $table->string('host_recreated_marker')->nullable();
        });
    }

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeTrue();
})->with([
    'rollback started' => ['upgrade.rollback_started'],
    'indexes dropped' => ['upgrade.indexes_dropped'],
    'columns dropped' => ['upgrade.columns_dropped'],
]);

it('persists registry ownership separately before dropping the registry', function (string $migrationType): void {
    if ($migrationType === 'create') {
        core12StateCreateCompleteTable();
        $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
        $ownershipKey = CORE12_STATE_CREATE_KEY;
        $checkpoint = 'create.registry_drop_started';
        $ownership = core12StateCurrentRecord($ownershipKey, 'owned', [
            'created_table' => true,
            'owns_registry' => true,
        ]);
    } else {
        core12StateCreateLegacyTable();
        $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';
        $migration->up();
        $ownershipKey = CORE12_STATE_UPGRADE_KEY;
        $checkpoint = 'upgrade.registry_drop_started';
        $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
            ->where('migration', $ownershipKey)
            ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);
        $record['payload']['owns_registry'] = true;
        $ownership = json_encode($record, JSON_THROW_ON_ERROR);
    }

    DB::table(CORE12_STATE_OWNERSHIP_TABLE)->delete();
    core12StateWriteRecord($ownershipKey, $ownership);
    app()->instance(MigrationOwnershipLedger::class, new MigrationOwnershipLedger(
        static function (string $actualCheckpoint) use ($checkpoint): void {
            if ($actualCheckpoint === $checkpoint) {
                throw new RuntimeException('simulated crash');
            }
        },
    ));

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'simulated crash')
        ->and(Schema::hasTable(CORE12_STATE_OWNERSHIP_TABLE))->toBeTrue();

    $record = json_decode(DB::table(CORE12_STATE_OWNERSHIP_TABLE)
        ->where('migration', $ownershipKey)
        ->value('ownership'), true, flags: JSON_THROW_ON_ERROR);

    expect($record['state'])->toBe('registry_drop_started');

    app()->forgetInstance(MigrationOwnershipLedger::class);

    if ($migrationType === 'create') {
        core12StateCreateCompleteTable();
    } else {
        Schema::table(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('version')->default(1);
        });
    }

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable(CORE12_STATE_OWNERSHIP_TABLE))->toBeTrue();
})->with(['create', 'upgrade']);
