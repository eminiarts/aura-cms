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
                ->select('posts.*', 'post_meta.id as post_meta_id', 'post_meta.post_id', 'post_meta.key', 'post_meta.value')
                ->leftJoin('post_meta', function ($join) use ($metaFields) {
                    $join->on('posts.id', '=', 'post_meta.post_id')
                        ->whereIn('post_meta.key', $metaFields);
                })->where(function ($query) {
                    $query->where('posts.title', 'like', '%'.$this->search.'%')
                        ->orWhere(function ($query) {
                            $query->where('post_meta.value', 'LIKE', '%'.$this->search.'%');
                        });
                })
                           ->distinct()
                           //->orderBy('posts.id', 'desc')
                ;
            }

            // dd($searchableFields, $metaFields, $query);

            // $query->where(function ($query) {
            //     foreach ($this->model->searchableColumns() as $column) {
            //         $query->orWhere($column, 'like', '%'.$this->search.'%');
            //     }
            // });
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
