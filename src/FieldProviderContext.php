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
        foreach ($values as $key => $value) {
            if (! is_string($key) || (! is_scalar($value) && $value !== null)) {
                throw new InvalidArgumentException('Field provider cache context must be a string-keyed array of scalar or null values.');
            }
        }

        ksort($values);

        $this->resourceClass = $resourceClass;
        $this->values = $values;
    }

    /**
     * @throws JsonException
     */
    public function fingerprint(): string
    {
        return hash('sha256', json_encode(
            [$this->resourceClass, $this->values],
            JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }

    public function value(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }
}
