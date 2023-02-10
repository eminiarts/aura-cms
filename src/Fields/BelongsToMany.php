<?php

namespace Eminiarts\Aura\Fields;

class BelongsToMany extends Field
{
    public string $component = 'aura::fields.hasmany';

    public bool $group = true;

    public string $type = 'relation';

    protected string $view = 'components.fields.hasmany';

    public function queryFor($model, $query)
    {
        if ($model instanceof \Eminiarts\Aura\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \Eminiarts\Aura\Resources\Team) {
            return $query;
        }

        return $query->where('user_id', $model->id);
    }
}
