<?php

namespace App\Aura\Traits;

use App\Models\Scopes\TypeScope;

trait CustomTable
{
    // protected static bool $customTable = true;

    protected static function bootCustomTable()
    {
        // Remove GlobalScope TypeScope
        unset(static::$globalScopes[static::class][TypeScope::class]);
        // static::withoutGlobalScope(TypeScope::class);

        static::$customTable = true;
    }
}
