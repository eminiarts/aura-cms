<?php

namespace Aura\Base\Table;

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Number;
use Aura\Base\Resource;
use Aura\Base\Support\ExactDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TableFieldSorter
{
    /**
     * @param  array<string, mixed>  $field
     * @param  'asc'|'desc'  $direction
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        Field $fieldInstance,
        string $direction,
    ): void {
        $slug = $field['slug'];
        $qualifiedKeyName = $resource->getQualifiedKeyName();
        $table = $resource->getTable();
        $customMethod = 'sort_'.$slug;

        if (method_exists($resource, $customMethod)) {
            $resource->{$customMethod}($query, $direction);
            $query->orderBy($qualifiedKeyName);

            return;
        }

        if ($resource->isTaxonomyField($slug)) {
            $resourceType = $field['resource'];

            $query->leftJoin('post_relations as pr', function ($join) use ($qualifiedKeyName, $resource, $resourceType): void {
                $join->on($qualifiedKeyName, 'pr.related_id')
                    ->where('pr.related_type', $resource->getMorphClass())
                    ->where('pr.resource_type', $resourceType)
                    ->where('pr.slug', Str::plural(Str::lower(class_basename($resourceType))));
            })->select($table.'.*')
                ->groupBy($qualifiedKeyName)
                ->orderByRaw('MIN(pr.resource_id) '.$direction)
                ->orderBy($qualifiedKeyName, 'desc');

            return;
        }

        if ($resource->usesMeta() && $resource->isMetaField($slug)) {
            $metaTable = $resource->getMetaTable();

            $query->leftJoin($metaTable, function ($join) use ($metaTable, $qualifiedKeyName, $resource, $slug): void {
                $join->on($qualifiedKeyName, $metaTable.'.'.$resource->getMetaForeignKey())
                    ->where($metaTable.'.metable_type', $resource->getMorphClass())
                    ->where($metaTable.'.key', $slug);
            })->select($table.'.*');

            $this->applyColumnSort($query, $resource, $field, $fieldInstance, $metaTable.'.value', $direction);
            $query->orderBy($qualifiedKeyName, 'desc');

            return;
        }

        $this->applyColumnSort($query, $resource, $field, $fieldInstance, $resource->qualifyColumn($slug), $direction);
        $query->orderBy($qualifiedKeyName, $resource->isNumberField($slug) ? 'desc' : 'asc');
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  'asc'|'desc'  $direction
     */
    private function applyColumnSort(
        Builder $query,
        Resource $resource,
        array $field,
        Field $fieldInstance,
        string $column,
        string $direction,
    ): void {
        $connection = DB::connection($resource->getConnectionName());

        if ($resource->isNumberField($field['slug']) && ExactDecimal::supportsSql($connection)) {
            ExactDecimal::applySorting(
                $query,
                $connection,
                $query->getQuery()->getGrammar()->wrap($column),
                $direction,
                $fieldInstance instanceof Number
                    ? $fieldInstance->exactQueryConfiguration($field)
                    : null,
            );

            return;
        }

        if ($resource->isNumberField($field['slug'])) {
            $query->orderByRaw('CAST('.$query->getQuery()->getGrammar()->wrap($column).' AS DECIMAL(65,30)) '.$direction);

            return;
        }

        $query->orderBy($column, $direction);
    }
}
