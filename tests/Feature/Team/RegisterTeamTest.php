<?php

use Eminiarts\Aura\Models\User;

use Eminiarts\Aura\Resources\Option;

use Eminiarts\Aura\Models\Scopes\TeamScope;

use Eminiarts\Aura\Http\Livewire\AuraConfig;

use function Pest\Livewire\livewire;

uses()->group('current');


// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

test('team can be registered', function () {
});

test('registration config can be disabled', function () {
    // test AuraConfig Livewire component
    $component = livewire(AuraConfig::class)
        ->set('post.fields.team_registration', false)
        ->call('save')
        ->assertHasNoErrors();

    // DB options shoult have name=aura-settings and team_id = 0
    $this->assertDatabaseHas('options', [
        'name' => 'aura-settings',
        'team_id' => 0,
    ]);

    $option = Option::withoutGlobalScopes([TeamScope::class])->where('name', 'aura-settings')->where('team_id', 0)->first();

    // decode the value
    $options = json_decode($option->value);

    // expect $options->team_registration to be true
    expect($options->team_registration)->toBeFalse();
});

test('registration config can be enabled', function () {
    // test AuraConfig Livewire component
    $component = livewire(AuraConfig::class)
        ->set('post.fields.team_registration', true)
        ->call('save')
        ->assertHasNoErrors();

    // DB options shoult have name=aura-settings and team_id = 0
    $this->assertDatabaseHas('options', [
        'name' => 'aura-settings',
        'team_id' => 0,
    ]);

    $option = Option::withoutGlobalScopes([TeamScope::class])->where('name', 'aura-settings')->where('team_id', 0)->first();

    // decode the value
    $options = json_decode($option->value);

    // expect $options->team_registration to be true
    expect($options->team_registration)->toBeTrue();
});

test('aura config site is working', function () {
    // make sure the site route('aura.config') is working
    $this->get(route('aura.config'))->assertOk();
});
