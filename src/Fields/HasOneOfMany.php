<?php

namespace Eminiarts\Aura\Fields;

class HasOneOfMany extends Field
{
    public $component = 'aura::fields.has-one-of-many';

    public bool $group = false;

    public string $type = 'relation';

    public function resource($model, $resource, $option)
    {
        // return $model->posts()->latest()->first();

        return app($resource)->where('user_id', $model->id)->latest()->first();
    }
}
