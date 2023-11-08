<?php

namespace Eminiarts\Aura\Fields;

class Boolean extends Field
{
    public $component = 'aura::fields.boolean';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Boolean',
                'name' => 'Boolean',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'boolean-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],

        ]);
    }

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
