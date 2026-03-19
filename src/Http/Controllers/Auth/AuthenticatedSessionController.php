<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Events\LoggedIn;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
        // Here we need to handle 2FA from Laravel Fortify

        $request->authenticate();

        $request->session()->regenerate();

        // Event LoggedIn
        event(new LoggedIn($request->user()));

        return redirect()->intended(config('aura.auth.redirect'));
    }
}
