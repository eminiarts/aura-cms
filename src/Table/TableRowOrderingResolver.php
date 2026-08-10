<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\DeclaresTableRowOrdering;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Schema;

final class TableRowOrderingResolver
{
    public function resolve(Resource $resource): ?TableRowOrdering
    {
        if (! $resource instanceof DeclaresTableRowOrdering) {
            return null;
        }

        $ordering = $resource->tableRowOrdering();

        if ($ordering->column === $resource->getKeyName()) {
            return null;
        }

        $schema = Schema::connection($resource->getConnectionName());

        if (! $schema->hasColumn($resource->getTable(), $ordering->column)) {
            return null;
        }

        return $ordering;
    }
}
