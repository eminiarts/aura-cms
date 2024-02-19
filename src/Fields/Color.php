<?php

namespace Aura\Base\Fields;

class Color extends Field
{
    public $component = 'aura::fields.color';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Color',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'color-tab',
                'style' => [],
            ],
            [
                'name' => 'Native Color Picker',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'native',
                'style' => [],
            ],
            [
                'name' => 'Format',
                'type' => 'Aura\\Base\\Fields\\Select',
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
