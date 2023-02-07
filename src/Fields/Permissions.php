<?php

namespace Eminiarts\Aura\Aura\Fields;

class Permissions extends Field
{
    public string $component = 'fields.permissions';

    protected string $view = 'components.fields.permissions';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Permissions',
                'name' => 'Permissions',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],
            [
                'label' => 'Posttype',
                'name' => 'Posttype',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'posttype',
            ],
        ]);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
