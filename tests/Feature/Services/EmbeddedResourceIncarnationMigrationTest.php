<?php

use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CORE12_CREATE_OWNERSHIP_KEY = 'create_embedded_resource_incarnations';
const CORE12_UPGRADE_OWNERSHIP_KEY = 'upgrade_embedded_resource_incarnations';
const CORE12_OWNERSHIP_TABLE = 'aura_migration_ownership';

beforeEach(function (): void {
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

it('records and rolls back only the incarnation table created by the create migration', function () {
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

    expect(Schema::hasTable(EmbeddedResourceIncarnationStore::TABLE))->toBeFalse()
        ->and(DB::table(CORE12_OWNERSHIP_TABLE)
            ->where('migration', CORE12_CREATE_OWNERSHIP_KEY)
            ->exists())->toBeFalse();
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

it('upgrades and rolls back only columns and indexes owned by the upgrade migration', function () {
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
        ->and(Schema::hasColumn(EmbeddedResourceIncarnationStore::TABLE, 'version'))->toBeFalse()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_identity_unique',
        ))->toBeFalse();
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
