<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

use Illuminate\Support\Str;

/**
 * Trait to handle search functionality.
 */
trait Search
{
    /**
     * Apply search to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applySearch($query)
    {

        if ($this->search) {

            $searchableFields = $this->model->getSearchableFields()->pluck('slug');

            $metaFields = $searchableFields->filter(function ($field) {
                return $this->model->isMetaField($field);
            });

            if($metaFields->count() > 0) {
                $query
                ->select($this->model->getTable() . '.*')
                ->leftJoin('post_meta', function ($join) use ($metaFields) {
                    $join->on($this->model->getTable() . '.id', '=', 'post_meta.post_id')
                        ->whereIn('post_meta.key', $metaFields);
                })
                ->where(function ($query) {
                    $query->where($this->model->getTable() . '.title', 'like', '%'.$this->search.'%')
                        ->orWhere(function ($query) {
                            $query->where('post_meta.value', 'LIKE', '%'.$this->search.'%');
                        });
                })
                         ->groupBy($this->model->getTable() . '.id')
                ;
            }

            // Check if there is a search method in the model (modifySearch()), and call it.
            if (method_exists($this->model, 'modifySearch')) {
                $query = $this->model->modifySearch($query, $this->search);
            }
        }

       

        return $query;
    }

    /**
     * Search for data in the table.
     *
     * @return void
     */
    public function search()
    {
        // Code to implement the search functionality.
    }


}
