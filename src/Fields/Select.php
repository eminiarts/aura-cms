<?php

namespace Eminiarts\Aura\Fields;

class Select extends Field
{
    public $component = 'aura::fields.select';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select',
                'name' => 'Select',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Key',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

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
        ]);
    }

    // public $view = 'components.fields.select';

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }
}
