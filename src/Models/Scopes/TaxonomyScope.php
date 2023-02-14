<?php

namespace Eminiarts\Aura\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TaxonomyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model::getType()) {
            return $builder->where('taxonomy', $model::getType());
        }

        return $builder;
    }
}
