<?php

namespace Aura\Base\Services;

use Aura\Base\Exceptions\InvalidEmbeddedComponentParameters;
use JsonException;

final class EmbeddedComponentParameterValidator
{
    public const MAX_DEPTH = 10;

    public const MAX_ELEMENTS = 1024;

    public const MAX_ENCODED_BYTES = 65_536;

    public const MAX_KEY_BYTES = 191;

    public const MAX_STRING_BYTES = 16_384;

    /**
     * @param  array<string|int, mixed>  $parameters
     */
    public function validate(array $parameters): void
    {
        $elements = 0;
        $this->validateValue($parameters, 0, $elements);

        try {
            $encoded = json_encode(
                $parameters,
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new InvalidEmbeddedComponentParameters(
                'Embedded component parameters must be JSON encodable.',
                previous: $exception,
            );
        }

        if (strlen($encoded) > self::MAX_ENCODED_BYTES) {
            throw new InvalidEmbeddedComponentParameters(sprintf(
                'Embedded component parameters exceed the %d encoded byte limit.',
                self::MAX_ENCODED_BYTES,
            ));
        }
    }

    private function validateValue(mixed $value, int $depth, int &$elements): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new InvalidEmbeddedComponentParameters(sprintf(
                'Embedded component parameters exceed the %d level nesting depth.',
                self::MAX_DEPTH,
            ));
        }

        if (is_string($value)) {
            if (strlen($value) > self::MAX_STRING_BYTES) {
                throw new InvalidEmbeddedComponentParameters(sprintf(
                    'Embedded component parameters exceed the %d string byte limit.',
                    self::MAX_STRING_BYTES,
                ));
            }

            return;
        }

        if ($value === null || is_int($value) || is_bool($value)) {
            return;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidEmbeddedComponentParameters('Embedded component parameters require finite numbers.');
            }

            return;
        }

        if (! is_array($value)) {
            throw new InvalidEmbeddedComponentParameters('Embedded component parameters may contain only bounded scalar and array values.');
        }

        foreach ($value as $key => $nestedValue) {
            $elements++;

            if ($elements > self::MAX_ELEMENTS) {
                throw new InvalidEmbeddedComponentParameters(sprintf(
                    'Embedded component parameters exceed the %d element limit.',
                    self::MAX_ELEMENTS,
                ));
            }

            if (is_string($key) && strlen($key) > self::MAX_KEY_BYTES) {
                throw new InvalidEmbeddedComponentParameters(sprintf(
                    'Embedded component parameters exceed the %d key byte limit.',
                    self::MAX_KEY_BYTES,
                ));
            }

            $this->validateValue($nestedValue, $depth + 1, $elements);
        }
    }
}
