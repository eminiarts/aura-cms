<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Schema\FieldColumn;
use Illuminate\Database\Eloquent\Model;

class Number extends Field
{
    public const DEFAULT_PRECISION = 19;

    public const DEFAULT_SCALE = 2;

    public $edit = 'aura::fields.number';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'integer';

    public $view = 'aura::fields.view-value';

    /**
     * @param  array<string, mixed>  $field
     */
    public function columnDefinition(array $field): FieldColumn
    {
        if (! $this->isDecimal($field)) {
            return parent::columnDefinition($field);
        }

        [$precision, $scale] = $this->precisionAndScale($field);

        return new FieldColumn(
            type: 'decimal',
            arguments: [$precision, $scale],
            nullable: $this->tableNullable,
        );
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
                'name' => 'Number Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => 'required|in:integer,decimal',
                'slug' => 'number_type',
                'default' => 'integer',
                'options' => [
                    'integer' => 'Integer',
                    'decimal' => 'Decimal',
                ],
            ],
            [
                'name' => 'Precision',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required|integer|min:1|max:65',
                'slug' => 'precision',
                'default' => self::DEFAULT_PRECISION,
            ],
            [
                'name' => 'Decimal Places',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required|integer|min:0|max:30',
                'slug' => 'scale',
                'default' => self::DEFAULT_SCALE,
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

    public function hydrateFromStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
        FieldValueContext $context = FieldValueContext::Model,
    ): mixed {
        return $this->normalize($value, $field);
    }

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        return $this->normalize($value, $field);
    }

    public function set($post, $field, $value)
    {
        return $this->normalize($value, is_array($field) ? $field : []);
    }

    public function value($value)
    {
        if ($value === null || $value === '' || is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[+-]?\d+$/', $value) === 1
            ? $this->normalizeInteger($value)
            : $value;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function isDecimal(array $field): bool
    {
        if (array_key_exists('number_type', $field)) {
            return $field['number_type'] === 'decimal';
        }

        return $this->tableColumnType === 'decimal'
            || array_key_exists('scale', $field);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function normalize(mixed $value, array $field): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            return $value;
        }

        $numeric = is_string($value) ? trim($value) : (string) $value;

        if (! is_numeric($numeric)) {
            return $value;
        }

        if (! $this->isDecimal($field)) {
            if (preg_match('/^[+-]?\d+$/', $numeric) !== 1) {
                return $value;
            }

            return $this->normalizeInteger($numeric);
        }

        if (preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/', $numeric, $matches) !== 1) {
            return $value;
        }

        [$precision, $scale] = $this->precisionAndScale($field);
        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;

        if (strlen($integer) > $precision - $scale) {
            return $value;
        }

        $fraction = $matches[3] ?? '';
        $rounded = $this->roundDecimal($integer, $fraction, $scale);

        if (strlen($rounded['integer']) > $precision - $scale) {
            return $value;
        }

        $sign = $matches[1] === '-' && ($rounded['integer'] !== '0' || trim($rounded['fraction'], '0') !== '')
            ? '-'
            : '';

        return $sign.$rounded['integer'].($scale > 0 ? '.'.$rounded['fraction'] : '');
    }

    private function normalizeInteger(string $value): int|string
    {
        $sign = str_starts_with($value, '-') ? '-' : '';
        $digits = ltrim($value, '+-0');
        $digits = $digits === '' ? '0' : $digits;
        $normalized = $sign === '-' && $digits !== '0' ? '-'.$digits : $digits;

        if (filter_var($normalized, FILTER_VALIDATE_INT) !== false) {
            return (int) $normalized;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{0: int, 1: int}
     */
    private function precisionAndScale(array $field): array
    {
        $precision = max(1, min(65, (int) ($field['precision'] ?? self::DEFAULT_PRECISION)));
        $scale = max(0, min(30, (int) ($field['scale'] ?? self::DEFAULT_SCALE)));
        $scale = min($scale, $precision - 1);

        return [$precision, $scale];
    }

    /**
     * @return array{integer: string, fraction: string}
     */
    private function roundDecimal(string $integer, string $fraction, int $scale): array
    {
        $keptFraction = str_pad(substr($fraction, 0, $scale), $scale, '0');
        $roundingDigit = (int) ($fraction[$scale] ?? 0);

        if ($roundingDigit < 5) {
            return ['integer' => $integer, 'fraction' => $keptFraction];
        }

        $digits = $integer.$keptFraction;
        $carry = 1;

        for ($index = strlen($digits) - 1; $index >= 0 && $carry === 1; $index--) {
            $next = ((int) $digits[$index]) + $carry;
            $digits[$index] = (string) ($next % 10);
            $carry = intdiv($next, 10);
        }

        if ($carry === 1) {
            $digits = '1'.$digits;
        }

        if ($scale === 0) {
            return ['integer' => $digits, 'fraction' => ''];
        }

        return [
            'integer' => substr($digits, 0, -$scale) ?: '0',
            'fraction' => substr($digits, -$scale),
        ];
    }
}
