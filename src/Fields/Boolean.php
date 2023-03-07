<?php

namespace Eminiarts\Aura\Fields;

class Boolean extends Field
{
    public $component = 'aura::fields.boolean';

    // public $view = 'components.fields.boolean';

    public function get($field, $value)
    {
        return (bool) $value;
    }

    public function set($value)
    {
        return (bool) $value;
    }

    public function value($value)
    {
        return (bool) $value;
    }
}
