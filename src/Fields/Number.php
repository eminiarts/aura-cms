<?php

namespace Eminiarts\Aura\Fields;

class Number extends Field
{
    public $component = 'aura::fields.number';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'number-tab',
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

    public function value($value)
    {
        return (int) $value;
    }
}
