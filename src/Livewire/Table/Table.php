<?php

namespace Eminiarts\Aura\Livewire\Table;

use Livewire\Component;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Models\User;
use Livewire\Attributes\Computed;
use Eminiarts\Aura\Livewire\Table\Traits\Search;
use Eminiarts\Aura\Livewire\Table\Traits\Filters;
use Eminiarts\Aura\Livewire\Table\Traits\Sorting;
use Eminiarts\Aura\Livewire\Table\Traits\BulkActions;
use Eminiarts\Aura\Livewire\Table\Traits\QueryFilters;
use Eminiarts\Aura\Livewire\Table\Traits\PerPagePagination;

/**
 * Class Table
 */
class Table extends Component
{
    use BulkActions;
    use Filters;
    use PerPagePagination;
    use QueryFilters;
    use Search;
    use Sorting;

    public $rowIds;

    public $namespace;

    /**
     * List of table columns.
     *
     * @var array
     */
    public $columns = [];

    /**
     * Indicates if the Create Component should be in a Modal.
     *
     * @var bool
     */
    public $createInModal = false;

    /**
     * Indicates if the Edit Component should be in a Modal.
     *
     * @var bool
     */
    public $editInModal = false;

    /**
     * The field of the parent.
     *
     * @var string
     */
    public $field;

    /**
     * The name of the filter in the modal.
     *
     * @var string
     */
    public $filter = [
        'name' => '',
        'public' => false,
        'global' => false,
    ];

    /**
     * The last clicked row.
     *
     * @var mixed
     */
    public $lastClickedRow;

    /**
     * The parent of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    /**
     * Validation rules.
     *
     * @var array
     */
    public $rules = [
        'filter.name' => 'required',
        'filter.global' => '',
        'filter.public' => '',
        'filters.custom.*.name' => 'required',
        'filters.custom.*.operator' => 'required',
        'filters.custom.*.value' => 'required',
    ];

    /**
     * The search value.
     *
     * @var string
     */
    public $search;

    /**
     * The settings of the table.
     *
     * @var array
     */
    public $settings;

    /**
     * The view of the table.
     *
     * @var string
     */
    public $tableView;

    protected $queryString = ['selectedFilter'];

    /**
     * List of events listened to by the component.
     *
     * @var array
     */
    protected $listeners = [
        'refreshTable' => '$refresh',
        'selectedRows' => 'selectRows',
        'selectRowsRange' => 'selectRowsRange',
    ];

    public function action($data)
    {
        // return redirect to post view
        if ($data['action'] == 'view') {
            return redirect()->route('aura.post.view', ['slug' => $this->model()->getType(), 'id' => $data['id']]);
        }
        // edit
        if ($data['action'] == 'edit') {
            return redirect()->route('aura.post.edit', ['slug' => $this->model()->getType(), 'id' => $data['id']]);
        }

        // if custom
        // dd($data);

        if (method_exists($this->model, $data['action'])) {
            return $this->model()->find($data['id'])->{$data['action']}();
        }
    }

    /**
     * Handle bulk action on the selected rows.
     */
    public function bulkAction(string $action)
    {
        $this->selectedRowsQuery->each(function ($item, $key) use ($action) {
            if (str_starts_with($action, 'callFlow.')) {
                $item->callFlow(explode('.', $action)[1]);
            } elseif (str_starts_with($action, 'multiple')) {
                $posts = $this->selectedRowsQuery->get();
                $item->{$action}($posts);
            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        $this->notify('Erfolgreich: '.$action);
    }

    public function openBulkActionModal($action, $data)
    {
        // ray($data, $this->selectedRowsQuery->get());

        $this->dispatch('openModal', $data['modal'], [
            'action' => $action,
            'selected' => $this->selectedRowsQuery->get(),
            'model' => get_class($this->model()),
        ]);

        // $dispatch('openModal', '{{ $data['modal'] }}', {{ json_encode(['action' => $action, 'selected' => $this->selectedRowsQuery->get()]) }})
    }

    // public function getRowIds()
    // {
    //     return $this->rows->pluck('id')->toArray();
    // }

    /**
         * Get the available bulk actions.
         *
         * @return mixed
         */
    #[Computed]
    public function bulkActions()
    {
        return $this->model()->getBulkActions();
    }

    #[Computed]
    public function page()
    {
        return $this->getPage();
    }

    /**
     * Get the create link.
     *
     * @return string
     */
    #[Computed]
    public function createLink()
    {
        if ($this->model()->createUrl()) {
            return $this->model()->createUrl();
        }

        if ($this->parent) {
            return route('aura.post.create', [
                'slug' => $this->model()->getType(),
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
        }

        return route('aura.post.create', ['slug' => $this->model()->getType()]);
    }

    /**
     * Get the input fields.
     *
     * @return mixed
     */
    #[Computed]
    public function fields()
    {
        return $this->model()->inputFields();
    }

    /**
     * Get the table headers.
     *
     * @return mixed
     */
    #[Computed]
    public function headers()
    {
        $headers = $this->model()->getTableHeaders();

        if ($sort = auth()->user()->getOption('columns_sort.'.$this->model()->getType())) {
            $headers = $headers->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, $sort);
            });
        }

        return $headers;
    }

    /**
     * Get the model columns.
     *
     * @return mixed
     */
    #[Computed]
    public function modelColumns()
    {
        $columns = collect($this->model()->getColumns());

        if ($sort = auth()->user()->getOption('columns_sort.'.$this->model()->getType())) {
            $columns = $columns->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, $sort);
            });
        }

