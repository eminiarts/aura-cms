<?php

namespace Aura\Base\Fields;

class File extends Field
{
    public $edit = 'aura::fields.file';

    public $optionGroup = 'Media Fields';

    public $tableColumnType = 'json';

    public $view = 'aura::fields.view-value';

    public function get($class, $value, $field = null)
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function set($post, $field, $value)
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
