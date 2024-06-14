<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeAll(function () {
    // Ensure the environment variable is set before migrations run
    putenv('AURA_TEAMS=false');
});

afterAll(function () {
    // Ensure the environment variable is set before migrations run
    putenv('AURA_TEAMS=true');
});

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

test('Settings Component can be rendered', function () {
    $this->withoutExceptionHandling();

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
    $this->assertEquals($option->name, 'settings');

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
    expect($option->name)->toBe('settings');
    expect($option->value)->toBeArray();

    expect($option->value['darkmode-type'])->toBe('light');
    expect($option->value['sidebar-type'])->toBe('light');
    expect($option->value['color-palette'])->toBe('red');
    expect($option->value['gray-color-palette'])->toBe('zinc');

});
