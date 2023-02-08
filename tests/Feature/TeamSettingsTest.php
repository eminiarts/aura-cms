<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Http\Livewire\TeamSettings;
use Eminiarts\Aura\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('Team Settings Component can be rendered', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    $component = Livewire::test(TeamSettings::class);

    $component->assertStatus(200);
});

test('Default Team Settings are created', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    // Default Team Settings
    Livewire::test(TeamSettings::class)
        ->assertSee('Set all the team related options')
        ->assertSee('primary')
        ->assertSet('post.fields.darkmode-type', 'auto')
        ->assertSet('post.fields.sidebar-type', 'primary')
        ->assertSet('post.fields.color-palette', 'aura')
        ->assertSet('post.fields.gray-color-palette', 'slate');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // get first option from DB
    $option = Option::first();

    // assert option is team settings
    $this->assertEquals($option->name, 'team-settings');

    // assertJSON option value darkmode-type is auto
    $this->assertJsonStringEqualsJsonString($option->value, '{"darkmode-type":"auto","sidebar-type":"primary","color-palette":"aura","gray-color-palette":"slate"}');
});

test('Team Settings can be saved', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$role->id]]]);

    // team factory create team
    $teams = Team::factory(2)->create();
    $firstTeam = $teams->first();
    $secondTeam = $teams->last();

    // create a entry in team_user table with team_id and user_id
    $this->user->teams()->attach([$firstTeam->id, $secondTeam->id]);

    // Default Team Settings
    Livewire::test(TeamSettings::class)
        ->set('post.fields.darkmode-type', 'light')
        ->set('post.fields.sidebar-type', 'light')
        ->set('post.fields.color-palette', 'red')
        ->set('post.fields.gray-color-palette', 'zinc')
        ->call('save');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 1);

    // get first option from DB
    $option = Option::first();

    // assert option is team settings
    $this->assertEquals($option->name, 'team-settings');

    // assertJSON option value darkmode-type is auto
    $this->assertJsonStringEqualsJsonString($option->value, '{"app-logo":null,"app-logo-darkmode":null,"timezone1":null,"timezone":null,"sidebar-type":"light","darkmode-type":"light","color-palette":"red","primary-25":null,"primary-50":null,"primary-100":null,"primary-200":null,"primary-300":null,"primary-400":null,"primary-500":null,"primary-600":null,"primary-700":null,"primary-800":null,"primary-900":null,"gray-color-palette":"zinc","gray-25":null,"gray-50":null,"gray-100":null,"gray-200":null,"gray-300":null,"gray-400":null,"gray-500":null,"gray-600":null,"gray-700":null,"gray-800":null,"gray-900":null}');

    // acting as $this->user, get team-settings page and assertSee "--primary-400: #f87171;" in html
    $this->actingAs($this->user)
        ->get('/team-settings')
        ->assertSee('--primary-400: 248 113 113;');

    // Default Team Settings
    Livewire::test(TeamSettings::class)
        ->set('post.fields.color-palette', 'emerald')
        ->call('save');

    $this->actingAs($this->user)
        ->get('/team-settings')
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertSee('--primary-400: 16 185 129;');

    // user switchTeam
    $this->user->switchTeam($secondTeam);

    $this->actingAs($this->user)
        ->get('/team-settings')
        ->assertSee('--primary-400: 82 139 255;');

    // assert DB has 1 record in options table
    $this->assertDatabaseCount('options', 2);

    // Switch back to first team
    $this->user->switchTeam($firstTeam);

    $this->actingAs($this->user)
        ->get('/team-settings')
        ->assertSee('--primary-400: 16 185 129;')
        ->assertDontSee('--primary-400: 248 113 113;')
        ->assertDontSee('--primary-400: 82 139 255;');
});
