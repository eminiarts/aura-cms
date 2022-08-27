<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

use Livewire\WithPagination;

trait PerPagePagination
{
    use WithPagination;

    public $perPage = 10;

    public function applyPagination($query)
    {
        return $query->paginate($this->perPage);
    }

    public function mountWithPerPagePagination()
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
    }
}
