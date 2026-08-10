<?php

namespace Aura\Base\Fields\Filters;

use InvalidArgumentException;
use Stringable;
use Traversable;

final class FilterOptionNormalizer
{
    private const WIRE_PREFIX = '__aura_filter:';

    public function assertScalarStorageIsUnambiguous(mixed $options): void
    {
        $storageValues = [];

        foreach ($this->normalize($options) as $option) {
            $storageValue = $this->legacyWireValue($option['value']);

            if (array_key_exists($storageValue, $storageValues)) {
                throw new InvalidArgumentException(
                    'Scalar choice storage cannot distinguish scalar option values with the same string representation. Use unique scalar keys or a JSON-backed multiple-value field.'
                );
            }

            $storageValues[$storageValue] = true;
        }
    }

    /**
     * @return list<array{value: string|int|float|bool, wire_value: string, label: string}>
     */
    public function normalize(mixed $options): array
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (! is_array($options)) {
            return [];
        }

        $candidates = [];

        if (array_is_list($options)) {
            foreach ($options as $option) {
                if (is_array($option) && array_key_exists('key', $option) && array_key_exists('value', $option)) {
                    $this->append($candidates, $option['key'], $option['value']);

                    continue;
                }

                if (is_array($option) && array_key_exists('value', $option) && array_key_exists('label', $option)) {
                    $this->append($candidates, $option['value'], $option['label']);

                    continue;
                }

                $this->append($candidates, $option, $option);
            }
        } else {
            foreach ($options as $value => $label) {
                $this->append($candidates, $value, $label);
            }
        }

        return $this->assignWireValues($candidates);
    }

    /**
     * @param  list<array{value: string|int|float|bool, label: string}>  $candidates
     */
    private function append(array &$candidates, mixed $value, mixed $label): void
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

        if (is_float($value) && ! is_finite($value)) {
            return;
        }

        $identity = $this->identity($value);

        foreach ($candidates as $candidate) {
            if ($this->identity($candidate['value']) === $identity) {
                return;
            }
        }

        $candidates[] = [
            'value' => $value,
            'label' => (string) $label,
        ];
    }

    /**
     * @param  list<array{value: string|int|float|bool, label: string}>  $candidates
     * @return list<array{value: string|int|float|bool, wire_value: string, label: string}>
     */
    private function assignWireValues(array $candidates): array
    {
        $legacyCounts = [];

        foreach ($candidates as $candidate) {
            $legacy = $this->legacyWireValue($candidate['value']);
            $legacyCounts[$legacy] = ($legacyCounts[$legacy] ?? 0) + 1;
        }

        return array_map(function (array $candidate) use ($legacyCounts): array {
            $legacy = $this->legacyWireValue($candidate['value']);

            return [
                'value' => $candidate['value'],
                'wire_value' => $legacyCounts[$legacy] === 1 && ! str_starts_with($legacy, self::WIRE_PREFIX)
                    ? $legacy
                    : self::WIRE_PREFIX.rtrim(strtr(base64_encode($this->identity($candidate['value'])), '+/', '-_'), '='),
                'label' => $candidate['label'],
            ];
        }, $candidates);
    }

    private function identity(string|int|float|bool $value): string
    {
        return match (true) {
            is_bool($value) => 'bool:'.($value ? '1' : '0'),
            is_int($value) => 'int:'.$value,
            is_float($value) => 'float:'.json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
            default => 'string:'.$value,
        };
    }

    private function legacyWireValue(string|int|float|bool $value): string
    {
        return is_bool($value) ? (string) (int) $value : (string) $value;
    }
}
