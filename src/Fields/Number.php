<?php

namespace Aura\Base\Fields;

class Number extends Field
{
    public $edit = 'aura::fields.number';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'integer';

    public $view = 'aura::fields.view-value';

    /**
     * Portable exact-query envelope for sorting/filtering/reporting.
     *
     * Minimal DEST port: enough for ResourceAggregateEngine precision/scale
     * checks without the full FieldValueContract Number rewrite.
     *
     * @param  array<string, mixed>  $field
     * @return array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}
     */
    public function exactQueryConfiguration(array $field): array
    {
        $isDecimal = ($field['number_type'] ?? null) === 'decimal'
            || array_key_exists('scale', $field);

        if ($isDecimal) {
            return [
                'mode' => 'decimal',
                'precision' => isset($field['precision']) ? (int) $field['precision'] : 18,
                'scale' => isset($field['scale']) ? (int) $field['scale'] : 2,
            ];
        }

        $precision = array_key_exists('precision', $field) ? (int) $field['precision'] : null;

        return [
            'mode' => array_key_exists('number_type', $field) ? 'integer' : 'legacy',
            'precision' => $precision,
            'scale' => 0,
        ];
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

    public function getFilterValues($model, $field)
    {
        // For number fields, we don't typically provide predefined values
        // But we could return min and max values if they're defined in the field config
        return [
            'min' => $field['min'] ?? null,
            'max' => $field['max'] ?? null,
        ];
    }

    /**
     * Normalize a value for ExactDecimal comparisons when the full Number
     * write-contract stack is not present on DEST.
     *
     * @param  array<string, mixed>  $field
     */
    public function normalizeForExactQuery(mixed $value, array $field): int|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/\A[+-]?\d+(?:\.\d+)?\z/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    public function set($post, $field, $value)
    {
        return $value;
    }

    public function value($value)
    {
        return (int) $value;
    }
}
