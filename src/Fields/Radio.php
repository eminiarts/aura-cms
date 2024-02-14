<?php

namespace Aura\Base\Fields;

class Radio extends Field
{
    public $component = 'aura::fields.radio';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.radio';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Radio',
                'name' => 'Radio',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'radio',
                'style' => [],
            ],

            [
                'name' => 'Options',
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
}
