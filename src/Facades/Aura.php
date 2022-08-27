<?php

namespace Eminiarts\Aura\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eminiarts\Aura\Aura
 */
class Aura extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Eminiarts\Aura\Aura::class;
    }
}
