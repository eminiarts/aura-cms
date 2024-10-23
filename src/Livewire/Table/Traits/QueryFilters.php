<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
    {
        if (empty($this->filters['custom'])) {
            return $query;
        }

        $mainOperator = $this->filters['operator'] ?? 'and';

        ray($this->filters, 'mainOperator', $mainOperator);

        $query->where(function ($query) use ($mainOperator) {
            foreach ($this->filters['custom'] as $filter) {
                ray('filter here', $filter, $mainOperator);

                $mainOperator = $filter['main_operator'] ?? $mainOperator;

                if ($this->isValidFilter($filter)) {
                    $this->applyFilter($query, $filter, $mainOperator);
                }
            }
        });

        return $query;
    }

    protected function applyFilter(Builder $query, array $filter, string $mainOperator): void
    {
        if ($mainOperator === 'or') {
            $query->orWhere(function ($subQuery) use ($filter) {
                $this->applyFilterBasedOnType($subQuery, $filter);
            });
        } else {
            $this->applyFilterBasedOnType($query, $filter);
        }
    }

    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        if ($this->model->usesCustomTable() || $this->model->isTableField($filter['name'])) {
            $this->applyTableFieldFilter($query, $filter);
        } else {
            $this->applyMetaFieldFilter($query, $filter);
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
        }
    }

    protected function applyStandardMetaFilter(Builder $query, array $filter): void
    {
        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name']);
            $this->applyOperatorCondition($query, $filter);
        });
    }

    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
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
        }

        return $query;
    }

    protected function applyTaxonomyFilter(Builder $query): Builder
    {
        if ($this->filters['taxonomy']) {
            foreach ($this->filters['taxonomy'] as $key => $taxonomy) {
                if (! $taxonomy) {
                    continue;
                }

                $query->whereExists(function ($subQuery) use ($key, $taxonomy) {
                    $subQuery->select(DB::raw(1))
                        ->from('post_meta')
                        ->where('post_meta.post_id', '=', DB::raw('posts.id'))
                        ->where('post_meta.key', $key)
                        ->where(function ($subQuery) use ($taxonomy) {
                            foreach ($taxonomy as $value) {
                                $subQuery->orWhereRaw('JSON_CONTAINS(CAST(post_meta.value as JSON), ?)', [(string) $value]);
                            }
                        });
                });
            }
        }

        return $query;
    }

    protected function isValidFilter(array $filter): bool
    {
        return ! empty($filter['name']) &&
               (! empty($filter['value']) || in_array($filter['operator'], ['is_empty', 'is_not_empty']));
    }
}
