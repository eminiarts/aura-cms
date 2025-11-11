<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(Aura\Base\Tests\TestCase::class)->in(__DIR__);

uses()->group('fields')->in('Feature/Fields');
uses()->group('flows')->in('Feature/Flows');
uses()->group('table')->in('Feature/Table');
uses()->group('resource')->in('Feature/Resource');

uses(RefreshDatabase::class)->in('Feature');
uses(DatabaseMigrations::class)->in('FeatureWithDatabaseMigrations');

// uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createPost(array $attributes = []): Post
{
    return Post::factory()->create($attributes);
}

function createSuperAdmin()
{
    $user = User::factory()->create();

    auth()->login($user);

    // Create Team
    $team = Team::factory()->create();

    // Set current_team_id of the user
    $user->update(['current_team_id' => $team->id]);

    // Create or find Super Admin role for the team
    $role = Role::firstOrCreate([
        'team_id' => $team->id,
        'slug' => 'super_admin',
    ], [
        'type' => 'Role',
        'title' => 'Super Admin',
        'name' => 'Super Admin',
        'description' => 'Super Admin can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $user->id,
    ]);

    // Associate the role with the user using the proper relationship
    if (config('aura.teams')) {
        $user->roles()->syncWithPivotValues([$role->id], ['team_id' => $team->id]);
    } else {
        $user->roles()->sync([$role->id]);
    }

    $user->refresh();

    return $user;
}

function bootstrapApp()
{
    $app = require_once base_path('bootstrap/app.php'); // Using base_path to ensure correct location
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    // Run migrations
    // Artisan::call('migrate:fresh', ['--force' => true]);
}

function createSuperAdminWithoutTeam()
{
    $user = User::factory()->create();

    auth()->login($user);

    $role = Role::create([
        'type' => 'Role',
        'title' => 'Super Admin',
        'slug' => 'super_admin',
        'name' => 'Super Admin',
        'description' => 'Super Admin can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $user->id,
    ]);

    $user->update(['roles' => [$role->id]]);

    $user->refresh();

    return $user;
}

function createAdmin()
{
    $user = User::factory()->create();

    // Create Team
    if (! $team = Team::first()) {
        $team = Team::factory()->create();
    }

    // Set current_team_id of the user
    $user->update(['current_team_id' => $team->id]);

    $role = Role::create(['team_id' => $team->id, 'type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'view-attachment' => true,
        'viewAny-attachment' => true,
        'create-attachment' => true,
        'update-attachment' => true,
        'restore-attachment' => true,
        'delete-attachment' => true,
        'forceDelete-attachment' => true,
        'scope-attachment' => false,
        'view-option' => false,
        'viewAny-option' => false,
        'create-option' => false,
        'update-option' => false,
        'restore-option' => false,
        'delete-option' => false,
        'forceDelete-option' => false,
        'scope-option' => false,
        'view-post' => false,
        'viewAny-post' => false,
        'create-post' => false,
        'update-post' => false,
        'restore-post' => false,
        'delete-post' => false,
        'forceDelete-post' => false,
        'scope-post' => false,
        'view-permission' => false,
        'viewAny-permission' => false,
        'create-permission' => false,
        'update-permission' => false,
        'restore-permission' => false,
        'delete-permission' => false,
        'forceDelete-permission' => false,
        'scope-permission' => false,
        'view-role' => false,
        'viewAny-role' => false,
        'create-role' => false,
        'update-role' => false,
        'restore-role' => false,
        'delete-role' => false,
        'forceDelete-role' => false,
        'scope-role' => false,
        'view-user' => true,
        'viewAny-user' => true,
        'create-user' => true,
        'update-user' => true,
        'restore-user' => true,
        'delete-user' => true,
        'forceDelete-user' => true,
        'scope-user' => true,
        'view-team' => false,
        'viewAny-team' => false,
        'create-team' => false,
        'update-team' => false,
        'restore-team' => false,
        'delete-team' => false,
        'forceDelete-team' => false,
        'scope-team' => false,
        'view-TeamInvitation' => false,
        'viewAny-TeamInvitation' => false,
        'create-TeamInvitation' => false,
        'update-TeamInvitation' => false,
        'restore-TeamInvitation' => false,
        'delete-TeamInvitation' => false,
        'forceDelete-TeamInvitation' => false,
        'scope-TeamInvitation' => false,
    ]]);

    $user->update(['roles' => [$role->id]]);
    $user->refresh();

    return $user;
}
