<?php

namespace Eminiarts\Aura\Fields;

class File extends Field
{
    public $component = 'aura::fields.file';

    public $view = 'aura::fields.view-value';

    public function get($field, $value)
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
