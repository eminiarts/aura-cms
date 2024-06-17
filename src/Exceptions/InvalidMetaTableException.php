<?php

namespace Aura\Base\Exceptions;

use Exception;

class InvalidMetaTableException extends Exception
{
    public function __construct($message = 'You need to define a custom meta table for this model.')
    {
        parent::__construct($message);
    }
}
