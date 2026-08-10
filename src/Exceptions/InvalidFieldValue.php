<?php

namespace Aura\Base\Exceptions;

use InvalidArgumentException;

class InvalidFieldValue extends InvalidArgumentException
{
    public static function forField(?string $slug, string $reason): self
    {
        $field = $slug ? "Field [{$slug}]" : 'Field value';

        return new self("{$field} is invalid: {$reason}");
    }
}
