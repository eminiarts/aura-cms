<?php

namespace Aura\Base\Facades;

use Aura\Base\AuraFake;
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
