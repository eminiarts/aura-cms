<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

                ->where(function ($query) use ($metaFields) {
                    $query->where('posts.title', 'like', $this->search.'%')
                          ->orWhereExists(function ($query) use ($metaFields) {
                              $query->select(DB::raw(1))
                                    ->from('post_meta')
                                    ->where('posts.id', '=', DB::raw('post_meta.post_id'))
                                    ->whereIn('post_meta.key', $metaFields)
                                    ->where('post_meta.value', 'LIKE', $this->search.'%');
                          });
                })
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
