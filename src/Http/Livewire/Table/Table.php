<?php

namespace Eminiarts\Aura\Http\Livewire\Table;

use Eminiarts\Aura\Http\Livewire\Table\Traits\BulkActions;
use Eminiarts\Aura\Http\Livewire\Table\Traits\Filters;
use Eminiarts\Aura\Http\Livewire\Table\Traits\PerPagePagination;
use Eminiarts\Aura\Http\Livewire\Table\Traits\QueryFilters;
use Eminiarts\Aura\Http\Livewire\Table\Traits\Search;
use Eminiarts\Aura\Http\Livewire\Table\Traits\Sorting;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Livewire\Component;

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

    public $loaded = false;

    public $post;

    public $headerView = 'aura::components.table.header';
    public $filterView = 'aura::components.table.filter';
    public $bulkActionsView = 'aura::components.table.bulkActions';

    /**
     * The model of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The parent of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    public $rowIds;

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

    /**
     * List of events listened to by the component.
     *
     * @var array
     */
    protected $listeners = [
        'refreshTable' => '$refresh',
        'selectedRows' => 'selectRows',
        'selectRowsRange' => 'selectRowsRange',
        'refreshTableSelected' => 'refreshTableSelected',
        'selectFieldRows',
    ];

    protected $queryString = ['selectedFilter'];

    public function selectFieldRows($data)
    {
        if($data['slug'] == $this->field['slug']) {
            ray('selectFieldRows', $data);

            $this->selected = $data['value'];
        }
    }

    public function action($data)
    {
        // return redirect to post view
        if ($data['action'] == 'view') {
            return redirect()->route('aura.post.view', ['slug' => $this->model->getType(), 'id' => $data['id']]);
        }
        // edit
        if ($data['action'] == 'edit') {
            return redirect()->route('aura.post.edit', ['slug' => $this->model->getType(), 'id' => $data['id']]);
        }

        // if custom
        // dd($data);

        if (method_exists($this->model, $data['action'])) {
            return $this->model->find($data['id'])->{$data['action']}();
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
                $response = $item->{$action}($posts);

                // dd($response);
            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        $this->notify('Erfolgreich: '.$action);
    }

    public function bulkCollectionAction($action)
    {
        //$action = $this->model->getBulkActions()[$action];
        $ids = $this->selectedRowsQuery->pluck('id')->toArray();

        $response = $this->model->{$action}($ids);

        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $response;
        }

        // reset selected rows
        $this->selected = [];

        $this->notify('Erfolgreich: '.$action);

        $this->emit('refreshTable');
    }

    public function getAllTableRows()
    {
        // dd('hier', $this->rowsQuery->pluck('id'));
        return $this->rowsQuery->pluck('id')->all();
    }

    // public function getRowIdsProperty()
    // {
    //     return $this->rows->pluck('id')->toArray();
    // }

    /**
     * Get the available bulk actions.
     *
     * @return mixed
     */
    public function getBulkActionsProperty()
    {
        return $this->model->getBulkActions();
    }

    /**
     * Get the create link.
     *
     * @return string
     */
    public function getCreateLinkProperty()
    {
        if ($this->model->createUrl()) {
            return $this->model->createUrl();
        }

        if ($this->parent) {
            return route('aura.post.create', [
                'slug' => $this->model->getType(),
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
        }

        return route('aura.post.create', ['slug' => $this->model->getType()]);
    }

    /**
     * Get the input fields.
     *
     * @return mixed
     */
    public function getFieldsProperty()
    {
        return $this->model->inputFields();
    }

    /**
     * Get the table headers.
     *
     * @return mixed
     */
    public function getHeadersProperty()
    {
        $headers = $this->model->getTableHeaders();

        if ($sort = auth()->user()->getOption('columns_sort.'.$this->model->getType())) {
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
    public function getModelColumnsProperty()
    {
        $columns = collect($this->model->getColumns());

        if ($sort = auth()->user()->getOption('columns_sort.'.$this->model->getType())) {
            $columns = $columns->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, $sort);
            });
        }

        return $columns;
    }

    public function getParentModelProperty()
    {
        return $this->parent;
    }

    /**
     * Get the rows for the table.
     *
     * @return mixed
     */
    public function getRowsProperty()
    {
        return $this->rowsQuery->paginate($this->perPage);
    }

    /**
     * Get query property for the table data
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRowsQueryProperty()
    {
        $query = $this->model->query()
            ->orderBy($this->model->getTable().'.id', 'desc');

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
            $query = app($this->field['type'])->queryFor($query, $this);
        }

        if (method_exists($this->model, 'indexQuery')) {
            $query = $this->model->indexQuery($query);
        }

        // when model is instance Resource, eager load meta and taxonomies
        if ($this->model instanceof Resource) {
            $query = $query->with(['taxonomies']);
        }

        // when model is instance Resource, eager load meta and taxonomies
        if ($this->model->usesMeta()) {
            $query = $query->with(['meta']);
        }


        if ($this->filters) {
            $query = $this->applyTaxonomyFilter($query);
            $query = $this->applyCustomFilter($query);
        }

        // Search
        if ($this->search) {
            $query = $this->applySearch($query);

            // return $query;
        }

        return $this->applySorting($query);
    }

    public function loadTable()
    {
        $this->loaded = true;
    }

    public function mount($query = null)
    {
        // if(!$this->loaded) {
        //     return;
        // }

        if ($this->parentModel) {
            // dd($this->parentModel);
        }
        $this->emit('tableMounted');

        $this->setTaxonomyFilters();

        if ($this->selectedFilter) {
            $this->filters = $this->userFilters[$this->selectedFilter];
        }

        // dd($this->model);

        $this->query = $query;

        $this->tableView = $this->model->defaultTableView();

        $this->rowIds = $this->rows->pluck('id')->toArray();

        $this->perPage = $this->model->defaultPerPage();

        if (auth()->user()->getOptionColumns($this->model->getType())) {
            $this->columns = auth()->user()->getOptionColumns($this->model->getType());
        } else {
            $this->columns = $this->model->getDefaultColumns();
        }
    }

    public function openBulkActionModal($action, $data)
    {
        // ray($data, $this->selectedRowsQuery->get());

        $this->emit('openModal', $data['modal'], [
            'action' => $action,
            'selected' => $this->selectedRowsQuery->pluck('id'),
            'model' => get_class($this->model),
        ]);

        // $emit('openModal', '{{ $data['modal'] }}', {{ json_encode(['action' => $action, 'selected' => $this->selectedRowsQuery->get()]) }})
    }

    public function refreshTableSelected()
    {
        $this->selected = [];
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
        ]);
    }

    /**
     * Reorder the table columns.
     *
     * @param $slugs array The new column order.
     * @return void
     */
    public function reorder($slugs)
    {
        auth()->user()->updateOption('columns_sort.'.$this->model->getType(), $slugs);
    }

    /**
     * Select a single row in the table.
     *
     * @param $id int The id of the row to select.
     * @return void
     */
    public function selectRow($id)
    {
        // ray('selectRow', $id);
        $this->selected = $id;
        $this->lastClickedRow = $id;
    }

    /**
     * Select multiple rows in the table.
     *
     * @param $ids array The ids of the rows to select.
     * @return void
     */
    public function selectRows($ids)
    {
        $this->selected = $ids;
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
            auth()->user()->updateOption('columns.'.$this->model->getType(), $this->columns);
        }
    }

    public function updatedPage($page)
    {
        $this->rowIds = $this->rows->pluck('id')->toArray();
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

        // ray('updated selected');

        // Only allow the max number of selected rows.
        if (optional($this->field)['max'] && count($this->selected) > $this->field['max']) {
            $this->selected = array_slice($this->selected, 0, $this->field['max']);

            $this->emit('selectedRows', $this->selected);
            $this->notify('You can only select '.$this->field['max'].' items.', 'error');
        } else {
            $this->emit('selectedRows', $this->selected);
        }
    }
}
