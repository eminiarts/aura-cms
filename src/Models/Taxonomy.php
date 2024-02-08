<?php

namespace Aura\Base\Models;

use Aura\Base\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope());
    }
}
