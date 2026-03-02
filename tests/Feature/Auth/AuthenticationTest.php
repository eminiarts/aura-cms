<?php

namespace Tests\Feature\Auth;

use Aura\Base\Events\LoggedIn;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['aura.auth.registration' => true]);
});

describe('Login Screen', function () {
    test('login page renders successfully', function () {
        $this->get(route('aura.login'))
            ->assertSuccessful()
            ->assertSee('Login')
            ->assertSee('Email')
            ->assertSee('Password');
    });

    test('login page shows remember me checkbox', function () {
        $this->get(route('aura.login'))
            ->assertSee('Remember me');
    });

    test('login page shows forgot password link', function () {
        $this->get(route('aura.login'))
            ->assertSee('Forgot your password?');
    });

    test('login page shows registration link when enabled', function () {
        $this->get(route('aura.login'))
            ->assertSee('Register.');
    });

    test('login page hides registration link when disabled', function () {
        config(['aura.auth.registration' => false]);

        $this->get(route('aura.login'))
            ->assertDontSee('Register.');
    });
});

describe('Authentication', function () {
    test('user can authenticate with valid credentials', function () {
        $user = User::factory()->create();

        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(config('aura.auth.redirect'));

        $this->assertAuthenticated();
        expect(auth()->id())->toBe($user->id);
    });

    test('user cannot authenticate with invalid password', function () {
        $user = User::factory()->create();

        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('user cannot authenticate with non-existent email', function () {
        $this->post(route('aura.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('logged in event is dispatched on successful authentication', function () {
        Event::fake([LoggedIn::class]);

        $user = User::factory()->create();

        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Event::assertDispatched(LoggedIn::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    });

    test('session is regenerated after login', function () {
        $user = User::factory()->create();
        $oldSessionId = session()->getId();

        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        expect(session()->getId())->not->toBe($oldSessionId);
    });
});

describe('Validation', function () {
    test('email is required', function () {
        $this->post(route('aura.login'), [
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('password is required', function () {
        $this->post(route('aura.login'), [
            'email' => 'test@example.com',
        ])
            ->assertSessionHasErrors('password');
    });

    test('email must be valid format', function () {
        $this->post(route('aura.login'), [
            'email' => 'invalid-email',
            'password' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });
});

describe('Rate Limiting', function () {
    test('user is rate limited after too many failed attempts', function () {
        Event::fake([Lockout::class]);
        $user = User::factory()->create();

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('aura.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // The 6th attempt should be rate limited
        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        Event::assertDispatched(Lockout::class);
    });

    test('rate limit is cleared after successful login', function () {
        $user = User::factory()->create();

        // Make some failed attempts (but not enough to trigger lockout)
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('aura.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // Successful login should clear rate limit
        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    });
});

describe('Remember Me', function () {
    test('user can login with remember me option', function () {
        $user = User::factory()->create();

        $this->post(route('aura.login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])
            ->assertRedirect(config('aura.auth.redirect'));

        $this->assertAuthenticated();
    });
});

describe('Logout', function () {
    test('authenticated user can logout', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('aura.logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });

    test('session is invalidated on logout', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $oldSessionId = session()->getId();

        $this->get(route('aura.logout'));

        expect(session()->getId())->not->toBe($oldSessionId);
    });
});
