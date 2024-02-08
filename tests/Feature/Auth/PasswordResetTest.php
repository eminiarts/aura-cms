<?php

namespace Tests\Feature\Auth;

use Eminiarts\Aura\Resources\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('aura.password.request'));

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create();

    $this->resource(route('aura.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create();

    $this->resource(route('aura.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    // Set the user model in auth config to use the one from the package
    config(['auth.providers.users.model' => User::class]);

    Notification::fake();

    $user = User::factory()->create();

    $this->resource(route('aura.password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->resource('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        return true;
    });
});
