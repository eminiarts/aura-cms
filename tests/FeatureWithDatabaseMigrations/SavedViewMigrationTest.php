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

test('saved view migration refuses to claim a host table', function () {
    Schema::create('aura_saved_views', fn ($table) => $table->id());

    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_saved_views_table.php.stub';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class);

    $migration->down();

    expect(Schema::hasTable('aura_saved_views'))->toBeTrue();
});
