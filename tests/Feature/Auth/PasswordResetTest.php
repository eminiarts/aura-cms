<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);
});

describe('Password Reset Link Request', function () {
    test('forgot password screen renders successfully', function () {
        $this->get(route('aura.password.request'))
            ->assertSuccessful()
            ->assertSee('Forgot')
            ->assertSee('Email');
    });

    test('reset password link can be requested', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    test('reset password link is not sent for non-existent email', function () {
        Notification::fake();

        $this->post(route('aura.password.email'), ['email' => 'nonexistent@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });

    test('email is required for password reset request', function () {
        $this->post(route('aura.password.email'), [])
            ->assertSessionHasErrors('email');
    });

    test('email must be valid format for password reset request', function () {
        $this->post(route('aura.password.email'), ['email' => 'invalid-email'])
            ->assertSessionHasErrors('email');
    });
});

describe('Password Reset Screen', function () {
    test('reset password screen renders with valid token', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $this->get(route('aura.password.reset', ['token' => $notification->token]))
                ->assertSuccessful();

            return true;
        });
    });
});

describe('Password Reset', function () {
    test('password can be reset with valid token', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('aura.password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
                ->assertSessionHasNoErrors();

            return true;
        });

        $user->refresh();
        expect(Hash::check('new-password', $user->password))->toBeTrue();
    });

    test('password reset event is dispatched', function () {
        Event::fake([PasswordReset::class]);
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('aura.password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            return true;
        });

        Event::assertDispatched(PasswordReset::class);
    });

    test('password reset fails with invalid token', function () {
        $user = User::factory()->create();

        $this->post(route('aura.password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('password reset fails with wrong email', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $this->post(route('aura.password.store'), [
                'token' => $notification->token,
                'email' => 'wrong@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
                ->assertSessionHasErrors('email');

            return true;
        });
    });

    test('password confirmation is required', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('aura.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post(route('aura.password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])
                ->assertSessionHasErrors('password');

            return true;
        });
    });

    test('all fields are required for password reset', function () {
        $this->post(route('aura.password.store'), [])
            ->assertSessionHasErrors(['token', 'email', 'password']);
    });
});

test('reset password link can be requested with uppercase email', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->post(route('aura.password.email'), ['email' => 'TEST@EXAMPLE.COM']);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password link can be requested with mixed case email', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    $this->post(route('aura.password.email'), ['email' => 'Test@Example.COM']);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password can be reset with uppercase email', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    // Request with lowercase (will work)
    $this->post(route('aura.password.email'), ['email' => 'test@example.com']);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        // Reset with uppercase email
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasNoErrors();

        return true;
    });
});
