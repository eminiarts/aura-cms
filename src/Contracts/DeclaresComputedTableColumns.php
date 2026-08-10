<?php

namespace Aura\Base\Contracts;

use Aura\Base\Table\ComputedTableColumn;

interface DeclaresComputedTableColumns
{
    /**
     * @return list<ComputedTableColumn>
     */
    public function computedTableColumns(): array;
}
