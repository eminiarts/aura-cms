<?php

namespace Eminiarts\Aura\Fields;

class Color extends Field
{
    public string $component = 'aura::fields.color';

    protected string $view = 'components.fields.color';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Color',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'color-tab',
                'style' => [],
            ],
            [
                'name' => 'Native Color Picker',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'slug' => 'native',
                'style' => [],
            ],
            [
                'name' => 'Format',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
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
