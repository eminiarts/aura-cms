<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Exceptions\InvalidFieldValue;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Support\ExactDecimal;
use Illuminate\Database\Eloquent\Model;

/**
 * Exact-number write contract.
 *
 * New values must be native integers or plain base-10 strings. Floats and
 * scientific notation are rejected because their binary/string conversion can
 * diverge by runtime or database driver. Hydration stays tolerant so legacy
 * invalid rows remain inspectable instead of being silently rewritten.
 */
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
            if (array_key_exists('precision', $field)) {
                [$precision] = $this->precisionAndScale([...$field, 'scale' => 0]);

                return new FieldColumn(
                    type: 'decimal',
                    arguments: [$precision, 0],
                    nullable: $this->tableNullable,
                    driverTypes: ['sqlite' => 'text'],
                );
            }

            return parent::columnDefinition($field);
        }

        [$precision, $scale] = $this->precisionAndScale($field);

        return new FieldColumn(
            type: 'decimal',
            arguments: [$precision, $scale],
            nullable: $this->tableNullable,
            // SQLite's NUMERIC affinity coerces long decimal strings to
            // binary floating point. TEXT is required there for exact storage;
            // MySQL/PostgreSQL continue to use native DECIMAL.
            driverTypes: ['sqlite' => 'text'],
        );
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array{mode: 'decimal'|'integer'|'legacy', precision: int|null, scale: int}
     */
    public function exactQueryConfiguration(array $field): array
    {
        if ($this->isDecimal($field)) {
            [$precision, $scale] = $this->precisionAndScale($field);

            return ['mode' => 'decimal', 'precision' => $precision, 'scale' => $scale];
        }

        $precision = null;

        if (array_key_exists('precision', $field)) {
            [$precision] = $this->precisionAndScale([...$field, 'scale' => 0]);
        }

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
        return $this->normalize($value, $field, strict: false);
    }

    /**
     * Return the strict normalized numeric value used by exact queries, or
     * null when a tolerant legacy value is outside this field's contract.
     *
     * @param  array<string, mixed>  $field
     */
    public function normalizeForExactQuery(mixed $value, array $field): int|string|null
    {
        try {
            $normalized = $this->normalize($value, $field, strict: true);
        } catch (InvalidFieldValue) {
            return null;
        }

        return $normalized === null || $normalized === '' ? null : $normalized;
    }

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        return $this->normalize($value, $field, strict: true);
    }

    public function set($post, $field, $value)
    {
        return $this->normalize($value, is_array($field) ? $field : [], strict: true);
    }

    public function value($value)
    {
        if ($value === null || $value === '' || is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            throw InvalidFieldValue::forField(null, 'numbers must be submitted as plain base-10 strings or native integers');
        }

        $numeric = trim($value);

        if (preg_match('/^[+-]?\d+$/', $numeric) === 1) {
            return $this->normalizeInteger($numeric);
        }

        if (preg_match('/^[+-]?\d+\.\d+$/', $numeric) === 1) {
            return $numeric;
        }

        throw InvalidFieldValue::forField(null, 'scientific notation and non-decimal values are not supported');
    }

    private function configurationInteger(mixed $value, string $name): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw InvalidFieldValue::forField(null, "{$name} must be an integer");
    }

    private function integerDigits(string $integer): int
    {
        return $integer === '0' ? 0 : strlen($integer);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function invalidValue(mixed $legacyValue, array $field, bool $strict, string $reason): mixed
    {
        if (! $strict) {
            return $legacyValue;
        }

        throw InvalidFieldValue::forField($field['slug'] ?? null, $reason);
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
    private function normalize(mixed $value, array $field, bool $strict): mixed
    {
        $decimal = $this->isDecimal($field);
        [$precision, $scale] = $decimal
            ? $this->precisionAndScale($field)
            : [null, null];

        if ($value === null || $value === '') {
            return $value;
        }

        if (is_float($value)) {
            if ($strict) {
                throw InvalidFieldValue::forField($field['slug'] ?? null, 'floating-point input is inexact; submit a plain base-10 string');
            }

            $value = (string) $value;
        }

        if (! is_int($value) && ! is_string($value)) {
            return $this->invalidValue(
                $value,
                $field,
                $strict,
                'numbers must be submitted as plain base-10 strings or native integers',
            );
        }

        $numeric = is_string($value) ? trim($value) : (string) $value;

        if (preg_match('/^[+-]?(\d+)(?:\.(\d+))?$/', $numeric, $portableMatches) === 1) {
            $integer = ltrim($portableMatches[1], '0');
            $digitCount = ($integer === '' ? 0 : strlen($integer)) + strlen($portableMatches[2] ?? '');

            if ($digitCount > ExactDecimal::MAX_DIGITS) {
                return $this->invalidValue(
                    $value,
                    $field,
                    $strict,
                    'exceeds the 65-digit portability limit',
                );
            }
        }

        if (! $decimal) {
            // Fields created before number_type existed accepted both plain
            // integers and decimals in meta storage. Preserve that exact-string
            // behavior while keeping explicitly configured integer fields strict.
            if (! array_key_exists('number_type', $field)
                && preg_match('/^[+-]?\d+\.\d+$/', $numeric) === 1) {
                return $numeric;
            }

            if (preg_match('/^[+-]?\d+$/', $numeric) !== 1) {
                return $this->invalidValue(
                    $value,
                    $field,
                    $strict,
                    'scientific notation, fractions, and non-decimal values are not valid integers',
                );
            }

            if (array_key_exists('precision', $field)) {
                [$precision] = $this->precisionAndScale([...$field, 'scale' => 0]);
                $digits = ltrim($numeric, '+-0');
                $digitCount = $digits === '' ? 1 : strlen($digits);

                if ($digitCount > $precision) {
                    return $this->invalidValue($value, $field, $strict, "exceeds DECIMAL({$precision}, 0) precision");
                }
            }

            return $this->normalizeInteger($numeric);
        }

        if (preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/', $numeric, $matches) !== 1) {
            return $this->invalidValue(
                $value,
                $field,
                $strict,
                'scientific notation and non-decimal values are not supported',
            );
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $integerCapacity = $precision - $scale;

        if ($this->integerDigits($integer) > $integerCapacity) {
            return $this->invalidValue($value, $field, $strict, "exceeds DECIMAL({$precision}, {$scale}) precision");
        }

        $fraction = $matches[3] ?? '';
        $rounded = $this->roundDecimal($integer, $fraction, $scale);

        if ($this->integerDigits($rounded['integer']) > $integerCapacity) {
            return $this->invalidValue($value, $field, $strict, "rounding exceeds DECIMAL({$precision}, {$scale}) precision");
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
        $precision = $this->configurationInteger($field['precision'] ?? self::DEFAULT_PRECISION, 'precision');
        $scale = $this->configurationInteger($field['scale'] ?? self::DEFAULT_SCALE, 'scale');

        if ($precision < 1 || $precision > 65) {
            throw InvalidFieldValue::forField($field['slug'] ?? null, 'precision must be between 1 and 65');
        }

        if ($scale < 0 || $scale > 30 || $scale > $precision) {
            throw InvalidFieldValue::forField($field['slug'] ?? null, 'scale must be between 0 and 30 and cannot exceed precision');
        }

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
