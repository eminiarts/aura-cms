<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\Team;

beforeEach(function () {
    // Enable Team Registration
    config(['aura.auth.registration' => true]);
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

    $this->withoutExceptionHandling();

    $response = $this->post(route('aura.register'), [
        'name' => 'Test User',
        'team' => 'Test Team',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(config('aura.auth.redirect'));

    // Assert team is created
    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
    ]);

    // Get Team
    $team = Team::where('name', 'Test Team')->first();

    // Assert a Post with type="Role" is created
    $this->assertDatabaseHas('roles', [
        'team_id' => $team->id,
    ]);

    // get authenticated user
    $user = auth()->user();

    // User->current_team_id should be set to Team->id
    expect($user->current_team_id)->toBe($team->id);
});

test('registration can be disabeld', function () {

    config(['aura.auth.registration' => false]);

    $response = $this->get(route('aura.register'));

    $response->assertStatus(404);
});

test('register link is not visible on login page when registration is disabled', function () {
    // Disable registration feature
    config(['aura.auth.registration' => false]);

    // Visit the login page
    $response = $this->get(route('aura.login'));

    // Assert that the registration link is not visible
    $response->assertDontSee('Register.');
});

test('register link is visible on login page when registration is enabled', function () {
    // Disable registration feature
    config(['aura.auth.registration' => true]);

    // Visit the login page
    $response = $this->get(route('aura.login'));

    // Assert that the registration link is not visible
    $response->assertSee('Register.');
});

test('register with team shows team input', function () {
    // Disable registration feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => true]);

    // Visit the login page
    $response = $this->get(route('aura.register'));

    // Assert that the registration link is not visible
    $response->assertSee('Team');
});

test('register without team does not show team input', function () {
    // Disable registration feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => false]);

    // Visit the login page
    $response = $this->get(route('aura.register'));

    // Assert that the registration link is not visible
    $response->assertDontSee('Team');
});

test('register with team creates team and user', function () {
    // Enable registration and teams feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => true]);

    // Prepare input data
    $data = [
        'name' => 'Test User',
        'team' => 'Test Team',
        'email' => 'testuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    // Send POST request to register endpoint
    $response = $this->post(route('aura.register'), $data);

    // Validate input
    $response->assertSessionHasNoErrors();

    // Assert user creation
    $this->assertDatabaseHas('users', [
        'email' => 'testuser@example.com',
        'name' => 'Test User',
    ]);

    // Assert team creation
    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
    ]);

    // Assert user is assigned to the team
    $user = \Aura\Base\Resources\User::where('email', 'testuser@example.com')->first();
    $this->assertEquals('Test Team', $user->currentTeam->name);
});

test('register with team validates input', function () {
    // Enable registration and teams feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => true]);

    // Test with empty input data
    $data = [];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['name', 'team', 'email', 'password']);

    // Test with missing team
    $data = [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
    $response = $this->post(route('aura.register'), $data);

    $response->assertSessionHasErrors(['team']);

    // Test with invalid email
    $data = [
        'name' => 'Test User',
        'team' => 'Test Team',
        'email' => 'invalid-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['email']);

    // Test with password mismatch
    $data = [
        'name' => 'Test User',
        'team' => 'Test Team',
        'email' => 'testuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['password']);
});

test('register without team validates input', function () {
    // Enable registration and teams feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => false]);

    // Test with empty input data
    $data = [];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['name', 'email', 'password']);

    // Test with missing email
    $data = [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['email']);

    // Test with invalid email
    $data = [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['email']);

    // Test with password mismatch
    $data = [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ];
    $response = $this->post(route('aura.register'), $data);
    $response->assertSessionHasErrors(['password']);
});

test('register without team creates user', function () {
    // Enable registration and teams feature
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => false]);

    // Prepare input data
    $data = [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    // Send POST request to register endpoint
    $response = $this->post(route('aura.register'), $data);

    // Validate input
    $response->assertSessionHasNoErrors();

    // Assert user creation
    $this->assertDatabaseHas('users', [
        'email' => 'testuser@example.com',
        'name' => 'Test User',
    ]);

    // Assert team creation
    $this->assertDatabaseMissing('teams', [
        'name' => 'Test Team',
    ]);

});
