<?php

namespace Eminiarts\Aura\Livewire\Table\Traits;

use Livewire\WithPagination;

/**
 * Trait to handle per-page pagination.
 */
trait PerPagePagination
{
    use WithPagination;

    public function updatedPage($page)
    {
        // $this->render();
        ray('updated page: ' . $page);

        // $this->dispatch('refreshTable');

        unset($this->rows);

    }

    public function updatingPage($page)
    {
        // Runs before the page is updated for this component...
        ray('updating page: ' . $page);
        // $this->setPage($page);
    }

    /**
     * Number of items to be displayed per page.
     *
     * @var int
     */
    public $perPage = 10;

    /**
     * Paginate the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
    public function mountWithPerPagePagination()
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    /**
     * Pagination theme.
     *
     * @var string
     */
    public function paginationView()
    {
        return 'aura::aura.pagination';
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
