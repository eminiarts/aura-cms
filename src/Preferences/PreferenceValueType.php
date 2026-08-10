<?php

namespace Aura\Base\Preferences;

enum PreferenceValueType: string
{
    public function accepts(mixed $value): bool
    {
        return match ($this) {
            self::Array => is_array($value),
            self::Boolean => is_bool($value),
            self::Float => is_float($value) && is_finite($value),
            self::Integer => is_int($value),
            self::String => is_string($value),
        };
    }
    case Array = 'array';
    case Boolean = 'boolean';
    case Float = 'float';
    case Integer = 'integer';
    case String = 'string';
}
