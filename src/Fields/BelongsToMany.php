<?php

namespace Aura\Base\Fields;

class BelongsToMany extends Field
{
    public $component = 'aura::fields.has-many';

    public bool $group = true;

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function queryFor($query, $component)
    {
        $field = $component->field;
        $model = $component->model;

        if ($model instanceof \Aura\Base\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \Aura\Base\Resources\Team) {
            return $query;
        }

        return $query->where('user_id', $model->id);
    }
}
