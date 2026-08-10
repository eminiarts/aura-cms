<?php

namespace Aura\Base\Exceptions;

use RuntimeException;

final class OptionOwnerIdentityException extends RuntimeException
{
    public static function forOption(string|int|null $optionId): self
    {
        $reference = $optionId === null ? '' : " row [{$optionId}]";

        return new self(
            'User option ownership could not be verified for option'.$reference
            .'. Audit or recreate the affected option before retrying.',
        );
    }
}
