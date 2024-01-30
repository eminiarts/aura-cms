<?php

namespace Eminiarts\Aura\Fields;

class Textarea extends Field
{
    public $component = 'aura::fields.textarea';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Textarea',
                'name' => 'Textarea',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'textarea-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
            [
                'name' => 'Rows',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'min' => 1,
                'default' => 3,
                'slug' => 'rows',
            ],
            [
                'name' => 'Max Length',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_length',
                'style' => [
                    'width' => '100',
                ],

            ],
        ]);
    }
}
