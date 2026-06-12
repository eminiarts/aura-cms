<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
    {
        if (empty($this->filters['custom'])) {
            return $query;
        }

        $groups = $this->filters['custom'];

        // Start by building the conditions from the first group
        $condition = function ($query) use ($groups) {
            $this->applyFilterGroup($query, $groups[0]);
        };

        for ($i = 1; $i < count($groups); $i++) {
            $group = $groups[$i];
            $operator = $group['operator'] ?? 'and';

            // Create a new condition that wraps the previous condition and combines it with the current group
            $previousCondition = $condition;

            $condition = function ($query) use ($previousCondition, $group, $operator) {
                $query->where(function ($q) use ($previousCondition, $group, $operator) {
                    // Wrap previous conditions
                    $q->where(function ($subQ) use ($previousCondition) {
                        $previousCondition($subQ);
                    });

                    // Combine with current group using its operator
                    $method = $operator === 'and' ? 'where' : 'orWhere';

                    $q->$method(function ($subQ) use ($group) {
                        $this->applyFilterGroup($subQ, $group);
                    });
                });
            };
        }

        // Apply the accumulated condition to the main query
        $query->where(function ($q) use ($condition) {
            $condition($q);
        });

        return $query;
    }

    protected function applyFilter(Builder $query, array $filter, string $groupOperator): void
    {
        $method = $groupOperator === 'or' ? 'orWhere' : 'where';

        $query->$method(function ($subQuery) use ($filter) {
            $this->applyFilterBasedOnType($subQuery, $filter);
        });
    }

    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        if ($this->model->usesCustomTable() || $this->model->isTableField($filter['name'])) {
            $this->applyTableFieldFilter($query, $filter);
        } else {
            $this->applyMetaFieldFilter($query, $filter);
        }
    }

    protected function applyFilterGroup(Builder $query, array $group): void
    {
        foreach ($group['filters'] as $filterIndex => $filter) {
            if ($this->isValidFilter($filter)) {
                if ($filterIndex > 0) {
                    $groupOperator = $filter['main_operator'] ?? 'and';
                    $this->applyFilter($query, $filter, $groupOperator);
                } else {
                    $this->applyFilter($query, $filter, 'and');
                }
            }
        }
    }

    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->where(function ($query) use ($filter) {
            $query->whereDoesntHave('meta', function (Builder $query) use ($filter) {
                $query->where('key', '=', $filter['name']);
            })
                ->orWhereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', '=', $filter['name'])
                        ->where(function ($query) {
                            $query->where('value', '=', '')
                                ->orWhereNull('value');
                        });
                });
        });
    }

    protected function applyIsNotEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name'])
                ->where(function ($query) {
                    $query->where('value', '!=', '')
                        ->whereNotNull('value');
                });
        });
    }

    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        switch ($filter['operator']) {
            case 'is_empty':
                $this->applyIsEmptyMetaFilter($query, $filter);
                break;
            case 'is_not_empty':
                $this->applyIsNotEmptyMetaFilter($query, $filter);
                break;
            default:
                $this->applyStandardMetaFilter($query, $filter);
        }

        return $query;
    }

    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        switch ($filter['operator']) {
            case 'contains':
                $query->where('value', 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where('value', 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where('value', 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where('value', 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where('value', '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where('value', '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where('value', '>', $filter['value']);
                break;
            case 'less_than':
                $query->where('value', '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where('value', '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where('value', '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn('value', explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn('value', explode(',', $filter['value']));
                break;
            case 'like':
                $query->where('value', 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where('value', 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where('value', 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where('value', 'not regexp', $filter['value']);
                break;
            case 'date_is':
                $query->whereDate('value', '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate('value', '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate('value', '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate('value', '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate('value', '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate('value', '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) {
                    $query->whereNull('value')
                        ->orWhere('value', '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull('value')
                    ->where('value', '!=', '');
                break;
        }
    }

    protected function applyStandardMetaFilter(Builder $query, array $filter): void
    {
        if (isset($filter['options']) && isset($filter['options']['resource_type'])) {
            $resourceType = $filter['options']['resource_type'];
            $values = (array) $filter['value'];

            $slug = $filter['name'];
            $relatedType = get_class($query->getModel());

            if ($filter['operator'] === 'contains') {
                $query->whereIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                    $subQuery->select('related_id')
                        ->from('post_relations')
                        ->where('post_relations.related_type', $relatedType)
                        ->where('post_relations.resource_type', $resourceType)
                        ->where('post_relations.slug', $slug)
                        ->whereIn('post_relations.resource_id', $values);
                });
            } elseif ($filter['operator'] === 'does_not_contain') {
                $query->whereNotIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                    $subQuery->select('related_id')
                        ->from('post_relations')
                        ->where('post_relations.related_type', $relatedType)
                        ->where('post_relations.resource_type', $resourceType)
                        ->where('post_relations.slug', $slug)
                        ->whereIn('post_relations.resource_id', $values);
                });
            }

            return;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name']);
            $this->applyOperatorCondition($query, $filter);
        });
    }

    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
        if (is_array($filter['value'])) {
            $filter['value'] = implode(',', $filter['value']);
        }
        switch ($filter['operator']) {
            case 'contains':
                $query->where($filter['name'], 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where($filter['name'], 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where($filter['name'], 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where($filter['name'], 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where($filter['name'], '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where($filter['name'], '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where($filter['name'], '>', $filter['value']);
                break;
            case 'less_than':
                $query->where($filter['name'], '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where($filter['name'], '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where($filter['name'], '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'like':
                $query->where($filter['name'], 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where($filter['name'], 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where($filter['name'], 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where($filter['name'], 'not regexp', $filter['value']);
                break;
            case 'is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
            case 'date_is':
                $query->whereDate($filter['name'], '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate($filter['name'], '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate($filter['name'], '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate($filter['name'], '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate($filter['name'], '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate($filter['name'], '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
        }

        return $query;
    }

    protected function isValidFilter(array $filter): bool
    {
        return ! empty($filter['name']) &&
               (! empty($filter['value']) || in_array($filter['operator'], ['is_empty', 'is_not_empty']));
    }
}
