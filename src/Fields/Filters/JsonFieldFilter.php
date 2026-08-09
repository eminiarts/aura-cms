<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;

final class JsonFieldFilter implements AppliesFieldFilter
{
    /**
     * @param  array<string, mixed>  $field
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        if ($resource->isMetaField($field['slug'])) {
            $this->applyMetaFilter($query, $filter);

            return;
        }

        if ($resource->isTableField($field['slug']) || $resource->usesCustomTable()) {
            $this->applyOperator($query, $query->getModel()->qualifyColumn($field['slug']), $filter);

            return;
        }

        $this->applyMetaFilter($query, $filter);
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    private function applyMetaFilter(Builder $query, array $filter): void
    {
        if ($filter['operator'] === 'is_empty') {
            $query->where(function (Builder $query) use ($filter): void {
                $query->whereDoesntHave('meta', function (Builder $query) use ($filter): void {
                    $query->where('key', $filter['name']);
                })->orWhereHas('meta', function (Builder $query) use ($filter): void {
                    $query->where('key', $filter['name'])
                        ->where(function (Builder $query): void {
                            $query->whereNull('value')->orWhereIn('value', ['', '[]', 'null']);
                        });
                });
            });

            return;
        }

        if ($filter['operator'] === 'is_not_empty') {
            $query->whereHas('meta', function (Builder $query) use ($filter): void {
                $query->where('key', $filter['name'])
                    ->whereNotNull('value')
                    ->whereNotIn('value', ['', '[]', 'null']);
            });

            return;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter): void {
            $query->where('key', $filter['name']);
            $this->applyOperator($query, $query->getModel()->qualifyColumn('value'), $filter);
        });
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    private function applyOperator(Builder $query, string $column, array $filter): void
    {
        if ($filter['operator'] === 'is_empty') {
            $query->where(function (Builder $query) use ($column): void {
                $query->whereNull($column)->orWhereIn($column, ['', '[]', 'null']);
            });

            return;
        }

        if ($filter['operator'] === 'is_not_empty') {
            $query->whereNotNull($column)->whereNotIn($column, ['', '[]', 'null']);

            return;
        }

        $values = is_array($filter['value'] ?? null) ? array_values($filter['value']) : [];

        if ($values === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($filter['operator'] === 'contains') {
            $query->where(function (Builder $query) use ($column, $values): void {
                foreach ($values as $index => $value) {
                    $method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
                    $query->{$method}($column, $value);
                }
            });

            return;
        }

        if ($filter['operator'] === 'does_not_contain') {
            foreach ($values as $value) {
                $query->whereJsonDoesntContain($column, $value);
            }

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
