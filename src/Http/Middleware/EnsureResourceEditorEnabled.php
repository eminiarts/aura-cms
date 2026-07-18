<?php

namespace Aura\Base\Http\Middleware;

use Aura\Base\Resources\User;
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

        $user = $request->user();

        abort_unless($user instanceof User && $user->isSuperAdmin(), 403);

        return $next($request);
    }
}
