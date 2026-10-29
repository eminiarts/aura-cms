<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    config(['auth.providers.users.model' => User::class]);
    Auth::forgetGuards();
});

/** Logs in with "remember me" and returns the remember cookie as [name, value]. */
function rememberMeCookie(User $user): array
{
    $name = Auth::guard('web')->getRecallerName();
    $response = test()->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on']);

    return [$name, $response->getCookie($name)->getValue()];
}

/** A new browser session that carries only the remember cookie. */
function returnWithCookie(array $cookie): TestResponse
{
    test()->flushSession();
    Auth::forgetGuards();

    return test()->withCookie(...$cookie)->get(route('aura.dashboard'));
}

test('confirming two-factor authentication ends remember-me logins issued before', function () {
    $user = User::factory()->create();
    $cookie = rememberMeCookie($user);

    returnWithCookie($cookie);
    $this->assertAuthenticatedAs($user);

    app(EnableTwoFactorAuthentication::class)($user->fresh());
    $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);
    app(ConfirmTwoFactorAuthentication::class)($user->fresh(), app(Google2FA::class)->getCurrentOtp($secret));

    returnWithCookie($cookie)->assertRedirect(route('login'));
    $this->assertGuest();
});

test('turning off two-factor authentication ends remember-me logins issued before', function () {
    $user = User::factory()->create();
    $cookie = rememberMeCookie($user);

    // Enrolled without the confirmation event, so only turning it off rotates the token.
    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ])->save();
    returnWithCookie($cookie);
    $this->assertAuthenticatedAs($user);

    app(DisableTwoFactorAuthentication::class)($user->fresh());

    returnWithCookie($cookie)->assertRedirect(route('login'));
    $this->assertGuest();
});

test('the upgrade migration clears the remember token of users with confirmed two-factor authentication only', function () {
    $enrolled = User::factory()->create();
    $enrolled->forceFill(['remember_token' => 'enrolled-token', 'two_factor_confirmed_at' => now()])->save();
    $other = User::factory()->create();
    $other->forceFill(['remember_token' => 'other-token'])->save();

    (require dirname(__DIR__, 3).'/database/migrations/forget_remember_tokens_of_two_factor_users.php.stub')->up();

    expect(DB::table('users')->where('id', $enrolled->id)->value('remember_token'))->toBeNull()
        ->and(DB::table('users')->where('id', $other->id)->value('remember_token'))->toBe('other-token');
});
