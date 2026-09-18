<?php

namespace Aura\Base\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // JSON callers get a 401 instead of a redirect, so there is no target.
        return $request->expectsJson() ? null : route('login');
    }
}
