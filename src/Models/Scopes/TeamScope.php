<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            $currentTeamId = $this->getCurrentTeamId();
            $userId = Auth::id();

            // Handle User model specially
            if ($model->getTable() === 'users') {

                // Only apply team scoping if teams are enabled
                if (config('aura.teams') && $currentTeamId) {
                    $builder->whereHas('teams', function ($query) use ($currentTeamId) {
                        $query->where('teams.id', $currentTeamId);
                    });
                }

                self::$applying = false;

                return;  // Early return is important.
            }

            // --- Rest of your scope (for other models) ---

            // For team-enabled filtering
            if (! $currentTeamId) {
                self::$applying = false;

                return;
            }

            // For Team model, don't apply team scope
            if ($model->getTable() === 'teams') {
                self::$applying = false;

                return;
            }

            // Roles resolve against the Role Catalog: within a team, queries see
            // both the team's own Team Roles and the shared Global Roles
            // (team_id = null). The merged/de-duplicated Roles UI is handled
            // elsewhere; here we only make Global Roles visible at the query
            // layer. Shadow resolution itself goes through Role::resolveForTeam.
            if ($model->getTable() === 'roles') {
                $builder->where(function ($query) use ($currentTeamId) {
                    $query->where('roles.team_id', $currentTeamId)
                        ->orWhereNull('roles.team_id');
                });

                self::$applying = false;

                return;
            }

            // For all other models, filter by team_id
            $builder->where($model->getTable().'.team_id', $currentTeamId);

            self::$applying = false;

            return;

        } catch (\Exception $e) {
            self::$applying = false;
            throw $e;
        }
    }

    public static function flushState(): void
    {
        self::$applying = false;
    }

    /**
     * Get the current team ID without triggering the scope again.
     *
     * @return int|null
     */
    private function getCurrentTeamId()
    {
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
