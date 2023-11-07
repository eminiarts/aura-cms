<?php

namespace Eminiarts\Aura\Fields;

class Text extends Field
{
    public $component = 'aura::fields.text';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Text',
                'name' => 'Text',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'text-tab',
                'style' => [],
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
            [
                'name' => 'Prefix',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Suffix',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],

            ],
        ]);
    }
}
