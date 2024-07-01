<?php

namespace Aura\Base\Livewire\Table\Traits;

/**
 * Trait to handle search functionality.
 */
trait Search
{
    /**
     * The search value.
     *
     * @var string
     */
    public $search;

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

            ray('searchableFields', $searchableFields, $this->model);

            $metaFields = $searchableFields->filter(function ($field) {
                return $this->model->isMetaField($field);
            });

            if ($metaFields->count() > 0) {

                $metaTable = $this->model->getMetaTable();

                $query
                    ->select($this->model->getTable().'.*')
                    ->leftJoin($metaTable, function ($join) use ($metaFields, $metaTable) {
                        $join->on($this->model->getTable().'.id', '=', $metaTable .'.' . $this->model->getMetaForeignKey())
                            ->whereIn( $metaTable . '.key', $metaFields);
                    })
                    ->where(function ($query) use ($metaTable){
                        // Todo: Meta fields on Custom Tables may not have a title field.

                        $query
                            ->when($this->model->getTable() == 'posts', function ($query) {
                                $query->where($this->model->getTable().'.title', 'like', $this->search.'%');
                            })
                            ->orWhere(function ($query) use($metaTable) {
                                $query->where($metaTable .'.value', 'LIKE', '%'.$this->search.'%');
                            });
                    })
                    ->groupBy($this->model->getTable().'.id');
            }

            if ($searchableFields->count() > 0) {
                $query->where(function ($query) use ($searchableFields, $metaFields) {
                    foreach ($searchableFields as $field) {

                        // if $field is in $metaFields, continue
                        if ($metaFields->contains($field)) {
                            continue;
                        }

                        $query->orWhere($this->model->getTable().'.'.$field, 'like', $this->search.'%');
                    }
                });
            }

            // Check if there is a search method in the model (modifySearch()), and call it.
            if (method_exists($this->model, 'modifySearch')) {
                // dump('modify search');
                $query = $this->model->modifySearch($query, $this->search);
            }
        }

        return $query;
    }
}
