<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Events\LoggedIn;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return View
     */
    public function create()
    {
        return view('aura::auth.login');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->normalizeEmail();
        $request->ensureIsNotRateLimited();

        $response = app(RedirectsIfTwoFactorAuthenticatable::class)->handle($request, function (LoginRequest $request) {
            $request->authenticate();

            $request->session()->regenerate();

            event(new LoggedIn($request->user()));

            return redirect()->intended(config('aura.auth.redirect'));
        });

        RateLimiter::clear($request->throttleKey());

        return $response;
    }
}
