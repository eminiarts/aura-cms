<?php

namespace Aura\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResourceEditorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app()->environment(['local', 'testing']) && config('aura.features.resource_editor'),
            404
        );

        return $next($request);
    }
}
