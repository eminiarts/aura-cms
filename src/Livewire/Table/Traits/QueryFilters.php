<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FilterCapability;
use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
    {
        if (! is_array($this->filters)) {
            return $this->matchNoFilterRows($query);
        }

        $customFilters = $this->filters['custom'] ?? [];

        if ($customFilters === []) {
            return $query;
        }

        if (! is_array($customFilters)) {
            return $this->matchNoFilterRows($query);
        }

        $groups = $this->normalizeFilterGroups($customFilters);

        if ($groups === null) {
            return $this->matchNoFilterRows($query);
        }

        if ($groups === []) {
            return $query;
        }

        $query->where(function (Builder $query) use ($groups): void {
            $this->applyFilterGroupsLeftAssociatively($query, $groups, count($groups) - 1);
        });

        return $query;
    }

    protected function applyFilter(Builder $query, array $filter, string $groupOperator): void
    {
        $method = $groupOperator === 'or' ? 'orWhere' : 'where';

        $query->{$method}(function (Builder $query) use ($filter): void {
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

    /**
     * @param  array{operator: 'and'|'or', filters: list<array<string, mixed>>}  $group
     */
    protected function applyFilterGroup(Builder $query, array $group): void
    {
        foreach ($group['filters'] as $filterIndex => $filter) {
            $groupOperator = $filterIndex > 0 ? $filter['main_operator'] : 'and';
            $this->applyFilter($query, $filter, $groupOperator);
        }
    }

    protected function isValidFilter(array $filter): bool
    {
        $fieldSlug = $filter['name'] ?? null;
        $operator = $filter['operator'] ?? null;

        if (! is_string($fieldSlug) || ! is_string($operator) || trim($fieldSlug) === '' || trim($operator) === '') {
            return false;
        }

        if (in_array($operator, ['is_empty', 'is_not_empty', 'date_is_empty', 'date_is_not_empty'], true)) {
            return true;
        }

        $field = $this->model->fieldBySlug($fieldSlug);
        $fieldInstance = $this->model->fieldClassBySlug($fieldSlug);

        if (! $field || ! $fieldInstance instanceof Field) {
            return true;
        }

        if (! array_key_exists($operator, $fieldInstance->filterCapability($this->model, $field)->toArray()['operators'])) {
            return true;
        }

        return FilterCapability::hasValue($filter['value'] ?? null);
    }

    /**
     * @param  list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>  $groups
     */
    private function applyFilterGroupsLeftAssociatively(Builder $query, array $groups, int $groupIndex): void
    {
        if ($groupIndex === 0) {
            $this->applyFilterGroup($query, $groups[0]);

            return;
        }

        $query->where(function (Builder $query) use ($groups, $groupIndex): void {
            $this->applyFilterGroupsLeftAssociatively($query, $groups, $groupIndex - 1);
        });

        $group = $groups[$groupIndex];

        if ($group['operator'] === 'or') {
            $query->orWhere(function (Builder $query) use ($group): void {
                $this->applyFilterGroup($query, $group);
            });

            return;
        }

        $query->where(function (Builder $query) use ($group): void {
            $this->applyFilterGroup($query, $group);
        });
    }

    private function matchNoFilterRows(Builder $query): Builder
    {
        $query->whereRaw('1 = 0');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array{operator: 'and'|'or', filters: list<array<string, mixed>>}|null
     */
    private function normalizeFilterGroup(array $group): ?array
    {
        $operator = $group['operator'] ?? 'and';
        $filters = $group['filters'] ?? null;

        if (! is_string($operator) || ! in_array($operator, ['and', 'or'], true) || ! is_array($filters) || ! array_is_list($filters)) {
            return null;
        }

        $normalized = [];

        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                return null;
            }

            $mainOperator = $filter['main_operator'] ?? 'and';

            if (! is_string($mainOperator) || ! in_array($mainOperator, ['and', 'or'], true)) {
                return null;
            }

            $fieldSlug = $filter['name'] ?? null;
            $filterOperator = $filter['operator'] ?? null;
            $missingField = $fieldSlug === null || $fieldSlug === '';
            $missingOperator = $filterOperator === null || $filterOperator === '';

            if ($missingField && $missingOperator) {
                continue;
            }

            if ($missingField || $missingOperator) {
                return null;
            }

            if (! is_string($fieldSlug) || ! is_string($filterOperator)) {
                return null;
            }

            $filter['name'] = trim($fieldSlug);
            $filter['operator'] = trim($filterOperator);
            $filter['main_operator'] = $mainOperator;

            if ($this->isValidFilter($filter)) {
                $normalized[] = $filter;
            }
        }

        return [
            'operator' => $operator,
            'filters' => $normalized,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $customFilters
     * @return list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>|null
     */
    private function normalizeFilterGroups(array $customFilters): ?array
    {
        if (! array_is_list($customFilters)) {
            return null;
        }

        $containsGroups = false;
        $containsFlatFilters = false;

        foreach ($customFilters as $item) {
            if (! is_array($item)) {
                return null;
            }

            if (array_key_exists('filters', $item)) {
                $containsGroups = true;
            } else {
                $containsFlatFilters = true;
            }
        }

        if ($containsGroups && $containsFlatFilters) {
            return null;
        }

        $rawGroups = $containsFlatFilters ? [['filters' => $customFilters]] : $customFilters;
        $groups = [];

        foreach ($rawGroups as $rawGroup) {
            $group = $this->normalizeFilterGroup($rawGroup);

            if ($group === null) {
                return null;
            }

            if ($group['filters'] !== []) {
                $groups[] = $group;
            }
        }

        return $groups;
    }
}
