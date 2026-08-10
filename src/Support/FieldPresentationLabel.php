<?php

namespace Aura\Base\Support;

use Aura\Base\Contracts\FieldValueContext;
use Illuminate\Database\Eloquent\Model;

final class FieldPresentationLabel
{
    /**
     * Resolve a stored value against the current option catalog.
     *
     * Unknown values remain unchanged so historical codes stay visible. List
     * option keys use strict comparison, which preserves false, 0, and "0" as
     * distinct values. Plain maps cannot represent boolean keys, so booleans
     * never match their integer-key coercion.
     *
     * @param  array<array-key, mixed>  $options
     */
    public function current(mixed $rawValue, array $options): mixed
    {
        if ($rawValue === null || $rawValue === '') {
            return $rawValue;
        }

        if (is_string($rawValue) && str_starts_with($rawValue, '[')) {
            $decoded = json_decode($rawValue, true);

            if (is_array($decoded)) {
                $rawValue = $decoded;
            }
        }

        if (is_array($rawValue)) {
            return array_map(
                fn (mixed $item): mixed => $this->current($item, $options),
                $rawValue,
            );
        }

        foreach ($options as $key => $option) {
            if (is_array($option) && array_key_exists('key', $option)) {
                if ($option['key'] === $rawValue) {
                    return $option['value'] ?? $option['label'] ?? $option['key'];
                }

                continue;
            }

            if (
                ! is_bool($rawValue)
                && ($key === $rawValue || (is_int($key) && is_string($rawValue) && (string) $key === $rawValue))
            ) {
                return $option;
            }
        }

        return $rawValue;
    }

    /**
     * Resolve a record-aware current or historical presentation label.
     *
     * @param  array<string, mixed>  $field
     * @param  list<mixed>  $additionalArguments
     */
    public function resolve(
        mixed $rawValue,
        mixed $currentLabel,
        array $field,
        ?Model $model,
        FieldValueContext $context,
        array $additionalArguments = [],
    ): mixed {
        $resolver = $field['label_resolver'] ?? null;

        if (! is_callable($resolver)) {
            return $currentLabel;
        }

        $resolved = $resolver(
            $rawValue,
            $currentLabel,
            $model,
            $context,
            $field,
            ...$additionalArguments,
        );

        return $resolved ?? $currentLabel;
    }
}
