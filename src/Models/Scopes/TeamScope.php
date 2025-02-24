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

        // Don't apply scope in console
        if (app()->runningInConsole()) {
            return $builder;
        }

        $user = auth()->user();
        if (!$user) {
            return $builder;
        }

        $currentTeamId = $user->getAttribute('current_team_id');
        
        // For User model, filter by role team_id
        if ($model->getTable() === 'users') {
            return $builder->whereHas('roles', function ($query) use ($currentTeamId) {
                $query->where('roles.team_id', $currentTeamId);
            });
        }

        // For Team model, don't apply scope
        if ($model->getTable() === 'teams') {
            return $builder;
        }

        // For all other models
        return $builder->where($model->getTable().'.team_id', $currentTeamId);
    }
}
