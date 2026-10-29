<?php

namespace Aura\Base\Http\Middleware;

use Aura\Base\Resources\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureResourceEditorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app()->environment(['local', 'testing']) && config('aura.features.resource_editor'),
            404
        );

        abort_unless(Gate::allows(User::GLOBAL_ADMIN_GATE), 403);

        return $next($request);
    }
}
