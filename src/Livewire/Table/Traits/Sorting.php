<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;

/**
 * Trait to handle sorting functionality.
 */
trait Sorting
{
    /**
     * Collection of sort field and direction.
     *
     * @var array
     */
    public $sorts = [];

    /**
     * Sort by the specified field.
     *
     * @param  string  $field
     * @return void
     */
    public function sortBy($field)
    {
        $this->sorts = collect($this->sorts)->filter(function ($value, $key) use ($field) {
            return $key === $field;
        })->toArray();

        if (! isset($this->sorts[$field])) {
            $this->sorts[$field] = 'asc';

            return;
        }

        if ($this->sorts[$field] === 'asc') {
            $this->sorts[$field] = 'desc';

            return;
        }

        unset($this->sorts[$field]);
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query)
    {
        if ($this->sorts) {
            $query->getQuery()->orders = null;
        }

        foreach ($this->sorts as $field => $direction) {

            // We want to add custom Sorting. If the model has a custom sorting method, we want to use that instead of the default one. Name of the method is sort_{$field}
            if (method_exists($this->model, 'sort_'.$field)) {
                $this->model->{'sort_'.$field}($query, $direction);

                return $query;
            }

            if ($this->model->isTaxonomyField($field)) {
                $resourceType = $this->model->fieldBySlug($field)['resource'];

                // dd($resourceType);

                
                $query->leftJoin('post_relations as pr', function ($join) use ($resourceType) {
                    $join->on('posts.id', '=', 'pr.related_id')
                        ->where('pr.related_type', '=', $this->model->getMorphClass())
                        ->where('pr.resource_type', '=', $resourceType)
                        ->where('pr.slug', '=', Str::plural(Str::lower(class_basename($resourceType))));
                })
                ->select('posts.*')
                ->groupBy('posts.id')
                ->orderByRaw('MIN(pr.resource_id) ' . $direction)
                ->orderBy('posts.id', 'desc');

                return $query;
            }

            if ($this->model->usesMeta() && $this->model->isMetaField($field)) {
                $query->leftJoin('meta', function ($join) use ($field) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', '=', $this->model->getMorphClass())
                        ->where('meta.key', '=', "$field");
                })
                    ->select('posts.*')
                    ->when($this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS DECIMAL(10,2)) '.$direction);
                    })
                    ->when(! $this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS CHAR) '.$direction);
                    })
                    ->orderBy('id', 'desc');

                return $query;
            } else {
                $query->orderBy($field, $direction);

                return $query;
            }
        }

        $query->getQuery()->orders = null;

        // default sort
        $query->orderBy($this->model->getTable().'.'.$this->model->defaultTableSort(), $this->model->defaultTableSortDirection());

        return $query;
    }
}
