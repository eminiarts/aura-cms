<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Enable Team Registration
    config(['aura.auth.registration' => true]);
    config(['aura.auth.2fa' => false]);
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

test('login view can be rendered', function () {
    $response = $this->get(route('aura.login'));

    $response->assertStatus(200);
});

test('post login works', function () {
    // Create a user
    $user = \Aura\Base\Resources\User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    // Prepare login credentials
    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password',
    ];

    // Attempt to login
    $response = $this->post(route('aura.login'), $credentials);

    // Assert that the login was successful and the user is redirected
    $response->assertStatus(302);
    $response->assertRedirect(route('aura.dashboard'));

    // Assert that the user is authenticated
    $this->assertAuthenticated();
});

test('2FA routes exist when 2FA feature is enabled', function () {
    // Enable 2FA feature
    config(['aura.auth.2fa' => true]);

    // Check if the routes exist
    $this->assertTrue(Route::has('aura.two-factor.login'));
    $this->assertTrue(Route::has('aura.two-factor.enable'));
    $this->assertTrue(Route::has('aura.two-factor.confirm'));
    $this->assertTrue(Route::has('aura.two-factor.disable'));
    $this->assertTrue(Route::has('aura.two-factor.qr-code'));
    $this->assertTrue(Route::has('aura.two-factor.secret-key'));
    $this->assertTrue(Route::has('aura.two-factor.recovery-codes'));
});

// test('2FA routes dont exist when 2FA feature is disabled', function () {
//     // Disable 2FA feature
//     config(['aura.auth.2fa' => false]);

//     $response = $this->get(route('aura.two-factor.login'));
// });

test('2FA login works', function () {

    $this->get(route('aura.two-factor.login'))
        ->assertStatus(302);

});
