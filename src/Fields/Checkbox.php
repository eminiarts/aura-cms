<?php

namespace Eminiarts\Aura\Fields;

class Checkbox extends Field
{
    public string $component = 'fields.checkbox';

    protected string $view = 'components.fields.checkbox';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Checkbox',
                'name' => 'Checkbox',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'checkbox',
                'style' => [],
            ],

            [
                'label' => 'options',
                'name' => 'options',
                'type' => 'App\\Aura\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',

            ],
            [
                'label' => 'Key',
                'name' => 'key',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'label' => 'Value',
                'name' => 'value',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
        ]);
    }
}
