<?php

namespace Eminiarts\Aura\Fields;

class SelectRelation extends Field
{
    protected string $view = 'components.fields.select-relation';

    public string $component = 'fields.select-relation';

    public function set($value)
    {
        return json_encode($value);
    }

    public function get($field, $value)
    {
        if (!$value) {
            return;
        }

        return json_decode($value, true);
    }

    public function display($field, $value)
    {
        if (!$value) {
            return;
        }

        $values = json_decode($value, true);

        return app($field['posttype'])->find($values)->pluck('name')->implode(',');
    }
}
