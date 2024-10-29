<?php

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeAll(function () {
    // Ensure the environment variable is set before migrations run
    config(['aura.teams' => false]);
});

afterAll(function () {
    // Ensure the environment variable is set before migrations run
    config(['aura.teams' => true]);
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
    // expect(Schema::hasTable('user_role'))->toBeFalse();
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
test('Settings Component can be rendered', function () {

    $this->withoutExceptionHandling();

    expect(config('aura.features.settings'))->toBeTrue();

    expect(auth()->user()->isSuperAdmin())->toBeTrue();

    $response = $this->get(route('aura.settings'));
    $response->assertStatus(200);
});

test('Default Team Settings are created', function () {
    // Default Team Settings
    Livewire::test(Settings::class)
        ->assertSee('Settings')
        ->assertSee('primary')
        ->assertSet('form.fields.darkmode-type', 'auto')
        ->assertSet('form.fields.sidebar-type', 'primary')
        ->assertSet('form.fields.color-palette', 'aura')
        ->assertSet('form.fields.gray-color-palette', 'slate');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // get first option from DB
    $option = Option::first();

    // assert option is team settings
    // $this->assertEquals($option->name, 'settings');
    $this->assertEquals($option->name, 'settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);
});

test('Settings can be saved', function () {

    // Default Team Settings
    Livewire::test(Settings::class)
        ->set('form.fields.darkmode-type', 'light')
        ->set('form.fields.sidebar-type', 'light')
        ->set('form.fields.color-palette', 'red')
        ->set('form.fields.gray-color-palette', 'zinc')
        ->call('save');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // get first option from DB
    $option = Option::first();

    // assert option is team settings
    $this->assertEquals($option->name, 'settings');
    $this->assertIsArray($option->value);

    $this->assertEquals('light', $option->value['darkmode-type']);
    $this->assertEquals('light', $option->value['sidebar-type']);
    $this->assertEquals('red', $option->value['color-palette']);
    $this->assertEquals('zinc', $option->value['gray-color-palette']);
});
