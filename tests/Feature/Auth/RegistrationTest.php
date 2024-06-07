<?php

namespace Tests\Feature\Auth;

use Aura\Base\Providers\RouteServiceProvider;
use Aura\Base\Resources\Team;

beforeEach(function () {
    // Enable Team Registration
    config(['aura.features.registration' => true]);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('aura.register'));

    $response->assertSee('Team');
    $response->assertSee('Name');
    $response->assertSee('Email');
    $response->assertSee('Password');

    $response->assertStatus(200);
});

test('new users can register', function () {
    // Create team
    Team::factory()->create();

    $response = $this->post(route('aura.register'), [
        'name' => 'Test User',
        'team' => 'Test Team',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);

    // Assert team is created
    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
    ]);

    // Get Team
    $team = Team::where('name', 'Test Team')->first();

    // Assert a Post with type="Role" is created
    $this->assertDatabaseHas('posts', [
        'type' => 'Role',
        'team_id' => $team->id,
    ]);

    // get authenticated user
    $user = auth()->user();

    // User->current_team_id should be set to Team->id
    expect($user->current_team_id)->toBe($team->id);
});

test('registration can be disabeld', function () {

    config(['aura.features.registration' => false]);

    $response = $this->get(route('aura.register'));

    $response->assertStatus(404);
});

test('register link is not visible on login page when registration is disabled', function () {
    // Disable registration feature
    config(['aura.features.registration' => false]);

    // Visit the login page
    $response = $this->get(route('aura.login'));

    // Assert that the registration link is not visible
    $response->assertDontSee('Register.');
});

test('register link is visible on login page when registration is enabled', function () {
    // Disable registration feature
    config(['aura.features.registration' => true]);

    // Visit the login page
    $response = $this->get(route('aura.login'));

    // Assert that the registration link is not visible
    $response->assertSee('Register.');
});
