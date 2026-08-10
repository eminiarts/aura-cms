<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\DeclaresComputedTableColumns;
use Aura\Base\Resource;
use InvalidArgumentException;

final class TableColumnRegistry
{
    /** @var list<string> */
    private const RESERVED_KEYS = ['actions', 'select', 'selection'];

    /**
     * @return array<string, ComputedTableColumn>
     */
    public function computed(Resource $resource): array
    {
        if (! $resource instanceof DeclaresComputedTableColumns) {
            return [];
        }

        $declared = $resource->computedTableColumns();

        if (! array_is_list($declared)) {
            throw new InvalidArgumentException('Computed table columns must be declared as a list.');
        }

        $fieldKeys = collect($resource->getFields())
            ->pluck('slug')
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->all();
        $reserved = array_fill_keys([
            ...self::RESERVED_KEYS,
            ...$fieldKeys,
            ...$resource->getFillable(),
            $resource->getKeyName(),
            $resource->getCreatedAtColumn(),
            $resource->getUpdatedAtColumn(),
        ], true);
        $columns = [];

        foreach ($declared as $column) {
            if (! $column instanceof ComputedTableColumn) {
                throw new InvalidArgumentException('Computed table column declarations must use ComputedTableColumn.');
            }

            if (isset($reserved[$column->key])) {
                throw new InvalidArgumentException('Computed table column keys may not collide with fields or reserved keys.');
            }

            if (array_key_exists($column->key, $columns)) {
                throw new InvalidArgumentException('Computed table column keys must be unique.');
            }

            $columns[$column->key] = $column;
        }

        return $columns;
    }

    public function find(Resource $resource, string $key): ?ComputedTableColumn
    {
        return $this->computed($resource)[$key] ?? null;
    }
}
