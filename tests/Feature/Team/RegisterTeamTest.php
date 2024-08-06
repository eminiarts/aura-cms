<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Config;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

use function Pest\Livewire\livewire;
use function Pest\Laravel\post;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
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


test('team can be registered with user', function () {
    // Ensure registration is enabled
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => true]);

    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'team' => 'New Team',
    ];

    $response = post(route('aura.register.post'), $userData);

    $response->assertRedirect(config('aura.auth.redirect'));

    expect(User::where('email', 'john@example.com')->first())
        ->toExist()
        ->name->toBe('John Doe');

    expect(Team::where('name', 'New Team')->first())
        ->toExist();

    $user = User::where('email', 'john@example.com')->first();
    $team = Team::where('name', 'New Team')->first();

    expect($user->current_team_id)->toBe($team->id);
    expect($user->belongsToTeam($team))->toBeTrue();
});