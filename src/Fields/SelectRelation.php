<?php

namespace Aura\Base\Fields;

class SelectRelation extends Field
{
    public $edit = 'aura::fields.select-relation';

    // public $view = 'components.fields.select-relation';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $values = json_decode($value, true);

        return app($field['resource'])->find($values)->pluck('name')->implode(',');
    }

    public function get($class, $value, $field = null)
    {
        if (! $value) {
            return;
        }

        return json_decode($value, true);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }
}
