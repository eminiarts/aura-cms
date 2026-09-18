<?php

use Aura\Base\Resources\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

afterEach(function () {
    Schema::dropIfExists('user_meta');
});

it('does nothing on a fresh install where the legacy meta tables are missing', function () {
    expect(Schema::hasTable('post_meta'))->toBeFalse();

    $this->artisan('aura:migrate-post-meta-to-meta')
        ->expectsOutput('Nothing to migrate: the post_meta, team_meta and user_meta tables do not exist.')
        ->assertExitCode(0);
});

it('migrates the legacy tables that do exist', function () {
    Schema::create('user_meta', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('key')->nullable();
        $table->longText('value')->nullable();
    });

    $userId = DB::table('users')->insertGetId([
        'name' => 'Legacy User',
        'email' => 'legacy@example.com',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('user_meta')->insert([
        'user_id' => $userId,
        'key' => 'phone',
        'value' => '123456',
    ]);

    $this->artisan('aura:migrate-post-meta-to-meta')->assertExitCode(0);

    expect(DB::table('meta')->where([
        'metable_type' => User::class,
        'metable_id' => $userId,
        'key' => 'phone',
    ])->value('value'))->toBe('123456');
});
