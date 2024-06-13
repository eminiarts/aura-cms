<?php

namespace Aura\Base\Traits;

trait CustomTable
{
    protected static function bootCustomTable()
    {
        static::setCustomTable(true);

        ray(static::getCustomTable());

        ray('booted custom table');
    }

    public static function setCustomTable($value)
    {
        static::$customTable = $value;
    }

}
