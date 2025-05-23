<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Traits\BulkActions;
use Aura\Base\Livewire\Table\Traits\Filters;
use Aura\Base\Livewire\Table\Traits\Kanban;
use Aura\Base\Livewire\Table\Traits\PerPagePagination;
use Aura\Base\Livewire\Table\Traits\QueryFilters;
use Aura\Base\Livewire\Table\Traits\Search;
use Aura\Base\Livewire\Table\Traits\Select;
use Aura\Base\Livewire\Table\Traits\Settings;
use Aura\Base\Livewire\Table\Traits\Sorting;
use Aura\Base\Livewire\Table\Traits\SwitchView;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Class Table
 */
class Table extends Component
{
    use BulkActions;
    use Filters;
    use Kanban;
    use PerPagePagination;
    use QueryFilters;
    use Search;
    use Select;
    use Settings;
    use Sorting;
    use SwitchView;

    public $bulkActionsView = 'aura::components.table.bulkActions';

    /**
     * List of table columns.
     *
     * @var array
     */
    public $columns = [];

    public $disabled;

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

    public $form;

    /**
     * The last clicked row.
     *
     * @var mixed
     */
    public $lastClickedRow;

    public $loaded = false;

    public $model;

    /**
     * The parent of the table.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    public $query;

    public $resource;

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

    /**
     * List of events listened to by the component.
     *
     * @var array
     */
    protected $listeners = [
        'refreshTable' => '$refresh',
        'selectedRows' => '$refresh',
        'selectRowsRange' => 'selectRowsRange',
        'refreshTableSelected' => 'refreshTableSelected',
        'selectFieldRows',
    ];

    protected $queryString = ['selectedFilter'];

    public function action($data)
    {
        // return redirect to post view
        if ($data['action'] == 'view') {
            return redirect()->route('aura.'.$this->model()->getSlug().'.view', ['id' => $data['id']]);
        }
        // edit
        if ($data['action'] == 'edit') {
            return redirect()->route('aura.'.$this->model()->getSlug().'.edit', ['id' => $data['id']]);
        }

        if (method_exists($this->model, $data['action'])) {
            return $this->model()->find($data['id'])->{$data['action']}();
        }
    }

    public function allTableRows()
    {
        return $this->query()->pluck('id')->all();
    }

    public function boot() {}

