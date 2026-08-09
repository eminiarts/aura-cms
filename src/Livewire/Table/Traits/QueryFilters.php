<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FilterCapability;
use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
    {
        if (empty($this->filters['custom'])) {
            return $query;
        }

        $groups = $this->filters['custom'];
        $condition = function (Builder $query) use ($groups): void {
            $this->applyFilterGroup($query, $groups[0]);
        };

        for ($index = 1; $index < count($groups); $index++) {
            $group = $groups[$index];
            $operator = $group['operator'] ?? 'and';
            $previousCondition = $condition;

            $condition = function (Builder $query) use ($previousCondition, $group, $operator): void {
                $query->where(function (Builder $query) use ($previousCondition, $group, $operator) {
                    $query->where(function (Builder $query) use ($previousCondition) {
                        $previousCondition($query);
                    });

                    $method = $operator === 'and' ? 'where' : 'orWhere';

                    $query->{$method}(function (Builder $query) use ($group) {
                        $this->applyFilterGroup($query, $group);
                    });
                });
            };
        }

        $query->where(function (Builder $query) use ($condition) {
            $condition($query);
        });

        return $query;
    }

    protected function applyFilter(Builder $query, array $filter, string $groupOperator): void
    {
        $method = $groupOperator === 'or' ? 'orWhere' : 'where';

        $query->{$method}(function (Builder $query) use ($filter) {
            $fieldSlug = $filter['name'] ?? null;

            if (! is_string($fieldSlug) || trim($fieldSlug) === '') {
                $query->whereRaw('1 = 0');

                return;
            }

            $field = $this->model->fieldBySlug($fieldSlug);
            $fieldInstance = $this->model->fieldClassBySlug($fieldSlug);

            if (! $field || ! $fieldInstance instanceof Field) {
                $query->whereRaw('1 = 0');

                return;
            }

            $fieldInstance->filterCapability($this->model, $field)->apply(
                $query,
                $this->model,
                $field,
                $filter,
            );
        });
    }

    protected function applyFilterGroup(Builder $query, array $group): void
    {
        $filters = $group['filters'] ?? [];

        if (! is_array($filters)) {
            $query->whereRaw('1 = 0');

            return;
        }

        foreach ($filters as $filterIndex => $filter) {
            if (! is_array($filter)) {
                $query->whereRaw('1 = 0');

                continue;
            }

            if (! $this->isValidFilter($filter)) {
                continue;
            }

            $groupOperator = $filterIndex > 0 ? ($filter['main_operator'] ?? 'and') : 'and';
            $this->applyFilter($query, $filter, $groupOperator);
        }
    }

    protected function isValidFilter(array $filter): bool
    {
        $fieldSlug = $filter['name'] ?? null;
        $operator = $filter['operator'] ?? null;

        if ($fieldSlug === null || $operator === null) {
            return false;
        }

        if (! is_string($fieldSlug) || ! is_string($operator)) {
            return true;
        }

        if (trim($fieldSlug) === '' || trim($operator) === '') {
            return false;
        }

        if (in_array($operator, ['is_empty', 'is_not_empty', 'date_is_empty', 'date_is_not_empty'], true)) {
            return true;
        }

        return FilterCapability::hasValue($filter['value'] ?? null);
    }
}
