<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\TableColumnCapabilityResolver;
use Aura\Base\FieldProviderRegistry;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FieldFilterCapabilityResolver;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class ResourceTableColumnCapabilityResolver implements TableColumnCapabilityResolver
{
    /** @var list<string> */
    private const SAFE_PHYSICAL_COLUMNS = [
        'id',
        'title',
        'content',
        'status',
        'type',
        'slug',
        'created_at',
        'updated_at',
    ];

    public function resolve(Resource $resource, string $key): ?TableColumnCapability
    {
        $field = $resource->fieldBySlug($key);
        $fieldInstance = $resource->fieldClassBySlug($key);

        if (! is_array($field) || ! $fieldInstance instanceof Field) {
            $fieldResolution = app(FieldProviderRegistry::class)->resolve(
                $resource::class,
                fn (): array => $resource::getFields(),
            );
            $activeFieldSlugs = array_column($fieldResolution->fields, 'slug');

            if (in_array($key, $fieldResolution->managedFieldSlugs, true)
                && ! in_array($key, $activeFieldSlugs, true)) {
                return null;
            }

            $declaredColumns = array_unique([
                ...collect($resource->getColumns())->keys()->all(),
                ...self::SAFE_PHYSICAL_COLUMNS,
            ]);

            if (($key !== $resource->getKeyName() && ! in_array($key, $declaredColumns, true))
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
            validateFilter: static fn (array $filter): bool => $filterCapability->accepts($filter),
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
