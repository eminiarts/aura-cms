<?php

use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

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

    // Refresh the database using the RefreshDatabase trait
    $this->refreshTestDatabase();

    // expect teams table not to exist
    expect(Schema::hasTable('teams'))->toBeFalse();
    expect(Schema::hasTable('team_user'))->toBeFalse();
    expect(Schema::hasTable('team_meta'))->toBeFalse();
});

test('Aura without teams - table columns', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Refresh the database using the RefreshDatabase trait
    $this->refreshTestDatabase();

    // expect user table not to have current_team_id
    expect(Schema::hasColumn('users', 'current_team_id'))->toBeFalse();
    expect(Schema::hasColumn('posts', 'team_id'))->toBeFalse();
    expect(Schema::hasColumn('user_meta', 'team_id'))->toBeFalse();
});

test('Aura without teams - options table', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Refresh the database using the RefreshDatabase trait
    $this->refreshTestDatabase();

    // expect options table to exist
    expect(Schema::hasTable('options'))->toBeTrue();
});
