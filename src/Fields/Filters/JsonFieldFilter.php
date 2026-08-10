<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

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
        if (! $this->isValidPayload($field, $filter, $capability)) {
            $query->whereRaw('1 = 0');

            return;
        }

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

        if ($query->getQuery()->getGrammar() instanceof SQLiteGrammar) {
            $this->applySqliteOperator($query, $column, $filter['operator'], $values);

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

    /**
     * @param  list<mixed>  $values
     */
    private function applySqliteOperator(Builder $query, string $column, string $operator, array $values): void
    {
        if ($operator === 'contains') {
            $query->where(function (Builder $query) use ($column, $values): void {
                foreach ($values as $index => $value) {
                    [$sql, $bindings] = $this->sqliteJsonContainsExpression($query, $column, $value);

                    if ($index === 0) {
                        $query->whereRaw($sql, $bindings);
                    } else {
                        $query->orWhereRaw($sql, $bindings);
                    }
                }
            });

            return;
        }

        if ($operator === 'does_not_contain') {
            foreach ($values as $value) {
                [$sql, $bindings] = $this->sqliteJsonContainsExpression($query, $column, $value);
                $query->whereRaw('not '.$sql, $bindings);
            }

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $filter
     */
    private function isValidPayload(array $field, array $filter, FilterCapability $capability): bool
    {
        $operator = $filter['operator'] ?? null;

        if (! is_string($field['slug'] ?? null)
            || ($filter['name'] ?? null) !== $field['slug']
            || ! is_string($operator)
            || ! array_key_exists($operator, $capability->toArray()['operators'])) {
            return false;
        }

        if (in_array($operator, ['is_empty', 'is_not_empty'], true)) {
            return true;
        }

        $values = $filter['value'] ?? null;

        if (! is_array($values) || ! array_is_list($values) || $values === []) {
            return false;
        }

        foreach ($values as $value) {
            if ((! is_string($value) || trim($value) === '')
                && ! is_int($value)
                && ! is_bool($value)
                && (! is_float($value) || ! is_finite($value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{string, list<mixed>}
     */
    private function sqliteJsonContainsExpression(Builder $query, string $column, mixed $value): array
    {
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);
        $jsonType = match (true) {
            $value === true => 'true',
            $value === false => 'false',
            is_int($value) => 'integer',
            is_float($value) => 'real',
            default => 'text',
        };

        return [
            sprintf(
                'exists (select 1 from json_each(%s) as "aura_json_value" where "aura_json_value"."type" = ? and "aura_json_value"."value" is ?)',
                $wrappedColumn,
            ),
            [$jsonType, $value],
        ];
    }
}
