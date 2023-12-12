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
        // dump('setting file here', $value);
        if (is_null($value)) {
            return;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
