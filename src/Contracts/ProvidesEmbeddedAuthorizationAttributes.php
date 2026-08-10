<?php

namespace Aura\Base\Contracts;

interface ProvidesEmbeddedAuthorizationAttributes
{
    /**
     * @return list<string>
     */
    public function embeddedAuthorizationAttributeNames(): array;
}
