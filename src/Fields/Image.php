<?php

namespace Eminiarts\Aura\Fields;

class Image extends Field
{
    public $component = 'aura::fields.image';

    public $view = 'aura::fields.view-value';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
