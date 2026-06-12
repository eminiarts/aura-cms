<?php

namespace Aura\Base\Fields;

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

class BelongsToMany extends Field
{
    public $edit = 'aura::fields.has-many';

    public bool $group = true;

    public $optionGroup = 'Relationship Fields';

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function queryFor($query, $component)
    {
        $field = $component->field;
        $model = $component->model;

        if ($model instanceof User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof Team) {
            return $query;
        }

        return $query->where('user_id', $model->id);
    }
}
