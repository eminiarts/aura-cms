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
            $query->where(function ($query) {
                foreach ($this->model->searchableColumns() as $column) {
                    $query->orWhere($column, 'like', '%'.$this->search.'%');
                }
            });
        }

        return $query;
    }


}
