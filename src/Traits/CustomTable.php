<?php

namespace Eminiarts\Aura\Aura\Traits;

use Eminiarts\Aura\Models\Scopes\TypeScope;

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
