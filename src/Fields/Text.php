<?php

namespace Aura\Base\Fields;

class Text extends Field
{
    public $edit = 'aura::fields.text';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'text-tab',
                // 'global' => true,
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'name' => 'Autocomplete',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'autocomplete',
            ],
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],
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
