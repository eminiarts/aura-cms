<?php

namespace Tests\Feature\Auth;

use Aura\Base\Events\LoggedIn;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RoutePath;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    config(['auth.providers.users.model' => User::class]);
    Auth::forgetGuards();
});

test('two factor enrollment redirects to Aura password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('aura.two-factor.enable'))
        ->assertRedirect(route('aura.password.confirm'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

function createConfirmedTwoFactorUser(string $recoveryCode = 'recovery-code'): array
{
    $secret = 'JBSWY3DPEHPK3PXP';
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode([$recoveryCode])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return [$user, $secret, $recoveryCode];
}

test('a confirmed two factor user is challenged after a valid password before authentication', function () {
    Event::fake([LoggedIn::class]);

    [$user] = createConfirmedTwoFactorUser();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect('/two-factor-challenge')
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
    Event::assertNotDispatched(LoggedIn::class);
});

test('the challenge is available to the pending guest and missing challenge state returns to login', function () {
    [$user] = createConfirmedTwoFactorUser();

    $this->withSession(['login.id' => $user->id])
        ->get(route('two-factor.login'))
        ->assertSuccessful()
        ->assertSee('Two-factor authentication');

    $this->flushSession();

    $this->get(route('two-factor.login'))
        ->assertRedirect(route('login'));
});

test('an invalid OTP leaves the user unauthenticated', function () {
    Event::fake([LoggedIn::class]);
    [$user] = createConfirmedTwoFactorUser();

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.login'), ['code' => '000000'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();
    Event::assertNotDispatched(LoggedIn::class);
});

test('OTP failures are throttled when the host does not configure a two factor limiter', function () {
    config(['fortify.limiters' => ['login' => null, 'passkeys' => null]]);

    require __DIR__.'/../../../routes/web.php';

    [$user] = createConfirmedTwoFactorUser();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->withSession(['login.id' => $user->id])
            ->post(route('two-factor.login'), ['code' => '000000'])
            ->assertRedirect(route('two-factor.login'));
    }

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.login'), ['code' => '000000'])
        ->assertStatus(429);
});

test('a host configured two factor limiter overrides the Aura default', function () {
    config(['fortify.limiters.two-factor' => 'custom-two-factor']);

    require __DIR__.'/../../../routes/web.php';

    $path = ltrim(RoutePath::for('two-factor.login', '/two-factor-challenge'), '/');

    expect(Route::getRoutes()->get('POST')[$path]->gatherMiddleware())
        ->toContain('throttle:custom-two-factor');
});

test('a valid OTP completes authentication, preserves the intended redirect and remember request', function () {
    Event::fake([LoggedIn::class]);
    [$user, $secret] = createConfirmedTwoFactorUser();

    $response = $this->withSession([
        'login.id' => $user->id,
        'login.remember' => true,
        'url.intended' => '/protected-destination',
    ])->post(route('two-factor.login'), [
        'code' => app(Google2FA::class)->getCurrentOtp($secret),
    ]);

    $response
        ->assertRedirect('/protected-destination')
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
    Event::assertDispatched(LoggedIn::class, fn (LoggedIn $event) => $event->user->is($user));
});

test('a recovery code completes authentication and uses the configured Aura redirect', function () {
    Event::fake([LoggedIn::class]);
    config(['aura.auth.redirect' => '/control']);
    [$user, , $recoveryCode] = createConfirmedTwoFactorUser();

    $this->withSession(['login.id' => $user->id])
        ->post(route('two-factor.login'), ['recovery_code' => $recoveryCode])
        ->assertRedirect('/control');

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
    expect($user->fresh()->recoveryCodes())->not->toContain($recoveryCode);
    Event::assertDispatched(LoggedIn::class, fn (LoggedIn $event) => $event->user->is($user));
});

test('a pending unconfirmed two factor secret does not require a challenge', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(config('aura.auth.redirect'));

    $this->assertAuthenticated();
    expect(Auth::id())->toBe($user->id);
});

test('turning off Aura two factor management does not bypass an enrolled user second factor', function () {
    Event::fake([LoggedIn::class]);
    config(['aura.auth.2fa' => false]);
    [$user] = createConfirmedTwoFactorUser();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect('/two-factor-challenge')
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
    Event::assertNotDispatched(LoggedIn::class);
});
