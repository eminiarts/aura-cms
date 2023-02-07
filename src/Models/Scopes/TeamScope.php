<?php

namespace Eminiarts\Aura\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        // If the Model is a Team Resource, don't apply the scope
        if ($model instanceof \App\Aura\Resources\Team) {
            return $builder->whereId(auth()->user()->current_team_id);
        }

        if ($model instanceof \App\Aura\Resources\Role) {
            // return $builder;
            return $builder->where('posts.team_id', auth()->user()->current_team_id);
        }

        // return $builder;
        if (auth()->user()) {
            return $builder->whereTeamId(auth()->user()->current_team_id);
        }

        //return $builder;
    }
}
