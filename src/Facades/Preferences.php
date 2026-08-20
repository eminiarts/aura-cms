<?php

namespace Aura\Base\Facades;

use Aura\Base\Preferences\PreferenceManager;
use Illuminate\Support\Facades\Facade;

/** @see PreferenceManager */
class Preferences extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PreferenceManager::class;
    }
}
