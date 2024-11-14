<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Facades\DB;

trait Search
{
    public $search;

    public function applySearch($query)
    {
        if ($this->search) {

            ray($this->search);

            // Check if there is a search method in the model (modifySearch()), and call it.
            if (method_exists($this->model, 'modifySearch')) {
                $query = $this->model->modifySearch($query, $this->search);

                return $query;
            }

            $searchableFields = $this->model->getSearchableFields()->pluck('slug');
            $metaFields = $searchableFields->filter(fn ($field) => $this->model->isMetaField($field));

            $query->where(function ($query) use ($searchableFields, $metaFields) {
                // Search in regular fields
                foreach ($searchableFields as $field) {
                    if (! $metaFields->contains($field)) {
                        $query->orWhere($this->model->getTable().'.'.$field, 'like', '%'.$this->search.'%');
                    }
                }

                // Search in meta fields
                if ($metaFields->count() > 0) {
                    $metaTable = $this->model->getMetaTable();
                    $query->orWhereExists(function ($query) use ($metaTable, $metaFields) {
                        $query->select(DB::raw(1))
                            ->from($metaTable)
                            ->whereColumn($this->model->getTable().'.id', $metaTable.'.'.$this->model->getMetaForeignKey())
                            ->whereIn($metaTable.'.key', $metaFields)
                            ->where($metaTable.'.value', 'like', '%'.$this->search.'%');
                    });
                }
            });

        }

        return $query;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }
}
