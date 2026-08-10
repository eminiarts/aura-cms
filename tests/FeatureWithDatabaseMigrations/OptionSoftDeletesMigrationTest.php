<?php

use Illuminate\Database\Schema\Blueprint;
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

it('adds and rolls back restorable option storage idempotently', function () {
    Schema::table('options', function (Blueprint $table): void {
        $table->dropSoftDeletes();
    });

    $migration = require dirname(__DIR__, 2).'/database/migrations/add_soft_deletes_to_options.php.stub';

    expect(Schema::hasColumn('options', 'deleted_at'))->toBeFalse();

    $migration->up();
    $migration->up();

    expect(Schema::hasColumn('options', 'deleted_at'))->toBeTrue();

    $migration->down();
    $migration->down();

    expect(Schema::hasColumn('options', 'deleted_at'))->toBeFalse();
});
