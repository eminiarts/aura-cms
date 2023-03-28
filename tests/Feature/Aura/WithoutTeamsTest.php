<?php

use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// current
uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);

    $this->taxonomyData = [
        'name' => 'Test Category',
        'slug' => 'test-category',
        'taxonomy' => 'Category',
        'description' => 'A test category',
        'parent' => 0,
        'count' => 0,
    ];
});


test('Aura without teams', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Rerun migrations
    $this->artisan('migrate:fresh');

    // expect teams table not to exist
    expect(Schema::hasTable('teams'))->toBeFalse();
    expect(Schema::hasTable('team_user'))->toBeFalse();
    expect(Schema::hasTable('team_meta'))->toBeFalse();
});

test('Aura without teams - table columns', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Rerun migrations
    $this->artisan('migrate:fresh');

    // expect user table not to have current_team_id
    expect(Schema::hasColumn('users', 'current_team_id'))->toBeFalse();
    expect(Schema::hasColumn('posts', 'team_id'))->toBeFalse();
    expect(Schema::hasColumn('taxonomies', 'team_id'))->toBeFalse();
    expect(Schema::hasColumn('user_meta', 'team_id'))->toBeFalse();
});

test('Aura without teams - options table', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Rerun migrations
    $this->refreshTestDatabase();
    $this->getEnvironmentSetUp($this->app);

    // expect options table to exist
    expect(Schema::hasTable('options'))->toBeTrue();
});

test('Aura without teams - pages', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Rerun migrations
    $this->artisan('migrate:fresh', ['--env' => 'testing']);
    $this->getEnvironmentSetUp($this->app);

    // Create User
    $user = User::factory()->create();

    // Create Role
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = User::find($user->id);
    $user->update(['fields' => ['roles' => [$role->id]]]);

    // Refresh User
    $user = $user->refresh();
    $this->actingAs($user);

    // expect Dashboard to be accessible
    $this->get(config('aura.path'))->assertOk();

    // Team Settings Page
    $this->get(route('aura.team.settings'))->assertOk();

    // Resources
    $this->get(route('aura.post.index', ['slug' =>'Option']))->assertOk();
    $this->get(route('aura.post.index', ['slug' =>'User']))->assertOk();
});
