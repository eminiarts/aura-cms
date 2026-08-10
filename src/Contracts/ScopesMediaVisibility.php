<?php

namespace Aura\Base\Contracts;

use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

interface ScopesMediaVisibility
{
    public function scopeMediaVisibility(
        Builder $query,
        Authenticatable $actor,
        Resource $resource,
    ): Builder;
}
