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

    public function getFilterValues($model, $field)
    {
        // For number fields, we don't typically provide predefined values
        // But we could return min and max values if they're defined in the field config
        return [
            'min' => $field['min'] ?? null,
            'max' => $field['max'] ?? null,
        ];
    }
}
