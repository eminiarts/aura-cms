<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\TableColumnCapabilityResolver;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FieldFilterCapabilityResolver;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class ResourceTableColumnCapabilityResolver implements TableColumnCapabilityResolver
{
    public function resolve(Resource $resource, string $key): ?TableColumnCapability
    {
        $field = $resource->fieldBySlug($key);
        $fieldInstance = $resource->fieldClassBySlug($key);

        if (! is_array($field) || ! $fieldInstance instanceof Field) {
            if (($key !== $resource->getKeyName() && ! in_array($key, $resource->getFillable(), true))
                || ! Schema::connection($resource->getConnectionName())->hasColumn($resource->getTable(), $key)) {
                return null;
            }

            return TableColumnCapability::computed(
                key: $key,
                applySort: static function (Builder $query, Resource $resource, string $direction) use ($key): void {
                    $query->orderBy($resource->qualifyColumn($key), $direction)
                        ->orderBy($resource->getQualifiedKeyName());
                },
            );
        }

        $filterCapability = (new FieldFilterCapabilityResolver)->resolve($fieldInstance, $resource, $field);

        return TableColumnCapability::computed(
            key: $key,
            operators: array_keys($filterCapability->toArray()['operators']),
            applyFilter: static function (Builder $query, Resource $resource, array $filter) use ($field, $filterCapability): void {
                if (! $filterCapability->accepts($filter)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $filterCapability->apply($query, $resource, $field, $filter);
            },
            applySort: static function (Builder $query, Resource $resource, string $direction) use ($field, $fieldInstance): void {
                (new TableFieldSorter)->apply($query, $resource, $field, $fieldInstance, $direction);
            },
        );
    }
}
