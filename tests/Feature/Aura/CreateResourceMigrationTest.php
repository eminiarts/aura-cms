<?php

use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function () {
    // Clean up any created migration files
    collect(File::glob(database_path('migrations/*_create_users_table.php')))
        ->merge(File::glob(database_path('migrations/*_create_blog_posts_table.php')))
        ->each(fn ($file) => File::delete($file));

    if (File::exists(app_path('Aura/Resources/BlogPost.php'))) {
        File::delete(app_path('Aura/Resources/BlogPost.php'));
    }
});

it('creates a migration file for a resource', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    // Check if migration file was created
    $migrationFile = collect(File::glob(database_path('migrations/*_create_users_table.php')))
        ->first();

    expect($migrationFile)->not->toBeNull();
    expect(File::exists($migrationFile))->toBeTrue();
});

it('generates migration with Schema::create', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    $migrationFile = collect(File::glob(database_path('migrations/*_create_users_table.php')))
        ->first();

    $content = File::get($migrationFile);

    expect($content)
        ->toContain('Schema::create')
        ->toContain('users');
});

it('generates migration with basic columns', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => User::class,
    ])
        ->assertExitCode(0);

    $migrationFile = collect(File::glob(database_path('migrations/*_create_users_table.php')))
        ->first();

    $content = File::get($migrationFile);

    expect($content)
        ->toContain('user_id')
        ->toContain('team_id');
});

it('shows success message', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => User::class,
    ])
        ->expectsOutputToContain("Migration 'create_users_table' created successfully.")
        ->assertExitCode(0);
});

it('fails when resource class does not exist', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => 'NonExistentResource',
    ])
        ->expectsOutput("Resource class 'NonExistentResource' not found.")
        ->assertExitCode(1);
});

it('fails when resource has no getFields method', function () {
    // Create a mock class that exists but has no getFields method
    eval('class InvalidMigrationResource {}');

    $this->artisan('aura:create-resource-migration', [
        'resource' => 'InvalidMigrationResource',
    ])
        ->expectsOutput("Method 'getFields' not found in the 'InvalidMigrationResource' class.")
        ->assertExitCode(1);
});

it('uses the table the resource actually reads from', function () {
    $this->artisan('aura:resource', ['name' => 'BlogPost', '--custom' => true])
        ->assertExitCode(0);

    require_once app_path('Aura/Resources/BlogPost.php');

    $this->artisan('aura:create-resource-migration', [
        'resource' => 'App\\Aura\\Resources\\BlogPost',
    ])
        ->expectsOutputToContain("Migration 'create_blog_posts_table' created successfully.")
        ->assertExitCode(0);

    $migrationFile = collect(File::glob(database_path('migrations/*_create_blog_posts_table.php')))->first();

    expect($migrationFile)->not->toBeNull();
    expect(File::get($migrationFile))
        ->toContain("Schema::create('blog_posts'")
        ->toContain("Schema::dropIfExists('blog_posts');");
});
