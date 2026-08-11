<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (Schema::getTableListing() as $table) {
        $table = str($table)->afterLast('.')->toString();

        if ($table !== 'migrations') {
            Schema::drop($table);
        }
    }

    (require dirname(__DIR__, 2).'/database/migrations/create_aura_tables.php.stub')->up();
});

test('saved view migration owns and safely rolls back its table', function () {
    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    $migration->up();
    $migration->up();

    expect(Schema::hasTable('aura_saved_views'))->toBeTrue()
        ->and(Schema::getColumnListing('aura_saved_views'))->toBe([
            'id', 'resource_type', 'owner_id', 'team_id', 'context_key', 'visibility', 'name',
            'schema_version', 'state', 'default_key', 'created_at', 'updated_at',
        ])
        ->and(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')->exists())->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('aura_saved_views'))->toBeFalse();
});

test('saved view migration recovers from an interruption before table creation', function () {
    DB::table('aura_migration_ownership')->insert([
        'migration' => 'create_aura_saved_views_table',
        'ownership' => json_encode([
            'state' => 'creating',
            'created_table' => false,
        ], JSON_THROW_ON_ERROR),
    ]);

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    $migration->up();

    expect(Schema::hasTable('aura_saved_views'))->toBeTrue()
        ->and(json_decode(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')
            ->value('ownership'), true, flags: JSON_THROW_ON_ERROR))->toMatchArray([
                'state' => 'owned',
                'created_table' => true,
            ]);

    $migration->down();

    expect(Schema::hasTable('aura_saved_views'))->toBeFalse();
});

test('saved view migration recovers from an interruption after table creation before rollback', function () {
    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    $migration->up();

    DB::table('aura_migration_ownership')
        ->where('migration', 'create_aura_saved_views_table')
        ->update([
            'ownership' => json_encode([
                'state' => 'creating',
                'created_table' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

    expect(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable('aura_saved_views'))->toBeTrue()
        ->and(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')->exists())->toBeTrue();

    $migration->up();
    $migration->down();

    expect(Schema::hasTable('aura_saved_views'))->toBeFalse()
        ->and(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')->exists())->toBeFalse();
});

test('saved view migration fails closed for malformed ownership records', function () {
    DB::table('aura_migration_ownership')->insert([
        'migration' => 'create_aura_saved_views_table',
        'ownership' => '{malformed',
    ]);

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    expect(fn () => $migration->up())->toThrow(JsonException::class)
        ->and(fn () => $migration->down())->toThrow(JsonException::class)
        ->and(Schema::hasTable('aura_saved_views'))->toBeFalse()
        ->and(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')->exists())->toBeTrue();
});

test('saved view migration fails closed for invalid ownership states', function () {
    DB::table('aura_migration_ownership')->insert([
        'migration' => 'create_aura_saved_views_table',
        'ownership' => json_encode([
            'state' => 'claimed',
            'created_table' => true,
        ], JSON_THROW_ON_ERROR),
    ]);

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and(fn () => $migration->down())->toThrow(RuntimeException::class)
        ->and(Schema::hasTable('aura_saved_views'))->toBeFalse()
        ->and(DB::table('aura_migration_ownership')
            ->where('migration', 'create_aura_saved_views_table')->exists())->toBeTrue();
});

test('saved view migration refuses to claim a host table', function () {
    Schema::create('aura_saved_views', fn ($table) => $table->id());

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class);

    $migration->down();

    expect(Schema::hasTable('aura_saved_views'))->toBeTrue();
});
