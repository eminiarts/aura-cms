<?php

namespace Eminiarts\Aura\Fields;

class HasMany extends Field
{
    protected string $view = 'components.fields.hasmany';

    public string $component = 'fields.hasmany';

    public bool $group = true;

    public function queryFor($model)
    {
        // dd('hier');
        // dd($model->id, $model);

        // User_id works,
        // What do we do with other Post Types?
        return function ($query) use ($model) {
            return $query->where('user_id', $model->id);
        };
    }
}
