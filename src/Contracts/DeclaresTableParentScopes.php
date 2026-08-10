<?php

namespace Aura\Base\Contracts;

use Aura\Base\Table\TableParentScope;

interface DeclaresTableParentScopes
{
    /**
     * @return array<int|string, TableParentScope>
     */
    public function tableParentScopes(): array;
}
