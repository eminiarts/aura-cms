<?php

namespace Aura\Base\Fields;

class Checkbox extends Field
{
    public $component = 'aura::fields.checkbox';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.checkbox';

    public function get($field, $value)
    {
        if ($value === null || $value === false) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Checkbox',
                'name' => 'Checkbox',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'checkbox',
                'style' => [],
            ],

            [
                'label' => 'options',
                'name' => 'options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'name',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }

    public function set($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
