<?php

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

beforeAll(function () {
    // Ensure the environment variable is set before migrations run
    putenv('AURA_TEAMS=false');
});


// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

test('Aura without teams', function () {
    // ray()->clearScreen();
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
    expect(config('aura.teams'))->toBeFalse();

    // expect options table to exist
    expect(Schema::hasTable('options'))->toBeTrue();
});

// Additional test for SettingsWithoutTeamsTest
test('Settings Component can be rendered2', function () {

    $this->withoutExceptionHandling();

    expect(config('aura.features.theme_options'))->toBeTrue();

    $user = User::find(1);

    dd(DB::table('user_meta')->get());

    ray($user->toArray())->purple();

     ray(auth()->user()->toArray())->red();
     ray(auth()->user()->roles)->red();

        ray(auth()->user()->isSuperAdmin())->green();

        expect(auth()->user()->isSuperAdmin())->toBeTrue();

    $response = $this->get(route('aura.settings'));
    $response->assertStatus(200);
});

test('Default Team Settings are created', function () {
    $settings = config('team-settings');
    expect($settings)->toBeArray();
});

test('Settings can be saved', function () {
    $this->post('/settings/save', [
        'setting_name' => 'example_setting',
        'setting_value' => 'example_value'
    ])->assertStatus(200);

    $this->assertDatabaseHas('settings', [
        'name' => 'example_setting',
        'value' => 'example_value'
    ]);
});
