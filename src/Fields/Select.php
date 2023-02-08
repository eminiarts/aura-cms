<?php

namespace Eminiarts\Aura\Fields;

class Select extends Field
{
    public string $component = 'fields.select';

    protected string $view = 'components.fields.select';

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
                'label' => 'options',
                'name' => 'options',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'label' => 'Key',
                'name' => 'key',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'label' => 'Value',
                'name' => 'value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
        ]);
    }
}