        return $columns;
    }

    public function getParentModel()
    {
        return $this->parent;
    }

    /**
     * Get the rows for the table.
     *
     * @return mixed
     */
    #[Computed]
    public function rows()
    {
        return $this->rowsQuery->paginate(10);
    }

    /**
     * Get query  for the table data
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    #[Computed]
    public function rowsQuery()
    {
        $query = $this->model()->query()
            ->orderBy($this->model()->getTable().'.id', 'desc');

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
            $query = app($this->field['type'])->queryFor($this->parent, $query, $this->field);
        }

        if (method_exists($this->model(), 'indexQuery')) {
            $query = $this->model()->indexQuery($query);
        }

        // when model is instance Resource, eager load meta and taxonomies
        if ($this->model() instanceof Resource) {
            $query = $query->with(['meta', 'taxonomies']);
        }

        // Search
        if ($this->search) {
            $query = $this->applySearch($query);
        }

        if ($this->filters) {
            $query = $this->applyTaxonomyFilter($query);
            $query = $this->applyCustomFilter($query);
        }

        return $this->applySorting($query);
    }

    public function mount($query = null)
    {
        // if ($this->parentModel) {
        //     // dd($this->parentModel);
        // }

        $this->dispatch('tableMounted');

        $this->setTaxonomyFilters();


        if($this->selectedFilter) {
            $this->filters = $this->userFilters[$this->selectedFilter];
        }

        $this->query = $query;

        $this->tableView = $this->model()->defaultTableView();

        $this->rowIds = $this->rows->pluck('id')->toArray();

        $this->perPage = $this->model()->defaultPerPage();

        if (auth()->user()->getOptionColumns($this->model()->getType())) {
            $this->columns = auth()->user()->getOptionColumns($this->model()->getType());
        } else {
            $this->columns = $this->model()->getDefaultColumns();
        }
    }


    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('aura::livewire.table.table', [
            'parent' => $this->parent,
            'data' => $this->rows,
        ]);
    }

    public function setPageTen()
    {
        $this->setPage(10);
    }



    /**
     * Reorder the table columns.
     *
     * @param $slugs array The new column order.
     * @return void
     */
    public function reorder($slugs)
    {
        auth()->user()->updateOption('columns_sort.'.$this->model()->getType(), $slugs);
    }

    /**
     * Select a single row in the table.
     *
     * @param $id int The id of the row to select.
     * @return void
     */
    public function selectRow($id)
    {
        ray('selectRow', $id);
        $this->selected = $id;
        $this->lastClickedRow = $id;
    }



    /**
     * Update the columns in the table.
     *
     * @param $columns array The new columns.
     * @return void
     */
    public function updatedColumns($columns)
    {
        // Save the columns for the current user.
        if ($this->columns) {
            auth()->user()->updateOption('columns.'.$this->model()->getType(), $this->columns);
        }
    }

    #[Computed]
    public function allTableRows()
    {
        return $this->rowsQuery->pluck('id')->all();
    }

    /**
     * Update the selected rows in the table.
     *
     * @return void
     */
    public function updatedSelected()
    {
        $this->selectAll = false;
        $this->selectPage = false;

        // Only allow the max number of selected rows.
        if (optional($this->field)['max'] && count($this->selected) > $this->field['max']) {
            $this->selected = array_slice($this->selected, 0, $this->field['max']);

            $this->dispatch('selectedRows', $this->selected);
            $this->notify('You can only select '.$this->field['max'].' items.', 'error');
        } else {
            $this->dispatch('selectedRows', $this->selected);
        }
    }


    #[Computed]
    public function model()
    {
        return app($this->namespace);
    }

}
