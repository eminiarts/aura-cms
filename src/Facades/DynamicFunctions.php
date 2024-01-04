<?php

namespace Eminiarts\Aura\Facades;

use Eminiarts\Aura\AuraFake;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Eminiarts\Aura\Aura
 */
class DynamicFunctions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Eminiarts\Aura\DynamicFunctions::class;
    }
}
