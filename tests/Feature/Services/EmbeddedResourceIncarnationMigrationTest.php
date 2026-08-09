<?php

use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('installs the durable embedded resource incarnation store idempotently', function () {
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
        ))->toBeTrue();
});

it('upgrades and rolls back the previous incarnation store contract idempotently', function () {
    Schema::drop(EmbeddedResourceIncarnationStore::TABLE);
    Schema::create(EmbeddedResourceIncarnationStore::TABLE, function (Blueprint $table): void {
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
    DB::table(EmbeddedResourceIncarnationStore::TABLE)->insert([
        'resource_type' => 'LegacyResource',
        'resource_key_hash' => str_repeat('a', 64),
        'incarnation' => '00000000-0000-4000-8000-000000000001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $migration = require dirname(__DIR__, 3).'/database/migrations/upgrade_embedded_resource_incarnations.php.stub';

    $migration->up();
    $migration->up();

    expect(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
        'resource_key_type',
        'resource_key',
        'version',
    ]))->toBeTrue()
        ->and(DB::table(EmbeddedResourceIncarnationStore::TABLE)->count())->toBe(0)
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
        'resource_key_type',
        'resource_key',
        'version',
    ]))->toBeFalse()
        ->and(Schema::hasIndex(
            EmbeddedResourceIncarnationStore::TABLE,
            'aura_embedded_incarnation_guard_lookup',
        ))->toBeFalse()
        ->and(Schema::hasColumns(EmbeddedResourceIncarnationStore::TABLE, [
            'resource_type',
            'resource_key_hash',
            'incarnation',
        ]))->toBeTrue();
});
