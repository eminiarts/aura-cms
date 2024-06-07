<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(config('aura.auth.redirect'))
                    : view('aura::auth.verify-email');
    }
}
