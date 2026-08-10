<?php

namespace Aura\Base\Traits;

trait ProvidesEmbeddedAuthorizationAttributes
{
    /**
     * @return list<string>
     */
    public function embeddedAuthorizationAttributeNames(): array
    {
        return array_values(array_filter([
            static::getTeamColumn(),
            static::getOwnerColumn(),
        ]));
    }
}
