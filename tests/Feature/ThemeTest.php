<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\TeamSettings;
use Aura\Base\Models\User;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin() ));

test('Team Settings Component can be rendered', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    $component = Livewire::test(TeamSettings::class);

    $component->assertStatus(200);
});

test('Default Team Settings are created', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    // Default Team Settings
    Livewire::test(TeamSettings::class)
        ->assertSee('Theme Options')
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
    $this->assertEquals($option->name, 'team-settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);

    // assertJSON option value darkmode-type is auto
    $this->assertJsonStringEqualsJsonString(json_encode($option->value), '{"darkmode-type":"auto","sidebar-type":"primary","color-palette":"aura","gray-color-palette":"slate"}');
});

test('Team Settings can be saved', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    // team factory create team
    $teams = Team::factory(2)->create();
    $firstTeam = $teams->first();
    $secondTeam = $teams->last();

    // we need to create a role for the second team
    $role2 = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    $this->user->teams()->attach([
        $secondTeam->id => ['key' => 'roles', 'value' => $role2->id],
    ]);

    // Default Team Settings
    Livewire::test(TeamSettings::class)
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
    $this->assertEquals($option->name, 'team-settings');

    $this->assertIsArray($option->value);

    $this->assertEquals('light', $option->value['darkmode-type']);
    $this->assertEquals('light', $option->value['sidebar-type']);
    $this->assertEquals('red', $option->value['color-palette']);
    $this->assertEquals('zinc', $option->value['gray-color-palette']);

    // acting as $this->user, get team-settings page and assertSee "--primary-400: #f87171;" in html
    $this->actingAs($this->user)
        ->get(route('aura.team.settings'))
        ->assertSee('--primary-400: 248 113 113;');

    // Default Team Settings
    Livewire::test(TeamSettings::class)
        ->set('form.fields.color-palette', 'emerald')
        ->call('save');

    $this->actingAs($this->user)
        ->get(route('aura.team.settings'))
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertSee('--primary-400: 60 126 244;');

    // user switchTeam
    $this->user->switchTeam($secondTeam);

    // Options
    $options = Option::get();

    $this->actingAs($this->user)
        ->get(route('aura.team.settings'))
        ->assertSee('--primary-400: 60 126 244;');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // Switch back to first team
    $this->user->switchTeam($firstTeam);

    $this->actingAs($this->user)
        ->get(route('aura.team.settings'))
        ->assertOk()
        ->assertSee('--primary-400: 60 126 244;')
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertDontSee('--primary-400: 82 139 255;');
});


test('different options apply correct classes', function ($settings) {
    $darkmodeType = $settings['darkmode-type'];
    $sidebarType = $settings['sidebar-type'];
    $sidebarDarkmodeType = $settings['sidebar-darkmode-type'] ?? null;
    $colorPalette = $settings['color-palette'];
    $resultSidebar = $settings['result-sidebar'];
    $resultContent = $settings['result-content'];

    $component = Livewire::test(TeamSettings::class)
        ->set('form.fields.darkmode-type', $darkmodeType)
        ->set('form.fields.sidebar-type', $sidebarType)
        ->set('form.fields.sidebar-darkmode-type', $sidebarDarkmodeType)
        ->set('form.fields.color-palette', $colorPalette)
        ->call('save');

    /* .aura-sidebar */
    // .aura-sidebar-type-primary  {}
    // .aura-sidebar-type-light  {}
    // .aura-sidebar-type-dark  {}
    // /* Dark */
    // .aura-sidebar-darkmode-type-primary  {}
    // .aura-sidebar-darkmode-type-light  {}
    // .aura-sidebar-darkmode-type-dark  {}

    // $component->assertSee($resultSidebar);
    // $component->assertSee($resultContent);

    $this->actingAs($this->user)
        ->get(route('aura.team.settings'))
        ->assertOk()
        ->assertSee($resultSidebar)
        ->assertSee($resultContent);

})->with([
    [[
        'darkmode-type' => 'light',
        'sidebar-type' => 'light',
        'color-palette' => 'red',
        'result-sidebar' => 'aura-sidebar-type-light',
        'result-content' => '',
    ]],
    [[
        'darkmode-type' => 'dark',
        'sidebar-type' => 'primary',
        'color-palette' => 'red',
        'result-sidebar' => 'aura-sidebar-type-primary',
        'result-content' => '',
    ]],
    [[
        'darkmode-type' => 'auto',
        'sidebar-type' => 'primary',
        'sidebar-darkmode-type' => 'primary',
        'color-palette' => 'red',
        'result-sidebar' => 'aura-sidebar-type-primary',
        'result-content' => 'aura-sidebar-darkmode-type-primary',
    ]],
    [[
        'darkmode-type' => 'auto',
        'sidebar-type' => 'primary',
        'sidebar-darkmode-type' => 'dark',
        'color-palette' => 'red',
        'result-sidebar' => 'aura-sidebar-type-primary',
        'result-content' => 'aura-sidebar-darkmode-type-dark',
    ]],

]);
