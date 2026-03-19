<?php

namespace Aura\Base\Facades;

use Aura\Base\Aura;
use Illuminate\Support\Facades\Facade;

/**
 * @see Aura
 */
class DynamicFunctions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aura\Base\DynamicFunctions::class;
    }
}
