<?php

namespace Aura\Base\Contracts;

use Aura\Base\Table\TableRowOrdering;

interface DeclaresTableRowOrdering
{
    public function tableRowOrdering(): TableRowOrdering;
}
