<?php

namespace Eminiarts\Aura\Fields;

class Image extends Field
{
    public string $component = 'aura::fields.image';

    // public $view = 'components.fields.image';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
