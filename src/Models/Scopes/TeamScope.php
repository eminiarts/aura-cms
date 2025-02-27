<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resources\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
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
        // Prevent recursive calls
        if (self::$applying) {
            return $builder;
        }

        // Don't apply scope in console
        // if (app()->runningInConsole()) {
        //     return $builder;
        // }

        // Set the flag to prevent recursive calls
        self::$applying = true;

        try {
            // Get current team ID directly from database without triggering the scope again
            $currentTeamId = $this->getCurrentTeamId();
            $userId = Auth::id(); // Use Auth::id() instead of Auth::user() to avoid loading the full model

            // Handle User model specially
            if ($model->getTable() === 'users') {
                // If teams are disabled, don't filter by team_id
                if (config('aura.teams') === false) {
                    // In this case, users can see themselves only by default
                    if ($userId) {
                        $builder->where('id', $userId);
                    }

                    self::$applying = false;

                    return $builder;
                }

                // With teams enabled, filter by team_id in user_role
                if (! $currentTeamId) {
                    self::$applying = false;

                    return $builder;
                }

                // Filter users by their team roles
                $builder->whereHas('roles', function ($query) use ($currentTeamId) {
                    $query->where('user_role.team_id', $currentTeamId);
                });

                self::$applying = false;

                return $builder;
            }

            // For teams disabled, no additional filtering needed for other models
            if (config('aura.teams') === false) {
                self::$applying = false;

                return $builder;
            }

            // For team-enabled filtering
            if (! $currentTeamId) {
                self::$applying = false;

                return $builder;
            }

            // For Team model, don't apply team scope
            if ($model->getTable() === 'teams') {
                self::$applying = false;

                return $builder;
            }

            // For all other models, filter by team_id
            $builder->where($model->getTable().'.team_id', $currentTeamId);

            self::$applying = false;

            return $builder;
        } catch (\Exception $e) {
            self::$applying = false;
            throw $e;
        }
    }

    /**
     * Get the current team ID without triggering the scope again.
     *
     * @return int|null
     */
    private function getCurrentTeamId()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            // Direct database query to avoid triggering scopes
            $user = DB::table('users')->where('id', $userId)->first();

            if ($user && isset($user->current_team_id)) {
                return $user->current_team_id;
            }
        }

    }
}
