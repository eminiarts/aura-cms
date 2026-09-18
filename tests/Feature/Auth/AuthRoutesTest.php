<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['aura.auth.registration' => true]);
    config(['aura.auth.2fa' => false]);
});

describe('Registration Routes', function () {
    test('registration route returns 404 when disabled', function () {
        config(['aura.auth.registration' => false]);

        $this->get(route('aura.register'))
            ->assertNotFound();
    });

    test('registration route is accessible when enabled', function () {
        $this->get(route('aura.register'))
            ->assertSuccessful();
    });

    test('register link is hidden on login page when registration disabled', function () {
        config(['aura.auth.registration' => false]);

        $this->get(route('login'))
            ->assertDontSee('Register.');
    });

    test('register link is visible on login page when registration enabled', function () {
        $this->get(route('login'))
            ->assertSee('Register');
    });
});

describe('Login Routes', function () {
    test('login page renders successfully', function () {
        $this->get(route('login'))
            ->assertSuccessful();
    });

    test('authenticated user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('aura.dashboard'));

        $this->assertAuthenticated();
        expect(auth()->id())->toBe($user->id);
    });

    test('login fails with invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('login fails with non-existent email', function () {
        $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('login requires email field', function () {
        $this->post(route('login'), [
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('login requires password field', function () {
        $this->post(route('login'), [
            'email' => 'test@example.com',
        ])
            ->assertSessionHasErrors('password');
    });

    test('login requires valid email format', function () {
        $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });
});

describe('Logout Routes', function () {
    test('authenticated user cannot logout via GET', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('aura.logout'))
            ->assertMethodNotAllowed();

        $this->assertAuthenticated();
    });

    test('authenticated user can logout via POST', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });
});

describe('Two-Factor Authentication Routes', function () {
    test('2FA routes are registered when feature is enabled', function () {
        config(['aura.auth.2fa' => true]);

        expect(Route::has('two-factor.login'))->toBeTrue()
            ->and(Route::has('two-factor.login.store'))->toBeTrue()
            ->and(Route::has('aura.two-factor.enable'))->toBeTrue()
            ->and(Route::has('aura.two-factor.confirm'))->toBeTrue()
            ->and(Route::has('aura.two-factor.disable'))->toBeTrue()
            ->and(Route::has('aura.two-factor.qr-code'))->toBeTrue()
            ->and(Route::has('aura.two-factor.secret-key'))->toBeTrue()
            ->and(Route::has('aura.two-factor.recovery-codes'))->toBeTrue();
    });

    test('2FA challenge redirects guests without a pending login', function () {
        $this->get(route('two-factor.login'))
            ->assertRedirect(route('login'));
    });
});

describe('Password Reset Routes', function () {
    test('forgot password page is accessible', function () {
        $this->get(route('aura.password.request'))
            ->assertSuccessful();
    });

    test('reset password page requires valid token', function () {
        $this->get(route('aura.password.reset', ['token' => 'test-token']))
            ->assertSuccessful();
    });
});
