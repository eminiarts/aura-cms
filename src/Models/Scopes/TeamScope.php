<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Support\TeamExecutionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamScope implements Scope
{
    // Static flag to prevent recursive calls
    private static $applying = false;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! config('aura.teams')) {
            return;
        }

        // Prevent recursive calls
        if (self::$applying) {
            return;
        }

        // Don't apply scope in console (optional, as you commented it out)
        // if (app()->runningInConsole()) {
        //     return;
        // }

        self::$applying = true;

        try {
            // Guests are not in a team context. Fail-closed is for authenticated
            // actors missing a current team — never for unauthenticated requests.
            // Guest flows (login, password reset, invitation register, registration
            // role attach) must resolve models; applying 1=0 here 404/403s all of them.
            // Admin/resource routes still require auth middleware, so skipping the
            // scope for guests does not open team data to the public.
            if (! Auth::check()) {
                return;
            }

            $currentTeamId = $this->getCurrentTeamId();
            $userId = Auth::id();

            // Handle User model specially
            if ($model->getTable() === 'users') {
                // Match User::indexQuery: Global Admins only bypass the users
                // boundary in the global area or outside HTTP.
                $authUser = Auth::user();
                $isGlobalAdmin = Aura::area() !== 'team'
                    && $authUser && Gate::forUser($authUser)->allows(User::GLOBAL_ADMIN_GATE);

                if ($currentTeamId) {
                    if (! $isGlobalAdmin) {
                        // The own row always stays visible: the session guard
                        // resolves the signed-in user through this scope, and a
                        // visiting Global Admin is not a member of the team.
                        $builder->where(function (Builder $query) use ($currentTeamId, $userId) {
                            $query->whereHas('teams', function ($query) use ($currentTeamId) {
                                $query->where('teams.id', $currentTeamId);
                            })->orWhere($query->getModel()->getQualifiedKeyName(), $userId);
                        });
                    }
                } elseif (! $isGlobalAdmin) {
                    // No team context: fail closed for ordinary authenticated
                    // users by restricting to the authenticated identity only.
                    $builder->whereKey($authUser->getKey());
                }

                return;  // Early return is important.
            }

            // For Team model, don't apply team scope (leave unscoped as today)
            if ($model->getTable() === 'teams') {
                return;
            }

            // Fail-closed: without a current team, never return unscoped rows.
            // Roles previously needed currentTeamId for scopeVisibleToTeam; when
            // it is missing, refuse the query rather than exposing the catalog.
            if (! $currentTeamId) {
                $builder->whereRaw('1 = 0');

                return;
            }

            // Roles resolve against the Role Catalog: within a team, queries see
            // both the team's own Team Roles and the shared Global Roles
            // (team_id = null). The merged/de-duplicated Roles UI is handled
            // elsewhere; here we only make Global Roles visible at the query
            // layer. Shadow resolution itself goes through Role::resolveForTeam.
            if ($model instanceof Role) {
                $model->scopeVisibleToTeam($builder, $currentTeamId);

                return;
            }

            // For all other models, filter by team_id
            $builder->where($model->getTable().'.team_id', $currentTeamId);
        } finally {
            self::$applying = false;
        }
    }

    public static function flushState(): void
    {
        self::$applying = false;
        TeamExecutionContext::clear();
    }

    /**
     * Get the current team ID without triggering the scope again.
     *
     * @return int|null
     */
    private function getCurrentTeamId()
    {
        if (TeamExecutionContext::active()) {
            return TeamExecutionContext::currentTeamId();
        }

        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $cacheKey = User::currentTeamCacheKey($userId);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Direct database query to avoid triggering scopes.
        $currentTeamId = DB::table('users')->where('id', $userId)->value('current_team_id');

        if ($currentTeamId !== null) {
            Cache::forever($cacheKey, $currentTeamId);
        }

        return $currentTeamId;
    }
}
