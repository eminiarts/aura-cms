<?php

namespace Aura\Base\Fields\Filters;

use Stringable;

final class FilterOptionNormalizer
{
    /**
     * @param  iterable<array-key, mixed>  $options
     * @return list<array{value: string|int|float|bool, wire_value: string, label: string}>
     */
    public function normalize(iterable $options): array
    {
        $options = is_array($options) ? $options : iterator_to_array($options);
        $normalized = [];
        $wireValues = [];

        if (array_is_list($options)) {
            foreach ($options as $option) {
                if (is_array($option) && array_key_exists('key', $option) && array_key_exists('value', $option)) {
                    $this->append($normalized, $wireValues, $option['key'], $option['value']);

                    continue;
                }

                if (is_array($option) && array_key_exists('value', $option) && array_key_exists('label', $option)) {
                    $this->append($normalized, $wireValues, $option['value'], $option['label']);

                    continue;
                }

                $this->append($normalized, $wireValues, $option, $option);
            }

            return $normalized;
        }

        foreach ($options as $value => $label) {
            $this->append($normalized, $wireValues, $value, $label);
        }

        return $normalized;
    }

    /**
     * @param  list<array{value: string|int|float|bool, wire_value: string, label: string}>  $normalized
     * @param  array<int|string, true>  $wireValues
     */
    private function append(array &$normalized, array &$wireValues, mixed $value, mixed $label): void
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value) && ! is_bool($value)) {
            return;
        }

        if (! is_string($label) && ! is_int($label) && ! is_float($label) && ! is_bool($label) && ! $label instanceof Stringable) {
            return;
        }

        if ((is_string($value) && trim($value) === '') || trim((string) $label) === '') {
            return;
        }

        $wireValue = is_bool($value) ? (string) (int) $value : (string) $value;

        if (isset($wireValues[$wireValue])) {
            return;
        }

        $wireValues[$wireValue] = true;
        $normalized[] = [
            'value' => $value,
            'wire_value' => $wireValue,
            'label' => (string) $label,
        ];
    }
}
