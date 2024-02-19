<?php

namespace Aura\Base\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aura\Base\Aura
 */
class DynamicFunctions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aura\Base\DynamicFunctions::class;
    }
}
