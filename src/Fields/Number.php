<?php

namespace Eminiarts\Aura\Fields;

class Number extends Field
{
    public string $component = 'fields.number';

    protected string $view = 'components.fields.number';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'number',
                'style' => [],
            ],
            [
                'name' => 'Add on text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'test',
                'conditional_logic' => [],
                'default' => 'Hallo',
            ],
            // [
            //     'name' => 'Number',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Tab',
            //     'slug' => 'number-tab',
            //     'style' => [],
            // ],
            // [
            //     'name' => 'Add on',

            //     'type' => 'Eminiarts\\Aura\\Fields\\Radio',
            //     'validation' => '',
            //     'slug' => 'number-add-on',
            //     'default' => 'none',
            //     'instructions' => 'Add an add on to the number field',
            //     'options' => [
            //         'none' => 'None',
            //         'prefix' => 'Prefix',
            //         'suffix' => 'Suffix',
            //     ],
            // ],
            // [
            //     'name' => 'Add on text',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Text',
            //     'validation' => '',
            //     'slug' => 'number-add-on-text',
            //     'conditional_logic' => [
            //         [
            //             'field' => 'number-add-on',
            //             'operator' => '!=',
            //             'value' => 'none',
            //         ]
            //     ],
            // ]
        ]);
    }

    public function value($value)
    {
        return (int) $value;
    }
}
