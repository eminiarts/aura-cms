<?php

namespace Aura\Base\Providers;

use Aura\Base\Http\Responses\TwoFactorLoginResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::twoFactorChallengeView(function () {
            return view('aura::auth.two-factor-challenge');
        });

        Fortify::loginView(function () {
            return view('aura::auth.login');
        });

        Fortify::ignoreRoutes();

        // Password reset link in email template...
        ResetPassword::createUrlUsing(static function ($notifiable, $token) {
            return route('aura.password.reset', $token);
        });

        VerifyEmail::createUrlUsing(static function ($notifiable): string {
            return URL::temporarySignedRoute(
                'aura.verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        // Set Configuration of fortify.features to [registration, email-verification and two-factor-authentication]
        app('config')->set('fortify.features', [
            // Features::registration(),
            Features::emailVerification(),
            Features::twoFactorAuthentication([
                'confirm' => true,
                'confirmPassword' => true,
                // 'window' => 0,
            ]),
            // Features::confirmsTwoFactorAuthentication(),
        ]);

        app('config')->set('fortify.redirects.login', config('aura.auth.redirect'));
        // app('config')->set('fortify.views', false);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthenticationProviderContract::class, function ($app) {
            return new TwoFactorAuthenticationProvider(
                $app->make(Google2FA::class),
                $app->make(Repository::class)
            );
        });
    }
}
