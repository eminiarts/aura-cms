<?php

namespace Aura\Base\Models\Scopes;

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
               
                // Scope for current team only
                if($currentTeamId){
                    $builder->whereHas('teams', function ($query) use ($currentTeamId) {
                        $query->where('teams.id', $currentTeamId);
                    });
                }

                self::$applying = false;

                return;  // Early return is important.
            }

            // --- Rest of your scope (for other models) ---

            // For teams disabled, no additional filtering needed for other models
            if (config('aura.teams') === false) {
                self::$applying = false;

                return;
            }

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

            // For all other models, filter by team_id
            $builder->where($model->getTable().'.team_id', $currentTeamId);

            self::$applying = false;

            return;

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
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $cacheKey = "user_{$userId}_current_team_id";

        // Cache the result indefinitely. Invalidation should happen when the user's team changes.
        return Cache::rememberForever($cacheKey, function () use ($userId) {
            // Direct database query to avoid triggering scopes
            // Use value() for slightly better performance if only one column is needed
            return DB::table('users')->where('id', $userId)->value('current_team_id');
        });
    }
}
