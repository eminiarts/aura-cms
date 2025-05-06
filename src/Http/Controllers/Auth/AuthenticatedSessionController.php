<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Events\LoggedIn;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('aura::auth.login');
    }

    /**
     * Destroy an authenticated session.
     *
     * @return \Illuminate\Http\RedirectResponse
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // Here we need to handle 2FA from Laravel Fortify

        $request->authenticate();

        $request->session()->regenerate();

        // Event LoggedIn
        event(new LoggedIn($request->user()));

        return redirect()->intended(config('aura.auth.redirect'));
    }
}
