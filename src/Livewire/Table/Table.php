<?php

namespace Eminiarts\Aura\Livewire\Table;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Livewire\Table\Traits\BulkActions;
use Eminiarts\Aura\Livewire\Table\Traits\Filters;
use Eminiarts\Aura\Livewire\Table\Traits\PerPagePagination;
use Eminiarts\Aura\Livewire\Table\Traits\QueryFilters;
use Eminiarts\Aura\Livewire\Table\Traits\Search;
use Eminiarts\Aura\Livewire\Table\Traits\Select;
use Eminiarts\Aura\Livewire\Table\Traits\Settings;
use Eminiarts\Aura\Livewire\Table\Traits\Sorting;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Livewire\Component;
use Livewire\Attributes\Computed;

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
    use Select;
    use Settings;
    use Sorting;

    public $model;

    public $bulkActionsView = 'aura::components.table.bulkActions';

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

    public $disabled;

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

    /**
     * The parent of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    public $resource;

    public $query;

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
     * The settings of the table.
     *
     * @var array
     */
    public $settings;

    public $tableIndexView = 'aura::components.table.index';

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

    public function action($data)
    {
        // return redirect to post view
        if ($data['action'] == 'view') {
            return redirect()->route('aura.resource.view', ['slug' => $this->model()->getType(), 'id' => $data['id']]);
        }
        // edit
        if ($data['action'] == 'edit') {
            return redirect()->route('aura.resource.edit', ['slug' => $this->model()->getType(), 'id' => $data['id']]);
        }

        // if custom
        // dd($data);

        if (method_exists($this->model, $data['action'])) {
            return $this->model()->find($data['id'])->{$data['action']}();
        }
    }

    public function getAllTableRows()
    {
        return $this->rowsQuery->pluck('id')->all();
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
            return route('aura.resource.create', [
                'slug' => $this->model()->getType(),
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
        }

        return route('aura.resource.create', ['slug' => $this->model()->getType()]);
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
        $headers = $this->settings['columns'];

        if ($this->settings['sort_columns'] && $this->settings['sort_columns_key'] && $sort = Aura::getOption($this->settings['sort_columns_key'])) {
            $headers = collect($headers)->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, $sort);
            });
        }

        if ($this->settings['sort_columns'] && $this->settings['sort_columns_user_key'] && $sort = auth()->user()->getOption($this->settings['sort_columns_user_key'])) {
            $headers = collect($headers)->sortBy(function ($value, $key) use ($sort) {
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
    protected function rows()
    {
        $query = $this->model()->query()
            ->orderBy($this->model()->getTable().'.id', 'desc');

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
            $query = app($this->field['type'])->queryFor($query, $this);
        }

        if (method_exists($this->model, 'indexQuery')) {
            $query = $this->model->indexQuery($query, $this);
        }

        // If query is set, use it
        if ($this->query && is_string($this->query)) {
            try {
                $query = app('dynamicFunctions')::call($this->query);
            } catch (\Exception $e) {
                // Handle the exception
            }
        }

        // when model is instance Resource, eager load meta
        if ($this->model->usesMeta()) {
            $query = $query->with(['meta']);
        }

        if ($this->filters) {
            $query = $this->applyTaxonomyFilter($query);
            $query = $this->applyCustomFilter($query);
        }

        // Search
        $query = $this->applySearch($query);

        $query = $this->applySorting($query);

        $query = $query->paginate($this->perPage);

        return $query;
    }

    public function boot()
    {
        $this->rowIds = $this->rows()->pluck('id')->toArray();
    }

    public function loadTable()
    {
        $this->loaded = true;
    }

    public function mount()
    {
        // if ($this->parentModel) {
        //     // dd($this->parentModel);
        // }

        $this->dispatch('tableMounted');

        if ($this->selectedFilter) {
            if (array_key_exists($this->selectedFilter, $this->userFilters)) {
                $this->filters = $this->userFilters[$this->selectedFilter];
            }
        }

        $this->tableView = $this->model()->defaultTableView();



        if (auth()->user()->getOptionColumns($this->model()->getType())) {
            $this->columns = auth()->user()->getOptionColumns($this->model()->getType());
        } else {
            $this->columns = $this->model()->getDefaultColumns();
        }

        $this->initiateSettings();

        $this->setTaxonomyFilters();
    }

    public function openBulkActionModal($action, $data)
    {
        $this->dispatch('openModal', $data['modal'], [
            'action' => $action,
            'selected' => $this->selectedRowsQuery->pluck('id'),
            'model' => get_class($this->model),
        ]);
    }

    public function refreshTableSelected()
    {
        $this->selected = [];
    }

    public function getRows()
    {
        return $this->rows();
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
            'rows' => $this->rows(),
        ]);
    }

    public function setPageTen()
    {
        $this->setPage(10);
    }

    public function refreshRows()
    {
        unset($this->rowsQuery);
        unset($this->rows);
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

    public function selectFieldRows($value, $slug)
    {
        if ($slug == $this->field['slug']) {

            $this->selected = $value;
        }
    }

    /**
     * Select a single row in the table.
     *
     * @param $id int The id of the row to select.
     * @return void
     */
    public function selectRow($id)
    {
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
            ray('Save the columns for the current user', $this->columns);
            auth()->user()->updateOption('columns.'.$this->model()->getType(), $this->columns);
        }
    }


    public function allTableRows()
    {
        return $this->rowsQuery()->pluck('id')->all();
    }

    public function updatedPage($page)
    {
        $this->rowIds = $this->rows()->pluck('id')->toArray();
    }

    /**
     * Update the selected rows in the table.
     *
     * @return void
     */
    public function updatedSelected()
    {
        return;

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
        // ray('hier', $this->model);

        return $this->model;
    }

}
