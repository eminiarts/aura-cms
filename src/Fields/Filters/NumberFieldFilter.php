<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Exceptions\InvalidFieldValue;
use Aura\Base\Fields\Number;
use Aura\Base\Resource;
use Aura\Base\Support\ExactDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class NumberFieldFilter implements AppliesFieldFilter
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
        $slug = $field['slug'] ?? null;
        $fieldClass = is_string($slug) ? $resource->fieldClassBySlug($slug) : null;

        if (! is_string($slug) || ! $fieldClass instanceof Number) {
            $query->whereRaw('1 = 0');

            return;
        }

        $storage = $resource->isMetaField($slug)
            ? FieldValueStorage::Meta
            : FieldValueStorage::Physical;
        $connection = DB::connection($resource->getConnectionName());

        if (in_array($filter['operator'], ['is_empty', 'is_not_empty'], true)) {
            $this->applyEmptyCondition($query, $slug, $filter['operator'], $storage, $connection->getDriverName());

            return;
        }

        $operator = match ($filter['operator']) {
            'equals' => '=',
            'not_equals' => '!=',
            'greater_than' => '>',
            'less_than' => '<',
            'greater_than_or_equal' => '>=',
            'less_than_or_equal' => '<=',
            default => null,
        };

        if ($operator === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        try {
            $value = $fieldClass->normalizeForStorage(
                $filter['value'] ?? null,
                $field,
                $resource,
                $storage,
            );
        } catch (InvalidFieldValue) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($storage === FieldValueStorage::Physical && $connection->getDriverName() !== 'sqlite') {
            $query->where($query->getModel()->qualifyColumn($slug), $operator, $value);

            return;
        }

        if (! ExactDecimal::supportsSql($connection)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $configuration = $fieldClass->exactQueryConfiguration($field);

        if ($storage === FieldValueStorage::Meta) {
            $query->whereHas('meta', function (Builder $query) use ($slug, $operator, $value, $configuration, $connection): void {
                $query->where('key', $slug);
                ExactDecimal::applyComparison(
                    $query,
                    $connection,
                    $query->getQuery()->getGrammar()->wrap('value'),
                    $operator,
                    $value,
                    $configuration,
                );
            });

            return;
        }

        ExactDecimal::applyComparison(
            $query,
            $connection,
            $query->getQuery()->getGrammar()->wrap($query->getModel()->qualifyColumn($slug)),
            $operator,
            $value,
            $configuration,
        );
    }

    private function applyEmptyCondition(
        Builder $query,
        string $slug,
        string $operator,
        FieldValueStorage $storage,
        string $driver,
    ): void {
        if ($storage === FieldValueStorage::Meta) {
            if ($operator === 'is_empty') {
                $query->where(function (Builder $query) use ($slug): void {
                    $query->whereDoesntHave('meta', fn (Builder $query) => $query->where('key', $slug))
                        ->orWhereHas('meta', function (Builder $query) use ($slug): void {
                            $query->where('key', $slug)
                                ->where(function (Builder $query): void {
                                    $query->whereNull('value')->orWhere('value', '');
                                });
                        });
                });

                return;
            }

            $query->whereHas('meta', function (Builder $query) use ($slug): void {
                $query->where('key', $slug)
                    ->whereNotNull('value')
                    ->where('value', '!=', '');
            });

            return;
        }

        $column = $query->getModel()->qualifyColumn($slug);

        if ($driver === 'sqlite') {
            if ($operator === 'is_empty') {
                $query->where(fn (Builder $query) => $query->whereNull($column)->orWhere($column, ''));

                return;
            }

            $query->whereNotNull($column)->where($column, '!=', '');

            return;
        }

        $operator === 'is_empty'
            ? $query->whereNull($column)
            : $query->whereNotNull($column);
    }
}
