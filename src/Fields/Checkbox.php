<?php

namespace Eminiarts\Aura\Fields;

class Checkbox extends Field
{
    public $component = 'aura::fields.checkbox';

    // public $view = 'components.fields.checkbox';

     public function get($field, $value)
    {
        if (is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

     public function set($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Checkbox',
                'name' => 'Checkbox',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'checkbox',
                'style' => [],
            ],

            [
                'label' => 'options',
                'name' => 'options',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',

            ],
            [
                'name' => 'Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'name',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Default Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }
}
