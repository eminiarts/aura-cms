<?php

namespace Aura\Base\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ScopedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model instanceof \Aura\Base\Resources\Role) {
            return $builder;
        }
        if ($model instanceof \Aura\Base\Resources\User) {
            return $builder;
        }

        // Superadmin
        if (auth()->user() && auth()->user() instanceof \Aura\Base\Resources\User && auth()->user()->isSuperAdmin()) {
            return $builder;
        }

        if (auth()->user() && auth()->user() instanceof \Aura\Base\Resources\User && auth()->user()->hasPermissionTo('scope', $model)) {
            $builder->where($model->getTable().'.user_id', auth()->user()->id);
        }

        // Check access?
        return $builder;
    }
}
