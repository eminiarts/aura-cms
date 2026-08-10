<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TypeScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model instanceof Resource) {
            return;
        }

        $column = $model::getInheritanceColumn();
        $value = $model::getInheritanceValue();

        if ($column === null || $value === null) {
            return;
        }

        $builder->where($model->qualifyColumn($column), $value);
    }
}