    /**
     * Get the create link.
     *
     * @return string
     */
    #[Computed]
    public function createLink()
    {
        if ($this->settings['create_url']) {
            return $this->settings['create_url'];
        }

        if ($this->parent) {
            return route('aura.'.$this->model()->getSlug().'.create', [
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
        }

        if ($this->model()->createUrl()) {
            return $this->model()->createUrl();
        }

        return route('aura.'.$this->model()->getSlug().'.create');
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

    public function getAllTableRows()
    {
        return $this->query()->pluck('id')->all();
    }

    public function getParentModel()
    {
        return $this->parent;
    }

    public function getRows()
    {
        return $this->rows();
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

        if ($this->settings['sort_columns'] && $this->settings['columns_global_key']) {
            $option = Aura::getOption($this->settings['columns_global_key']);

            return empty($option) ? $headers->toArray() : $option;
        }

        if ($this->settings['sort_columns'] && $this->settings['columns_user_key'] && $sort = auth()->user()->getOption($this->settings['columns_user_key'])) {

            $headers = collect($headers)->sortBy(function ($value, $key) use ($sort) {
                return array_search($key, array_keys($sort));
            })->toArray();
        }

        return $headers;
    }

    public function loadTable()
    {
        $this->loaded = true;
    }

    #[Computed]
    public function model()
    {
        return $this->model;
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

    public function mount()
    {
        $this->dispatch('tableMounted');

        if ($this->selectedFilter) {
            if (array_key_exists($this->selectedFilter, $this->userFilters)) {
                $this->filters = $this->userFilters[$this->selectedFilter];
            }
        }

        if (empty($this->columns)) {
            if (auth()->user()->getOptionColumns($this->model()->getType())) {
                $this->columns = auth()->user()->getOptionColumns($this->model()->getType());
            } else {
                $this->columns = $this->model()->getDefaultColumns();
            }
        }
    }

    public function openBulkActionModal($action, $data)
    {
        $this->dispatch('openModal', $data['modal'], [
            'action' => $action,
            'selected' => $this->selectedRowsQuery->pluck('id'),
            'model' => get_class($this->model),
        ]);
    }

    public function refreshRows()
    {
        unset($this->rowsQuery);
        unset($this->rows);
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
        return view($this->model->tableComponentView(), [
            'parent' => $this->parent,
            'rows' => $this->rows(),
            'rowIds' => $this->rowIds,
        ]);
    }

    /**
     * Reorder the table columns.
     *
     * @param  $slugs  array The new column order.
     * @return void
     */
    public function reorder($slugs)
    {
        if ($this->settings['columns_global_key']) {
            $orderedSort = array_merge(array_flip($slugs), $this->headers());

            return Aura::updateOption($this->settings['columns_global_key'], $orderedSort);
        }

        // Save the columns for the current user.
        $headers = $this->columns;

        if ($headers instanceof \Illuminate\Support\Collection) {
            $headers = $headers->toArray();
        }

        $orderedSort = [];

        foreach ($slugs as $slug) {
            if (array_key_exists($slug, $headers)) {
                $orderedSort[$slug] = $headers[$slug];
            }
        }

        auth()->user()->updateOption($this->settings['columns_user_key'], $orderedSort);
    }

    #[Computed]
    public function rowIds()
    {
        $rowIds = $this->rows()->pluck('id')->toArray();

        $this->dispatch('rowIdsUpdated', $rowIds);

        return $rowIds;
    }

    /**
     * Build the query for the rows.
     *
     * @return mixed
     */
    public function rowsQuery()
    {
        $query = $this->query();

        if ($this->filters) {
            $query = $this->applyCustomFilter($query);
        }

        // Search
        $query = $this->applySearch($query);

        $query = $this->applySorting($query);

        return $query;
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
     * @param  $id  int The id of the row to select.
     * @return void
     */
    public function selectRow($id)
    {
        $this->selected = $id;
        $this->lastClickedRow = $id;
    }

    public function updateCardStatus($cardId, $newStatus)
    {
        $card = $this->model->find($cardId);
        if ($card) {
            $card->status = $newStatus;
            $card->save();
            $this->notify('Card status updated successfully');
        } else {
            $this->notify('Card not found', 'error');
        }
    }

    /**
     * Update the columns in the table.
     *
     * @param  $columns  array The new columns.
     * @return void
     */
    public function updatedColumns($columns)
    {
        // Save the columns for the current user.
        if ($this->columns) {
            auth()->user()->updateOption('columns.'.$this->model()->getType(), $this->columns);
        }
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

    protected function query()
    {
        $query = $this->model()->query()
            ->orderBy($this->model()->getTable().'.id', 'desc');

        if (method_exists($this->model, 'indexQuery')) {
            // ray('indexQuery');
            $query = $this->model->indexQuery($query, $this);
        }

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
            // ray('queryFor');
            $query = app($this->field['type'])->queryFor($query, $this);
        }

        // If query is set, use it
        if ($this->query && is_string($this->query)) {
            try {
                $query = app('dynamicFunctions')::call($this->query);
            } catch (\Exception $e) {
                // Handle the exception
            }
        }

        // Kanban Query
        if ($this->currentView == 'kanban') {
            $query = $this->applyKanbanQuery($query);
        }

        // when model is instance Resource, eager load meta
        if ($this->model->usesMeta()) {
            $query = $query->with(['meta']);
        }

        return $query;
    }

    /**
     * Get the rows for the table.
     *
     * @return mixed
     */
    protected function rows()
    {
        return $this->rowsQuery()->paginate($this->perPage);
    }
}
