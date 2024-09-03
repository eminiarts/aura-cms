<?php

namespace Aura\Base\Fields;

class Checkbox extends Field
{
    public $edit = 'aura::fields.checkbox';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.checkbox';

    public function get($class, $value, $field = null)
    {
        // dd($value);
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
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

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
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }

    public function set($post, $field, $value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
