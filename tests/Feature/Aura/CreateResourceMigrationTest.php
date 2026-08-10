<?php

use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

class Core14MigrationOwnerResource extends Resource
{
    public static $customTable = true;

    public static ?string $ownerColumn = 'owner_id';

    public static ?string $ownerRelation = 'assignee';

    public static array $physicalFields = ['name'];

    protected $fillable = ['name'];

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Text::class],
        ];
    }
}

class Core14MigrationGlobalResource extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['name'];

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];
    }
}

afterEach(function () {
    // Clean up any created migration files
    collect(File::glob(database_path('migrations/*_create_users_table.php')))
        ->each(fn ($file) => File::delete($file));
    collect(File::glob(database_path('migrations/*_create_core14migration*_table.php')))
        ->each(fn ($file) => File::delete($file));
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

it('generates only declared physical and configured ownership columns', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => Core14MigrationOwnerResource::class,
    ])->assertExitCode(0);

    $migrationFile = collect(File::glob(database_path('migrations/*_create_core14migrationownerresources_table.php')))
        ->first();

    expect($migrationFile)->not->toBeNull();

    $content = File::get($migrationFile);

    expect($content)
        ->toContain("string('name')")
        ->toContain("bigInteger('owner_id')")
        ->not->toContain("'notes'")
        ->not->toContain("'user_id'");
});

it('does not generate owner or team columns for global resources', function () {
    $this->artisan('aura:create-resource-migration', [
        'resource' => Core14MigrationGlobalResource::class,
    ])->assertExitCode(0);

    $migrationFile = collect(File::glob(database_path('migrations/*_create_core14migrationglobalresources_table.php')))
        ->first();

    expect($migrationFile)->not->toBeNull();

    $content = File::get($migrationFile);

    expect($content)
        ->toContain("string('name')")
        ->not->toContain("'user_id'")
        ->not->toContain("'team_id'");
});
