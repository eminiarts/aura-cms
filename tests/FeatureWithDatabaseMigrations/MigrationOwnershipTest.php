<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function auraInstallMigration()
{
    return require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
}

beforeEach(function () {
    foreach (Schema::getTableListing() as $table) {
        $table = str($table)->afterLast('.')->toString();

        if ($table !== 'migrations') {
            Schema::drop($table);
        }
    }

    config(['aura.teams' => true]);
});

it('removes every table it created during a fresh install rollback', function () {
    $migration = auraInstallMigration();

    $migration->up();

    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('posts'))->toBeTrue();

    $migration->down();

    expect(collect(Schema::getTableListing())->map(
        fn (string $table): string => str($table)->afterLast('.')->toString()
    )->all())->toBe(['migrations']);
});

it('preserves host tables and data while removing only Aura additions', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->longText('payload');
    });

    DB::table('users')->insert([
        'id' => 1,
        'name' => 'Host User',
        'email' => 'host@example.com',
        'password' => 'secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('sessions')->insert([
        'id' => 'host-session',
        'payload' => 'host-data',
    ]);

    $migration = auraInstallMigration();
    $migration->up();

    expect(Schema::hasColumn('users', 'two_factor_secret'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'current_team_id'))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('sessions'))->toBeTrue()
        ->and(DB::table('users')->where('id', 1)->value('email'))->toBe('host@example.com')
        ->and(DB::table('sessions')->where('id', 'host-session')->value('payload'))->toBe('host-data')
        ->and(Schema::hasColumn('users', 'two_factor_secret'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'current_team_id'))->toBeFalse()
        ->and(Schema::hasTable('posts'))->toBeFalse();
});

it('fails before mutating host schema when a package-owned table conflicts', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    expect(fn () => auraInstallMigration()->up())
        ->toThrow(RuntimeException::class, 'package-owned tables already exist: roles');

    expect(Schema::hasColumn('users', 'two_factor_secret'))->toBeFalse()
        ->and(Schema::hasTable('aura_migration_ownership'))->toBeFalse()
        ->and(Schema::hasTable('roles'))->toBeTrue();
});
