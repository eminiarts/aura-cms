<?php

namespace Eminiarts\Aura\Providers;

use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Illuminate\Contracts\Cache\Repository;
use PragmaRX\Google2FA\Google2FA;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        // dd(app('config')->get('fortify'), app('config')->get('fortify-options'));


        // Set Configuration of fortify.features to [registration, email-verification and two-factor-authentication]
        app('config')->set('fortify.features', [
            //Features::registration(),
            //Features::emailVerification(),
            Features::twoFactorAuthentication([
                'confirm' => true,
                'confirmPassword' => true,
                // 'window' => 0,
            ]),
            // Features::confirmsTwoFactorAuthentication(),
        ]);

        // Set Configuration of fortify.redirects.login to /admin/dashboard
        app('config')->set('fortify.redirects.login', '/admin/dashboard');
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
