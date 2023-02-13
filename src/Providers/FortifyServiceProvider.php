<?php

namespace Eminiarts\Aura\Providers;

use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Features;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Eminiarts\Aura\Actions\Fortify\CreateNewUser;
use Eminiarts\Aura\Actions\Fortify\ResetUserPassword;
use Eminiarts\Aura\Actions\Fortify\UpdateUserPassword;
use Eminiarts\Aura\Actions\Fortify\UpdateUserProfileInformation;

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
            return view('auth.two-factor-challenge');
        });

        Fortify::loginView(function () {
            return view('aura::auth.login');
        });

        // Set Configuration of fortify.features to [registration, email-verification and two-factor-authentication]
        app('config')->set('fortify.features', [
            Features::registration(),
            Features::emailVerification(),
            Features::twoFactorAuthentication(),
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
