<?php

namespace Eminiarts\Aura\Aura\Fields;

class BelongsToMany extends Field
{
    public string $component = 'fields.hasmany';

    public bool $group = true;

    public string $type = 'relation';

    protected string $view = 'components.fields.hasmany';

    public function queryFor($model, $query)
    {
        if ($model instanceof \App\Aura\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \App\Aura\Resources\Team) {
            return $query;
        }

        return $query->where('user_id', $model->id);
    }
}
