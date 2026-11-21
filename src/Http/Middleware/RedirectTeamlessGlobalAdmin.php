<?php

namespace Aura\Base\Http\Middleware;

use Aura\Base\Resources\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class RedirectTeamlessGlobalAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('aura.teams') && Gate::allows('AuraGlobalAdmin') && $user instanceof User && ! $user->currentTeam) {
            return redirect()->route('aura.global.index');
        }

        return $next($request);
    }
}
