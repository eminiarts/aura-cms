<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Settings;
use Aura\Base\Models\User;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    // $this->artisan('migrate:fresh');

    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

test('Settings Component can be rendered', function () {

    dd('hier', config('aura.teams'));
    $this->withoutExceptionHandling();

    ray('he');
    $response = $this->get(route('aura.settings'));

    dd($response->content());

    ray('hi', $response->content());


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
    $this->assertEquals('settings');

    // assert $option->value is an array
    $this->assertIsArray($option->value);

    // assertJSON option value matches expected JSON structure
    $this->assertJsonStringEqualsJsonString(json_encode($option->value), '{"darkmode-type":"auto","sidebar-type":"primary","color-palette":"aura","gray-color-palette":"slate","sidebar-size":"standard","sidebar-darkmode-type":"dark"}');
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
    $this->assertEquals('settings');
    $this->assertIsArray($option->value);

    $this->assertEquals('light', $option->value['darkmode-type']);
    $this->assertEquals('light', $option->value['sidebar-type']);
    $this->assertEquals('red', $option->value['color-palette']);
    $this->assertEquals('zinc', $option->value['gray-color-palette']);

});
