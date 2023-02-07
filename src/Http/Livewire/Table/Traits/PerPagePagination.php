<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

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
     * Pagination theme.
     *
     * @var string
     */
    protected $paginationTheme = 'aura-pagination';

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
