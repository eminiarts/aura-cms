<?php

namespace Aura\Base;

use InvalidArgumentException;
use JsonException;

final readonly class FieldProviderContext
{
    /**
     * @var class-string
     */
    public string $resourceClass;

    /**
     * @var array<string, bool|float|int|string|null>
     */
    public array $values;

    /**
     * @param  class-string  $resourceClass
     * @param  array<string, bool|float|int|string|null>  $values
     */
    public function __construct(string $resourceClass, array $values = [])
    {
        if (! $this->isValidUtf8($resourceClass)) {
            throw new InvalidArgumentException('Field provider cache context resource class must be valid UTF-8.');
        }

        foreach ($values as $key => $value) {
            if (! is_string($key) || ! $this->isValidUtf8($key)) {
                throw new InvalidArgumentException('Field provider cache context must use valid UTF-8 string keys.');
            }

            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Field provider cache context must be a string-keyed array of scalar or null values.');
            }

            if (is_float($value) && ! is_finite($value)) {
                throw new InvalidArgumentException('Field provider cache context floats must be finite.');
            }

            if (is_string($value) && ! $this->isValidUtf8($value)) {
                throw new InvalidArgumentException('Field provider cache context strings must be valid UTF-8.');
            }
        }

        ksort($values, SORT_STRING);

        $this->resourceClass = $resourceClass;
        $this->values = $values;
    }

    public function fingerprint(): string
    {
        try {
            return hash('sha256', json_encode(
                [$this->resourceClass, $this->values],
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Field provider cache context could not be encoded.',
                previous: $exception,
            );
        }
    }

    public function value(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    private function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }
}
