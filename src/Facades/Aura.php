<?php

namespace Eminiarts\Aura\Facades;

use Eminiarts\Aura\AuraFake;
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

      /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        static::swap($fake = new AuraFake());

        return $fake;
    }
}
