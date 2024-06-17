<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Config;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Option;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('team can be registered', function () {
});

// test('registration config can be disabled', function () {
//     // test Config Livewire component
//     $component = livewire(Config::class)
//         ->set('form.fields.team_registration', false)
//         ->call('save')
//         ->assertHasNoErrors();

//     // DB options shoult have name=aura-settings and team_id = 0
//     $this->assertDatabaseHas('options', [
//         'name' => 'aura-settings',
//         'team_id' => 0,
//     ]);

//     $option = Option::withoutGlobalScopes([TeamScope::class])->where('name', 'aura-settings')->where('team_id', 0)->first();

//     // decode the value
//     $options = json_decode($option->value);

//     // expect $options->team_registration to be true
//     expect($options->team_registration)->toBeFalse();
//     expect(Aura::option('team_registration'))->toBeFalse();
// });

// test('registration config can be enabled', function () {
//     // test Config Livewire component
//     $component = livewire(Config::class)
//         ->set('form.fields.team_registration', true)
//         ->call('save')
//         ->assertHasNoErrors();

//     // DB options shoult have name=aura-settings and team_id = 0
//     $this->assertDatabaseHas('options', [
//         'name' => 'aura-settings',
//         'team_id' => 0,
//     ]);

//     $option = Option::withoutGlobalScopes([TeamScope::class])->where('name', 'aura-settings')->where('team_id', 0)->first();

//     // decode the value
//     $options = json_decode($option->value);

//     // expect $options->team_registration to be true
//     expect($options->team_registration)->toBeTrue();
// });

test('aura config site is working', function () {
    $this->markTestSkipped('config not available atm');
    // make sure the site route('aura.config') is working
    $this->get(route('aura.config'))->assertOk();
});

// test('registration config can be enabled and called from the aura facade', function () {
//     livewire(Config::class)
//         ->set('form.fields.team_registration', true)
//         ->call('save')
//         ->assertHasNoErrors();

//     expect(Aura::option('team_registration'))->toBeTrue();

//     expect(app('aura')::option('team_registration'))->toBeTrue();
// });
