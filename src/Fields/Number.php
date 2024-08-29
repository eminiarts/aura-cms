<?php

namespace Aura\Base\Fields;

class Number extends Field
{
    public $edit = 'aura::fields.number';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'integer';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'number-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
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

            // minimum value and maximum value as number fields
            // [
            //     'name' => 'Minimum Value',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => '',
            //     'slug' => 'min',
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
            // [
            //     'name' => 'Maximum Value',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => '',
            //     'slug' => 'max',
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }

    public function value($value)
    {
        return (int) $value;
    }

    public function filterOptions()
    {
        return [
            'equals' => __('equals'),
            'not_equals' => __('does not equal'),
            'greater_than' => __('greater than'),
            'less_than' => __('less than'),
            'greater_than_or_equal' => __('greater than or equal to'),
            'less_than_or_equal' => __('less than or equal to'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }
}
