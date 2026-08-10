<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (Schema::getTableListing() as $table) {
        $table = str($table)->afterLast('.')->toString();

        if ($table !== 'migrations') {
            Schema::drop($table);
        }
    }

    $install = require dirname(__DIR__, 2).'/database/migrations/create_aura_tables.php.stub';
    $install->up();
});

function optionIdentityIndex(): string
{
    return Schema::hasColumn('options', 'team_id')
        ? 'options_team_name_unique'
        : 'options_name_unique';
}

function dropOptionIdentityIndex(): void
{
    $index = optionIdentityIndex();

    Schema::table('options', function (Blueprint $table) use ($index): void {
        $table->dropUnique($index);
    });
}

function optionIdentityMigration(): Migration
{
    return require dirname(__DIR__, 2).'/database/migrations/enforce_unique_option_identity.php.stub';
}

it('adds and rolls back scoped option uniqueness idempotently', function () {
    dropOptionIdentityIndex();
    $migration = optionIdentityMigration();

    expect(Schema::hasIndex('options', optionIdentityIndex(), 'unique'))->toBeFalse();

    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('options', optionIdentityIndex(), 'unique'))->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasIndex('options', optionIdentityIndex(), 'unique'))->toBeFalse();
});

it('refuses to guess which duplicate option row should survive', function () {
    dropOptionIdentityIndex();
    $attributes = [
        'name' => 'duplicate-option',
        'value' => json_encode(['version' => 1]),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('options', 'team_id')) {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Duplicate Owner',
            'email' => 'duplicate-owner@example.test',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $teamId = DB::table('teams')->insertGetId([
            'name' => 'Duplicate Scope',
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attributes['team_id'] = $teamId;
    }

    DB::table('options')->insert([$attributes, $attributes]);

    expect(fn () => optionIdentityMigration()->up())
        ->toThrow(RuntimeException::class, 'duplicate scoped names exist')
        ->and(DB::table('options')->count())->toBe(2)
        ->and(Schema::hasIndex('options', optionIdentityIndex(), 'unique'))->toBeFalse();
});
