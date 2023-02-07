<?php

namespace App\Aura\Fields;

class Color extends Field
{
    public string $component = 'fields.color';

    protected string $view = 'components.fields.color';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Color',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'color-tab',
                'style' => [],
            ],
            [
                'name' => 'Native Color Picker',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'slug' => 'native',
                'style' => [],
            ],
            [
                'name' => 'Format',
                'type' => 'App\\Aura\\Fields\\Select',
                'validation' => '',
                'slug' => 'format',
                'options' => [
                    'hex' => 'Hex',
                    'rgb' => 'RGB',
                    'hsl' => 'HSL',
                    'hsv' => 'HSV',
                    'cmyk' => 'CMYK',
                ],
                'conditional_logic' => [
                    // [
                    //     'field' => 'native',
                    //     'operator' => '==',
                    //     'value' => '1',
                    // ],
                ],
            ],

        ]);
    }
}
