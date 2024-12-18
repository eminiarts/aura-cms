<?php

namespace Aura\Base\Models\Scopes;

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

        // return $builder;

        // If the Model is a Team Resource, don't apply the scope
        $teamClass = app(config('aura.resources.team'));
        
        // if (auth()->user() && $model instanceof $teamClass) {
        //     return $builder->whereId(auth()->user()->current_team_id);
        // }

        // if (auth()->user() && $model instanceof \Aura\Base\Resources\Role) {
        //     return $builder->where('posts.team_id', auth()->user()->current_team_id);
        // }

       // return $builder;

        // if (auth()->user() && $model->getTable() == 'posts') {
        //     return $builder->where('posts.team_id', auth()->user()->current_team_id);
        // }

        // if(auth()->guest()) {
        //     return $builder;
        // }

        return $builder;

    dd(auth()->user()->current_team_id);

        if (auth()->user() && $model->getTable() == 'posts') {
            return $builder->where($model->getTable().'.team_id', auth()->user()->current_team_id);
        }

        if (auth()->user()) {
            return $builder->where($model->getTable().'.team_id', auth()->user()->current_team_id);
        }

        // Check access?
        return $builder;
    }
}
