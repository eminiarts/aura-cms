<?php

namespace Eminiarts\Aura\Models;

use Illuminate\Database\Eloquent\Model;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Taxonomy extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope());
    }
}
