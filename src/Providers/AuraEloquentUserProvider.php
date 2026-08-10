<?php

namespace Aura\Base\Providers;

use Aura\Base\Models\Scopes\TeamScope;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Authentication identity lookup is an explicit TeamScope bypass. Tenant
 * visibility is applied after authentication; credentials and session ids must
 * first be able to resolve the single user record they identify.
 */
class AuraEloquentUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)
            ->withoutGlobalScope(TeamScope::class)
            ->useWritePdo();
    }
}
