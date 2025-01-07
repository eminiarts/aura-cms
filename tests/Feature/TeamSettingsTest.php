<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('Team Settings Component can be rendered', function () {
    $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin2', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['roles' => [$role->id]]);

    $component = Livewire::test(Settings::class);

    $component->assertStatus(200);
});

test('Default Team Settings are created', function () {
    $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin2', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['roles' => [$role->id]]);

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
    $this->assertEquals($option->name, 'team.'.auth()->user()->currentTeam->id.'.settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);

    // assertJSON option value matches expected JSON structure
    $this->assertJsonStringEqualsJsonString(json_encode($option->value), '{"darkmode-type":"auto","sidebar-type":"primary","color-palette":"aura","gray-color-palette":"slate","sidebar-size":"standard","sidebar-darkmode-type":"dark"}');
});

test('Team Settings can be saved2', function () {
    // team factory create team
    $teams = Team::factory(2)->create([
        'user_id' => auth()->user()->id,
    ]);

    $firstTeam = $teams->first();
    $secondTeam = $teams->last();

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
    $this->assertEquals($option->name, 'team.'.auth()->user()->currentTeam->id.'.settings');
    $this->assertIsArray($option->value);

    $this->assertEquals('light', $option->value['darkmode-type']);
    $this->assertEquals('light', $option->value['sidebar-type']);
    $this->assertEquals('red', $option->value['color-palette']);
    $this->assertEquals('zinc', $option->value['gray-color-palette']);

    // acting as $this->user, get settings page and assertSee color variables
    $this->actingAs($this->user)
        ->get(route('aura.settings'))
        ->assertSee('--primary-400: 248 113 113;');

    // Default Team Settings
    Livewire::test(Settings::class)
        ->set('form.fields.color-palette', 'emerald')
        ->call('save');

    $this->actingAs($this->user)
        ->get(route('aura.settings'))
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertSee('--primary-400: 52 211 153;');

    // user switchTeam
    $this->user->switchTeam($secondTeam);

    // Options
    $options = Option::get();

    $this->actingAs($this->user)
        ->get(route('aura.settings'))
        ->assertSee('--primary-400: 52 211 153;');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // Switch back to first team
    $this->user->switchTeam($firstTeam);

    // Team is set to first team and it's set to aura palette
    $this->actingAs($this->user)
        ->get(route('aura.settings'))
        ->assertOk()
        ->assertDontSee('--primary-400: 52 211 153;')
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertDontSee('--primary-400: 82 139 255;');
});
