<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

describe('Email Verification Notice', function () {
    test('verification notice screen renders for unverified user', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('aura.verification.notice'))
            ->assertSuccessful();
    });

    test('verified user is redirected from verification notice', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('aura.verification.notice'))
            ->assertRedirect(config('aura.auth.redirect'));
    });

    test('guest is redirected from verification notice', function () {
        $this->get(route('aura.verification.notice'))
            ->assertRedirect();
    });
});

describe('Email Verification', function () {
    test('email can be verified with valid link', function () {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'aura.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(config('aura.auth.redirect').'?verified=1');

        Event::assertDispatched(Verified::class);
        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    test('email is not verified with invalid hash', function () {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'aura.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    test('email verification is not triggered for already verified user', function () {
        Event::fake([Verified::class]);

        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'aura.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(config('aura.auth.redirect').'?verified=1');

        Event::assertNotDispatched(Verified::class);
    });

    test('email verification requires valid signature', function () {
        $user = User::factory()->unverified()->create();

        $invalidUrl = route('aura.verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($invalidUrl)
            ->assertForbidden();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });
});

describe('Email Verification Notification', function () {
    test('verification notification can be resent', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('aura.verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    });

    test('verified user is redirected when requesting verification email', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('aura.verification.send'))
            ->assertRedirect(config('aura.auth.redirect'));

        Notification::assertNothingSent();
    });

    test('guest cannot request verification notification', function () {
        $this->post(route('aura.verification.send'))
            ->assertRedirect();
    });
});
