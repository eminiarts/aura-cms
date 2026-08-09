<?php

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

it('includes a nullable full owner verifier in the fresh option schema', function () {
    $expectedOwnerType = DB::connection()->getDriverName() === 'mysql' ? 'char(64)' : 'varchar';

    expect(Schema::hasColumn('options', 'owner_identity'))->toBeTrue()
        ->and(strtolower(Schema::getColumnType('options', 'owner_identity', true)))->toBe($expectedOwnerType);

    $attributes = [
        'name' => 'global-option-without-owner',
        'value' => json_encode(['enabled' => true]),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('options', 'team_id')) {
        $attributes['team_id'] = 1;
    }

    DB::table('options')->insert($attributes);

    expect(DB::table('options')->value('owner_identity'))->toBeNull();
});

it('adds and rolls back owner verification without guessing existing ownership', function () {
    Schema::table('options', function (Blueprint $table): void {
        $table->dropColumn('owner_identity');
    });

    $attributes = [
        'name' => 'u00000000000000ambiguous-existing-option',
        'value' => json_encode(['preserved' => true]),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('options', 'team_id')) {
        $attributes['team_id'] = 1;
    }

    DB::table('options')->insert($attributes);

    $migration = require dirname(__DIR__, 2).'/database/migrations/add_owner_identity_to_options.php.stub';
    $migration->up();
    $migration->up();

    expect(Schema::hasColumn('options', 'owner_identity'))->toBeTrue()
        ->and(DB::table('options')->value('owner_identity'))->toBeNull()
        ->and(json_decode(DB::table('options')->value('value'), true))->toBe(['preserved' => true]);

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn('options', 'owner_identity'))->toBeFalse()
        ->and(DB::table('options')->count())->toBe(1);
});
