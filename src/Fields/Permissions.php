<?php

namespace Eminiarts\Aura\Fields;

class Permissions extends Field
{
    public $component = 'aura::fields.permissions';

    // public $view = 'components.fields.permissions';

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
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'Resource',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
