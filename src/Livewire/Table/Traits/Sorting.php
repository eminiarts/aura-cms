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
            if ($this->model->isTaxonomyField($field)) {
                $taxonomy = Str::singular(ucfirst($field));

                $query->withFirstTaxonomy($taxonomy, $this->model->getMorphClass())
                    ->orderByRaw('CASE WHEN first_taxonomy IS NULL THEN 1 WHEN first_taxonomy = "" THEN 1 ELSE 0 END')
                    ->orderBy('first_taxonomy', $direction)
                    ->orderBy('id', 'desc');

                return $query;
            }

            if ($this->model->usesMeta() && $this->model->isMetaField($field)) {
                ray('isMetaField');
                $query->leftJoin('post_meta', function ($join) use ($field) {
                    $join->on('posts.id', '=', 'post_meta.post_id')
                        ->where('post_meta.key', '=', "$field");
                })
                    ->select('posts.*')
                    // ->orderByRaw('CASE WHEN post_meta.value IS NULL THEN 1 WHEN post_meta.value = "" THEN 1 ELSE 0 END')
                    ->when($this->model->isNumberField($field), function ($query) use ($direction) {
                        // $query->orderByRaw('CAST(post_meta.value AS SIGNED) '.$direction);
                        $query->orderByRaw('CAST(post_meta.value AS DECIMAL(10,2)) '.$direction);
                    })
                    ->when(! $this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(post_meta.value AS CHAR) '.$direction);
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
        // ray($this->model->defaultTableSort(), $this->model->defaultTableSortDirection(),'defaultTableSort()');
        $query->orderBy($this->model->getTable().'.'.$this->model->defaultTableSort(), $this->model->defaultTableSortDirection());

        return $query;
    }
}
