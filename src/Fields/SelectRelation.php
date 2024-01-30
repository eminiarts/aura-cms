<?php

namespace Eminiarts\Aura\Fields;

class SelectRelation extends Field
{
    public $component = 'aura::fields.select-relation';

    // public $view = 'components.fields.select-relation';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $values = json_decode($value, true);

        return app($field['resource'])->find($values)->pluck('name')->implode(',');
    }

    public function get($field, $value)
    {
        if (! $value) {
            return;
        }

        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
