<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    foreach (Schema::getTableListing() as $table) {
        $table = str($table)->afterLast('.')->toString();

        if ($table !== 'migrations') {
            Schema::drop($table);
        }
    }

    $install = require dirname(__DIR__, 2).'/database/migrations/create_aura_tables.php.stub';
    $install->up();
});

function dropMetaIdentityIndex(): void
{
    Schema::table('meta', function (Blueprint $table): void {
        $table->dropUnique('meta_metable_identity_unique');
    });
}

function metaIdentityMigration(): Migration
{
    return require dirname(__DIR__, 2).'/database/migrations/enforce_unique_meta_identity.php.stub';
}

it('adds and rolls back meta identity uniqueness idempotently', function (): void {
    dropMetaIdentityIndex();
    $migration = metaIdentityMigration();

    expect(Schema::hasIndex('meta', 'meta_metable_identity_unique', 'unique'))->toBeFalse();

    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('meta', 'meta_metable_identity_unique', 'unique'))->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasIndex('meta', 'meta_metable_identity_unique', 'unique'))->toBeFalse();
});

it('refuses to guess which duplicate meta row should survive', function (): void {
    dropMetaIdentityIndex();
    $row = [
        'metable_type' => 'App\\Resource',
        'metable_id' => 123,
        'key' => 'status',
        'value' => 'open',
    ];
    DB::table('meta')->insert([$row, $row]);

    expect(fn () => metaIdentityMigration()->up())
        ->toThrow(RuntimeException::class, 'duplicate model/type/key rows exist')
        ->and(DB::table('meta')->count())->toBe(2)
        ->and(Schema::hasIndex('meta', 'meta_metable_identity_unique', 'unique'))->toBeFalse();
});

it('the unique identity rejects concurrent duplicate insert outcomes', function (): void {
    $row = [
        'metable_type' => 'App\\Resource',
        'metable_id' => 456,
        'key' => 'status',
        'value' => 'open',
    ];
    DB::table('meta')->insert($row);

    expect(fn () => DB::table('meta')->insert($row))->toThrow(QueryException::class)
        ->and(DB::table('meta')->where($row)->count())->toBe(1);
});
