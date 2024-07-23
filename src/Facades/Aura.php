<?php

namespace Aura\Base\Facades;

use Aura\Base\AuraFake;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Aura\Base\Aura
 */
class Aura extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return \Illuminate\Support\Testing\Fakes\MailFake
     */
    public static function fake()
    {
        static::swap($fake = new AuraFake);

        return $fake;
    }

    protected static function getFacadeAccessor()
    {
        return \Aura\Base\Aura::class;
    }
}
