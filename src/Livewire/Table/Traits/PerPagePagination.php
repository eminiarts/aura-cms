<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

/**
 * Trait to handle per-page pagination.
 */
trait PerPagePagination
{
    use WithPagination;

    /**
     * Number of items to be displayed per page.
     *
     * @var int
     */
    public $perPage = 10;

    /**
     * Paginate the query.
     *
     * @param  Builder  $query
     * @return LengthAwarePaginator
     */
    public function applyPagination($query)
    {
        return $query->paginate($this->perPage, ['*']);
    }

    /**
     * Mount the pagination data from the session.
     *
     * @return void
     */
    public function mountPerPagePagination()
    {
        if (session()->has('perPage')) {
            $this->perPage = session()->get('perPage');

            return;
        }

        if (isset($this->settings['per_page'])) {
            $this->perPage = $this->settings['per_page'];

            return;
        }

        $this->perPage = $this->model()->defaultPerPage();
    }

    /**
     * Update the per-page pagination data in the session.
     *
     * @param  int  $value
     * @return void
     */
    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
    }
}
