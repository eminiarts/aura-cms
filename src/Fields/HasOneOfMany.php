<?php

namespace Eminiarts\Aura\Fields;

class HasOneOfMany extends Field
{
    public $component = 'aura::fields.has-one-of-many';

    public bool $group = false;

    public string $type = 'relation';

    public function resource($model, $posttype, $option)
    {
        // return $model->posts()->latest()->first();

        return app($posttype)->where('user_id', $model->id)->latest()->first();
    }
}
