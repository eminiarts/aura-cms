<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

use DB;
use Illuminate\Contracts\Database\Eloquent\Builder;

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

        // switch ($filter['operator']) {

        //     case 'is_in':
        //         return $query->whereIn($filter['name'], explode(',', $filter['value']));
        //     case 'is_not_in':
        //         return $query->whereNotIn($filter['name'], explode(',', $filter['value']));

        //     case 'is_between':
        //         $values = explode(',', $filter['value']);
        //         return $query->whereBetween($filter['name'], [$values[0], $values[1]]);
        //     case 'is_not_between':
        //         $values = explode(',', $filter['value']);
        //         return $query->whereNotBetween($filter['name'], [$values[0], $values[1]]);

        //     case 'is_today':
        //         return $query->whereDate($filter['name'], Carbon::today());
        //     case 'is_yesterday':
        //         return $query->whereDate($filter['name'], Carbon::yesterday());
        //     case 'is_this_week':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        //     case 'is_this_month':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        //     case 'is_this_year':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
        //     case 'is_last_week':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
        //     case 'is_last_month':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
        //     case 'is_last_year':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()]);
        //     case 'is_next_week':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->addWeek()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()]);
        //     case 'is_next_month':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->addMonth()->startOfMonth(), Carbon::now()->addMonth()->endOfMonth()]);
        //     case 'is_next_year':
        //         return $query->whereBetween($filter['name'], [Carbon::now()->addYear()->startOfYear(), Carbon::now()->addYear()->endOfYear()]);

        //     case 'is_before':
        //         return $query->whereDate($filter['name'], '<', Carbon::parse($filter['value']));
        //     case 'is_after':
        //         return $query->whereDate($filter['name'], '>', Carbon::parse($filter['value']));

        //     default:
        //         return $query;
    }

    /**
     * Apply taxonomy filter to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyTaxonomyFilter(Builder $query)
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
                     $subQuery->orWhereRaw('JSON_CONTAINS(CAST(post_meta.value as JSON), ?)', [(string)$value]);
                 }
             });
});

                // $query->join('post_meta', 'posts.id', '=', 'post_meta.post_id')
                //       ->where('post_meta.key', $key)
                //       ->where(function ($query) use ($taxonomy) {
                //           foreach ($taxonomy as $value) {
                //               $query->orWhereRaw('JSON_CONTAINS(CAST(post_meta.value as JSON), ?)', [(string)$value]);
                //           }
                //       });

                // $query->whereHas('taxonomies', function (Builder $query) use ($taxonomy) {
                //     $query->whereIn('id', $taxonomy);
                // });
            }
        }

        return $query;
    }
}
