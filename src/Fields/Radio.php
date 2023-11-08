<?php

namespace Eminiarts\Aura\Fields;

class Radio extends Field
{
    public $component = 'aura::fields.radio';

    // public $view = 'components.fields.radio';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Radio',
                'name' => 'Radio',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'radio',
                'style' => [],
            ],

            [
                'name' => 'Options',
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
