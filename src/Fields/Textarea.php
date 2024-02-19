<?php

namespace Aura\Base\Fields;

class Textarea extends Field
{
    public $component = 'aura::fields.textarea';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Textarea',
                'name' => 'Textarea',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'textarea-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
            [
                'name' => 'Rows',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'min' => 1,
                'default' => 3,
                'slug' => 'rows',
            ],
            [
                'name' => 'Max Length',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_length',
                'style' => [
                    'width' => '100',
                ],

            ],
        ]);
    }
}
