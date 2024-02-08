<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    /**
     * Apply custom filter to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCustomFilter(Builder $query)
    {
        if (! $this->filters['custom']) {
            return $query;
        }

        foreach ($this->filters['custom'] as $filter) {
            if (! $filter['name'] || ! $filter['value'] && $filter['operator'] != 'is_empty') {
                continue;
            }

            if ($this->model->usesCustomTable()) {
                $query = $this->applyTableFieldFilter($query, $filter);
            } elseif ($this->model->isTableField($filter['name'])) {
                $query = $this->applyTableFieldFilter($query, $filter);
            } else {
                $query = $this->applyMetaFieldFilter($query, $filter);
            }
        }

        // More advanced Search
        if ($this->search) {
            //   $query->where($this->model->getTable() . '.title', 'LIKE', $this->search.'%');
        }

        return $query;
    }

    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->where(function ($query) use ($filter) {
            $query->whereDoesntHave('meta', function (Builder $query) use ($filter) {
                $query->where('key', '=', $filter['name']);
            })
                ->orWhereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', '=', $filter['name']);
                    $query->where('value', '=', '');
                    // now we're checking for empty values
                    // what about null values?
                    // null, ""
                });
        });
    }

    /**
     * Apply filters to the meta fields query
     */
    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        if ($filter['operator'] == 'is_empty') {
            $this->applyIsEmptyMetaFilter($query, $filter);

            // where
            // where not exists
            // or where key = name AND value = null
            return $query;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name']);

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
                    $query->where('value', '=', $filter['value']);
                    break;
                case 'is_not':
                    $query->where('value', '!=', $filter['value']);
                    break;
                case 'greater_than':
                    $query->where('value', '>', $filter['value']);
                    break;
                case 'less_than':
                    $query->where('value', '<', $filter['value']);
                    break;
            }
        });

        return $query;
    }

    /**
     * Apply filter for table fields
     *
     * @return Builder
     */
    protected function applyTableFieldFilter(Builder $query, array $filter)
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
                $query->where($filter['name'], '=', $filter['value']);
                break;
            case 'is_not':
                $query->where($filter['name'], '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where($filter['name'], '>', $filter['value']);
                break;
            case 'less_than':
                $query->where($filter['name'], '<', $filter['value']);
                break;
            case 'is_empty':
                return $query->whereNull($filter['name']);
            case 'is_not_empty':
                return $query->whereNotNull($filter['name']);
        }

        return $query;
    }

    /**
     * Apply taxonomy filter to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTaxonomyFilter(Builder $query)
    {
        // if (app()->environment('testing')) {
        //     return $query;
        // }

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
                     $subQuery->orWhereRaw('JSON_CONTAINS(CAST(post_meta.value as JSON), ?)', [(string)$value]);
                 }
             });
});

            }
        }

        return $query;
    }
}
