<?php

namespace Aura\Base\Http\Responses;

use Aura\Base\Events\LoggedIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents a completed two factor login.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        event(new LoggedIn($request->user()));

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(config('aura.auth.redirect'));
    }
}
