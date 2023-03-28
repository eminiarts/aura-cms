<?php

namespace Eminiarts\Aura\Models;

use Eminiarts\Aura\Models\Scopes\TeamScope;
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
