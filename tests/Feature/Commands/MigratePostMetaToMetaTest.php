<?php

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Create necessary tables
    if (! Schema::hasTable('posts')) {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('type')->default('post');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('post_meta')) {
        Schema::create('post_meta', function ($table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('key');
            $table->longText('value')->nullable();
        });
    }

    if (! Schema::hasTable('team_meta')) {
        Schema::create('team_meta', function ($table) {
            $table->id();
            $table->foreignId('team_id');
            $table->string('key');
            $table->longText('value')->nullable();
        });
    }

    if (! Schema::hasTable('user_meta')) {
        Schema::create('user_meta', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('key');
            $table->longText('value')->nullable();
        });
    }
});

afterEach(function () {
    // Clean up tables
    Schema::dropIfExists('meta');
    Schema::dropIfExists('post_meta');
    Schema::dropIfExists('team_meta');
    Schema::dropIfExists('user_meta');
    Schema::dropIfExists('posts');
});

it('can migrate meta data from old tables to new meta table', function () {
    // Create test data
    $post = DB::table('posts')->insertGetId([
        'type' => 'post',
    ]);

    DB::table('post_meta')->insert([
        'post_id' => $post,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);

    DB::table('team_meta')->insert([
        'team_id' => 1,
        'key' => 'team_key',
        'value' => 'team_value',
    ]);

    DB::table('user_meta')->insert([
        'user_id' => 1,
        'key' => 'user_key',
        'value' => 'user_value',
    ]);

    // Run the migration command
    $this->artisan('aura:migrate-post-meta-to-meta')
        ->assertExitCode(0);

    // Assert meta table exists
    expect(Schema::hasTable('meta'))->toBeTrue();

    // Assert data was migrated correctly
    expect(DB::table('meta')->count())->toBe(3);

    // Check post meta migration
    $postMeta = DB::table('meta')
        ->where('metable_type', Post::class)
        ->where('metable_id', $post)
        ->first();

    expect($postMeta->key)->toBe('test_key');
    expect($postMeta->value)->toBe('test_value');

    // Check team meta migration
    $teamMeta = DB::table('meta')
        ->where('metable_type', Team::class)
        ->where('metable_id', 1)
        ->first();

    expect($teamMeta->key)->toBe('team_key');
    expect($teamMeta->value)->toBe('team_value');

    // Check user meta migration
    $userMeta = DB::table('meta')
        ->where('metable_type', User::class)
        ->where('metable_id', 1)
        ->first();

    expect($userMeta->key)->toBe('user_key');
    expect($userMeta->value)->toBe('user_value');
});
