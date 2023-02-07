<?php

namespace App;

class AuraTest extends Aura
{
    public static function findResourceBySlug($model)
    {
        return $model;
    }
}
