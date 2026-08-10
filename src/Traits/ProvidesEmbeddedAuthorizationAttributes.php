<?php

namespace Aura\Base\Traits;

trait ProvidesEmbeddedAuthorizationAttributes
{
    /**
     * @return list<string>
     */
    public function embeddedAuthorizationAttributeNames(): array
    {
        return ['team_id', 'user_id'];
    }
}
