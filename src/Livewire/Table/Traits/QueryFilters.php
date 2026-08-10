<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FieldFilterCapabilityResolver;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Livewire\Table\Table;
use Illuminate\Database\Eloquent\Builder;
use ReflectionMethod;

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
            $this->applyFilterBasedOnType($query, $filter);
        });
    }

    /**
     * Compatibility dispatch for table subclasses; prefer field capabilities or query handlers.
     */
    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        $fieldSlug = $filter['name'] ?? null;

        if (! is_string($fieldSlug) || trim($fieldSlug) === '' || ! $this->resolvedFilterField($fieldSlug)) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($this->isRelationBackedFilter($filter)) {
            $this->applyRelationFieldFilter($query, $filter);

            return;
        }

        if ($this->model->isMetaField($fieldSlug)) {
            $this->applyMetaFieldFilter($query, $filter);

            return;
        }

        if ($this->model->isTableField($fieldSlug) || $this->model->usesCustomTable()) {
            $this->applyTableFieldFilter($query, $filter);

            return;
        }

        $this->applyMetaFieldFilter($query, $filter);
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

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->where(function (Builder $query) use ($filter): void {
            $query->whereDoesntHave('meta', function (Builder $query) use ($filter): void {
                $query->where('key', $filter['name']);
            })->orWhereHas('meta', function (Builder $query) use ($filter): void {
                $query->where('key', $filter['name'])
                    ->where(function (Builder $query): void {
                        $query->whereNull('value')->orWhere('value', '');
                    });
            });
        });
    }

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyIsNotEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->whereHas('meta', function (Builder $query) use ($filter): void {
            $query->where('key', $filter['name'])
                ->whereNotNull('value')
                ->where('value', '!=', '');
        });
    }

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        if (in_array($filter['operator'] ?? null, ['is_empty', 'date_is_empty'], true)
            && $this->legacyFilterHookIsOverridden('applyIsEmptyMetaFilter')) {
            $this->applyIsEmptyMetaFilter($query, $filter);

            return $query;
        }

        if (in_array($filter['operator'] ?? null, ['is_not_empty', 'date_is_not_empty'], true)
            && $this->legacyFilterHookIsOverridden('applyIsNotEmptyMetaFilter')) {
            $this->applyIsNotEmptyMetaFilter($query, $filter);

            return $query;
        }

        if ($this->legacyFilterHookIsOverridden('applyStandardMetaFilter')
            || $this->legacyFilterHookIsOverridden('applyOperatorCondition')
            || ($this->legacyFilterHookIsOverridden('applyRelationFieldFilter')
                && $this->hasLegacyRelationshipOption($filter))) {
            $this->applyStandardMetaFilter($query, $filter);

            return $query;
        }

        $this->applyResolvedFilterCapability($query, $filter);

        return $query;
    }

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        $value = $filter['value'] ?? null;
        $operator = $filter['operator'] ?? null;

        if (! is_string($operator)) {
            $query->whereRaw('1 = 0');

            return;
        }

        if (in_array($operator, ['is_empty', 'date_is_empty'], true)) {
            $query->where(function (Builder $query): void {
                $query->whereNull('value')->orWhere('value', '');
            });

            return;
        }

        if (in_array($operator, ['is_not_empty', 'date_is_not_empty'], true)) {
            $query->whereNotNull('value')->where('value', '!=', '');

            return;
        }

        if (in_array($operator, ['in', 'not_in'], true)) {
            $values = is_array($value) ? $value : (is_scalar($value) ? explode(',', (string) $value) : []);

            if (! array_is_list($values) || $values === [] || collect($values)->contains(fn ($item) => ! is_scalar($item))) {
                $query->whereRaw('1 = 0');

                return;
            }

            $operator === 'in' ? $query->whereIn('value', $values) : $query->whereNotIn('value', $values);

            return;
        }

        if (! is_scalar($value)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $value = (string) $value;

        match ($operator) {
            'contains' => $query->where('value', 'like', '%'.$value.'%'),
            'does_not_contain' => $query->where('value', 'not like', '%'.$value.'%'),
            'starts_with' => $query->where('value', 'like', $value.'%'),
            'ends_with' => $query->where('value', 'like', '%'.$value),
            'is', 'equals' => $query->where('value', $value),
            'is_not', 'not_equals' => $query->where('value', '!=', $value),
            'greater_than' => $query->where('value', '>', $value),
            'less_than' => $query->where('value', '<', $value),
            'greater_than_or_equal' => $query->where('value', '>=', $value),
            'less_than_or_equal' => $query->where('value', '<=', $value),
            'like' => $query->where('value', 'like', $value),
            'not_like' => $query->where('value', 'not like', $value),
            'regex' => $query->where('value', 'regexp', $value),
            'not_regex' => $query->where('value', 'not regexp', $value),
            'date_is' => $query->whereDate('value', $value),
            'date_is_not' => $query->whereDate('value', '!=', $value),
            'date_before' => $query->whereDate('value', '<', $value),
            'date_after' => $query->whereDate('value', '>', $value),
            'date_on_or_before' => $query->whereDate('value', '<=', $value),
            'date_on_or_after' => $query->whereDate('value', '>=', $value),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Compatibility adapter; prefer a relationship field capability or query handler.
     */
    protected function applyRelationFieldFilter(Builder $query, array $filter): void
    {
        $this->applyResolvedFilterCapability($query, $filter);
    }

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyStandardMetaFilter(Builder $query, array $filter): void
    {
        if ($this->hasLegacyRelationshipOption($filter)
            && $this->legacyFilterHookIsOverridden('applyRelationFieldFilter')) {
            $this->applyRelationFieldFilter($query, $filter);

            return;
        }

        if (! $this->legacyFilterHookIsOverridden('applyOperatorCondition')) {
            $this->applyResolvedFilterCapability($query, $filter);

            return;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter): void {
            $query->where('key', $filter['name']);
            $this->applyOperatorCondition($query, $filter);
        });
    }

    /**
     * Compatibility adapter; prefer a field capability or query handler.
     */
    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
        $this->applyResolvedFilterCapability($query, $filter);

        return $query;
    }

    /**
     * Compatibility adapter; prefer inspecting the resolved field capability.
     */
    protected function isRelationBackedFilter(array $filter): bool
    {
        $resolved = $this->resolvedFilterField($filter['name'] ?? null);

        if ($resolved === null) {
            return false;
        }

        [$field, $fieldInstance] = $resolved;

        return (new FieldFilterCapabilityResolver)
            ->resolve($fieldInstance, $this->model, $field)
            ->toArray()['type'] === FilterCapability::RELATIONSHIP;
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

        if (! array_key_exists($operator, (new FieldFilterCapabilityResolver)->resolve($fieldInstance, $this->model, $field)->toArray()['operators'])) {
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

    /**
     * @param  array<string, mixed>  $filter
     */
    private function applyResolvedFilterCapability(Builder $query, array $filter): void
    {
        $resolved = $this->resolvedFilterField($filter['name'] ?? null);

        if ($resolved === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        [$field, $fieldInstance] = $resolved;

        (new FieldFilterCapabilityResolver)->resolve($fieldInstance, $this->model, $field)->apply(
            $query,
            $this->model,
            $field,
            $filter,
        );
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function hasLegacyRelationshipOption(array $filter): bool
    {
        $resourceType = $filter['options']['resource_type'] ?? null;

        return is_string($resourceType) && trim($resourceType) !== '';
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function isIncompleteFilter(array $filter): bool
    {
        $fieldSlug = $filter['name'] ?? null;
        $operator = $filter['operator'] ?? null;
        $value = $filter['value'] ?? null;

        if (! is_string($fieldSlug)
            || ! is_string($operator)
            || ($value !== null && (! is_string($value) || trim($value) !== ''))) {
            return false;
        }

        $field = $this->model->fieldBySlug($fieldSlug);
        $fieldInstance = $this->model->fieldClassBySlug($fieldSlug);

        return $field
            && $fieldInstance instanceof Field
            && array_key_exists(
                $operator,
                (new FieldFilterCapabilityResolver)->resolve($fieldInstance, $this->model, $field)->toArray()['operators'],
            );
    }

    private function legacyFilterHookIsOverridden(string $method): bool
    {
        return (new ReflectionMethod($this, $method))->getDeclaringClass()->getName() !== Table::class;
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
        if (array_diff(array_keys($group), ['operator', 'filters']) !== []) {
            return null;
        }

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

            if (array_diff(array_keys($filter), ['name', 'operator', 'value', 'main_operator', 'options']) !== []) {
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
                $options = $filter['options'] ?? [];

                if (! array_key_exists('name', $filter)
                    || ! array_key_exists('operator', $filter)
                    || ($filter['value'] ?? null) !== null
                    || ! array_key_exists('options', $filter)
                    || ! is_array($options)
                    || $options !== []) {
                    return null;
                }

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

            if (! $this->isValidFilter($filter)) {
                if ($this->isIncompleteFilter($filter)) {
                    continue;
                }

                return null;
            }

            $normalized[] = $filter;
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

        if ($customFilters === []) {
            return [];
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

    /**
     * @return array{array<string, mixed>, Field}|null
     */
    private function resolvedFilterField(mixed $fieldSlug): ?array
    {
        if (! is_string($fieldSlug) || trim($fieldSlug) === '') {
            return null;
        }

        $field = $this->model->fieldBySlug($fieldSlug);
        $fieldInstance = $this->model->fieldClassBySlug($fieldSlug);

        if (! is_array($field) || ! $fieldInstance instanceof Field) {
            return null;
        }

        return [$field, $fieldInstance];
    }
}
