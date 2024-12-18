<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resources\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {

        if (config('aura.teams') === false) {
            return $builder;
        }

         // Get user without triggering scopes
         $user = auth()->user();
        
         if (!$user) {
             return $builder;
         }
 

        $userClass = app(config('aura.resources.user'));

        // Prevent infinite loop by not applying scope to User model
        if ($model instanceof $userClass) {
            return $builder;
        }
        
        // If the Model is a Team Resource, don't apply the scope
        $teamClass = app(config('aura.resources.team'));
        
        if (auth()->user() && $model instanceof $teamClass) {
            // For Now
            return $builder;
            // return $builder->whereId(auth()->user()->current_team_id);
        }

        // Get user without triggering scopes
        $user = auth()->user();
        
        if (!$user) {
            return $builder;
        }

        // Get the current_team_id directly from the user attributes to avoid scope
        $currentTeamId = $user->getAttribute('current_team_id');
        
        if ($model->getTable() == 'posts') {
            return $builder->where($model->getTable().'.team_id', $currentTeamId);
        }

        // Temporary Fix
        if($model->getTable() == 'teams') {
            return $builder;
        }

        // Temporary Fix
        if($model->getTable() == 'users') {
            return $builder;
        }

        return $builder->where($model->getTable().'.team_id', $currentTeamId);
    }
}
