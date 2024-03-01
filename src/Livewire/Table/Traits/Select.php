<?php

namespace Aura\Base\Livewire\Table\Traits;

/**
 * Trait for bulk actions in Livewire table component
 */
trait Select
{
    /**
     * Indicates if all rows should be selected
     *
     * @var bool
     */
    public $selectAll = false;

    /**
     * Array of selected row IDs
     *
     * @var array
     */
    public $selected = [];

    /**
     * Indicates if all rows in the current page should be selected
     *
     * @var bool
     */
    public $selectPage = false;

    /**
     * Gets a query for selected rows
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getSelectedRowsQueryProperty()
    {
        return (clone $this->query())
            ->unless($this->selectAll, fn ($query) => $query->whereKey($this->selected));
    }

    // /**
    //  * Handles selecting all or page rows
    //  *
    //  * @return void
    //  */
    // public function renderingWithBulkActions()
    // {
    //     if ($this->selectAll) {
    //         $this->selectPageRows();
    //     }
    // }

    /**
     * Selects all rows
     *
     * @return void
     */
    public function selectAll()
    {
        $this->selectAll = true;
    }

    /**
     * Selects all rows in the current page
     *
     * @return void
     */
    public function selectPageRows()
    {
        $this->selected = collect($this->selected)
            ->merge($this->rows()->pluck('id')->map(fn ($id) => (string) $id))
            ->unique()
            ->values()
            ->all();
    }

    // when page is updated, reset selectPage
    public function updatedPage()
    {
        $this->selectPage = false;
    }

    /**
     * Handles updates to selected rows
     *
     * @return void
     */
    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    /**
     * Handles updates to selecting all rows in the current page
     *
     * @param  bool  $value
     * @return void
     */
    public function updatedSelectPage($value)
    {
        if ($value) {
            return $this->selectPageRows();
        }

        $this->selectAll = false;
        $this->selected = [];
    }
}
