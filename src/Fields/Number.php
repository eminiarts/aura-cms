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
                'name' => 'Default Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
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

            // minimum value and maximum value as number fields
            // [
            //     'name' => 'Minimum Value',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Number',
            //     'validation' => '',
            //     'slug' => 'min',
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
            // [
            //     'name' => 'Maximum Value',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Number',
            //     'validation' => '',
            //     'slug' => 'max',
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
        ]);
    }

    public function set($value)
    {
        return (int) $value;
    }

    public function value($value)
    {
        return (int) $value;
    }
}
