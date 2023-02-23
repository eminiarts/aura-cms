<?php

namespace Tests\Feature\Auth;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Providers\RouteServiceProvider;

beforeEach(function () {
    // Enable Team Registration
    Aura::setOption('team_registration', true);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});
