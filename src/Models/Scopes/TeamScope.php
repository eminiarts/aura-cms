<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resources\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // Don't apply scope in console
        // if (app()->runningInConsole()) {
        //     return $builder;
        // }

        // Get current user
        $user = Auth::user();
        if (!$user) {
            return $builder;
        }

        // Handle User model specially
        if ($model->getTable() === 'users') {
            // If teams are disabled, don't filter by team_id
            if (config('aura.teams') === false) {
                // In this case, users can see themselves only by default
                return $builder->where('id', $user->id);
            }
            
            // With teams enabled, filter by team_id in user_role
            $currentTeamId = $user->current_team_id;
            if (!$currentTeamId) {
                return $builder;
            }
            
            // Filter users by their team roles
            return $builder->whereHas('roles', function ($query) use ($currentTeamId) {
                $query->where('user_role.team_id', $currentTeamId);
            });
        }
        
        // For teams disabled, no additional filtering needed for other models
        if (config('aura.teams') === false) {
            return $builder;
        }
        
        // Get current team ID for team-enabled filtering
        $currentTeamId = $user->current_team_id;
        if (!$currentTeamId) {
            return $builder;
        }

        // For Team model, don't apply team scope
        if ($model->getTable() === 'teams') {
            return $builder;
        }

        // For all other models, filter by team_id
        return $builder->where($model->getTable().'.team_id', $currentTeamId);
    }
}
