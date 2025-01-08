<?php

namespace Tests\Feature;

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {

    $this->markTestSkipped('Skipped for now');
    // Set teams to false for this test
    config(['aura.teams' => false]);

    // Drop all tables and run our migration
    Schema::dropAllTables();
    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $user = User::factory()->create();

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

    $this->actingAs($user);
});

afterEach(function () {
    // Restore original config value
    config(['aura.teams' => true]);
});

test('Aura without teams', function () {
    expect(config('aura.teams'))->toBeFalse();

    // expect teams table not to exist
    expect(Schema::hasTable('teams'))->toBeFalse();
    expect(Schema::hasTable('team_meta'))->toBeFalse();
});

test('Aura without teams - table columns', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

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
    config(['aura.features.settings' => true]);

    $this->withoutExceptionHandling();

    expect(config('aura.features.settings'))->toBeTrue();
    expect(auth()->user()->isSuperAdmin())->toBeTrue();

    $response = $this->get(route('aura.settings'));
    $response->assertStatus(200);
});

test('Default Team Settings are created', function () {
    config(['aura.features.settings' => true]);

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
    $this->assertEquals($option->name, 'settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);
});

test('Settings can be saved', function () {
    config(['aura.features.settings' => true]);

    // Default Team Settings
    Livewire::test(Settings::class)
        ->set('form.fields.darkmode-type', 'light')
        ->set('form.fields.sidebar-type', 'light')
        ->set('form.fields.color-palette', 'rose')
        ->set('form.fields.gray-color-palette', 'zinc')
        ->call('save')
        ->assertSet('form.fields.darkmode-type', 'light')
        ->assertSet('form.fields.sidebar-type', 'light')
        ->assertSet('form.fields.color-palette', 'rose')
        ->assertSet('form.fields.gray-color-palette', 'zinc');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // get first option from DB
    $option = Option::first();

    // assert option is team settings
    $this->assertEquals($option->name, 'settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);

    // assert settings are saved
    $this->assertEquals($option->value['darkmode-type'], 'light');
    $this->assertEquals($option->value['sidebar-type'], 'light');
    $this->assertEquals($option->value['color-palette'], 'rose');
    $this->assertEquals($option->value['gray-color-palette'], 'zinc');
});
