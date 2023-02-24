<?php

namespace Tests\Feature\Auth;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Providers\RouteServiceProvider;

// uses()->group('current');

beforeEach(function () {
    // Enable Team Registration
    Aura::setOption('team_registration', true);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertSee('Team');
    $response->assertSee('Name');
    $response->assertSee('Email');
    $response->assertSee('Password');

    $response->assertStatus(200);
});

test('new users can register', function () {
    // Create team
    Team::factory()->create();

    $response = $this->post(route('register'), [
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
