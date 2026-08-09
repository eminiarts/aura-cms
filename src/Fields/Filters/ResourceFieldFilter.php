<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;

final class ResourceFieldFilter implements AppliesFieldFilter
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
        if (in_array($filter['operator'], ['is_empty', 'date_is_empty'], true)) {
            $query->where(function (Builder $query) use ($filter) {
                $query->whereDoesntHave('meta', function (Builder $query) use ($filter) {
                    $query->where('key', $filter['name']);
                })->orWhereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', $filter['name'])
                        ->where(function (Builder $query) {
                            $query->whereNull('value')->orWhere('value', '');
                        });
                });
            });

            return;
        }

        if (in_array($filter['operator'], ['is_not_empty', 'date_is_not_empty'], true)) {
            $query->whereHas('meta', function (Builder $query) use ($filter) {
                $query->where('key', $filter['name'])
                    ->whereNotNull('value')
                    ->where('value', '!=', '');
            });

            return;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', $filter['name']);
            $this->applyOperator($query, 'value', $filter);
        });
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    private function applyOperator(Builder $query, string $column, array $filter): void
    {
        $value = $filter['value'] ?? null;

        match ($filter['operator']) {
            'contains' => $query->where($column, 'like', '%'.$this->scalarValue($value).'%'),
            'does_not_contain' => $query->where($column, 'not like', '%'.$this->scalarValue($value).'%'),
            'starts_with' => $query->where($column, 'like', $this->scalarValue($value).'%'),
            'ends_with' => $query->where($column, 'like', '%'.$this->scalarValue($value)),
            'is', 'equals' => $query->where($column, $value),
            'is_not', 'not_equals' => $query->where($column, '!=', $value),
            'greater_than' => $query->where($column, '>', $value),
            'less_than' => $query->where($column, '<', $value),
            'greater_than_or_equal' => $query->where($column, '>=', $value),
            'less_than_or_equal' => $query->where($column, '<=', $value),
            'in' => $query->whereIn($column, $this->listValue($value)),
            'not_in' => $query->whereNotIn($column, $this->listValue($value)),
            'like' => $query->where($column, 'like', $this->scalarValue($value)),
            'not_like' => $query->where($column, 'not like', $this->scalarValue($value)),
            'regex' => $query->where($column, 'regexp', $this->scalarValue($value)),
            'not_regex' => $query->where($column, 'not regexp', $this->scalarValue($value)),
            'date_is' => $query->whereDate($column, $this->scalarValue($value)),
            'date_is_not' => $query->whereDate($column, '!=', $this->scalarValue($value)),
            'date_before' => $query->whereDate($column, '<', $this->scalarValue($value)),
            'date_after' => $query->whereDate($column, '>', $this->scalarValue($value)),
            'date_on_or_before' => $query->whereDate($column, '<=', $this->scalarValue($value)),
            'date_on_or_after' => $query->whereDate($column, '>=', $this->scalarValue($value)),
            'date_between' => $query
                ->whereDate($column, '>=', $this->scalarValue($value['from'] ?? null))
                ->whereDate($column, '<=', $this->scalarValue($value['to'] ?? null)),
            'is_empty', 'date_is_empty' => $query->where(function (Builder $query) use ($column) {
                $query->whereNull($column)->orWhere($column, '');
            }),
            'is_not_empty', 'date_is_not_empty' => $query->whereNotNull($column)->where($column, '!=', ''),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @return list<mixed>
     */
    private function listValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        return explode(',', (string) $value);
    }

    private function scalarValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string) reset($value);
        }

        return (string) $value;
    }
}
