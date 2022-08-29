<?php

namespace Eminiarts\Aura\Http\Livewire\Table;

use App\Http\Livewire\Table\Traits\BulkActions;
use App\Http\Livewire\Table\Traits\PerPagePagination;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;

class Table extends Component
{
    use BulkActions;
    use PerPagePagination;

    public $model;

    public $settings;

    private $query;

    public $filters = [];

    public $columns = [];

    public $search;

    public function render()
    {
        return view('livewire.table.table');
    }

    public function mount($query = null)
    {
        // $test = User::query();

        // $new = ($this->query)($test);

        //dd($this->query);

        // if ($this->query) {
        //     dd($this->query);
        // }

        $this->query = $query;
        // dump($query);
        // dump($this->query);

        // dd($this->settings, $new->get());
        // $this->fields = $this->model->inputFields();

        // dd($this->model, $this->model->first());
        // dd($this->query);
        // dd(($this->query)(User::query()));

        if (auth()->user()->getColumns($this->model->getType())) {
            $this->columns = auth()->user()->getColumns($this->model->getType());
        } else {
            $this->columns = $this->model->getDefaultColumns();
        }
    }

    public function getFieldsProperty()
    {
        return $this->model->inputFields();
    }

    public function getBulkActionsProperty()
    {
        return $this->model->bulkActions;
    }

    public function bulkAction($action)
    {
        $this->selectedRowsQuery->each(function ($item, $key) use ($action) {
            $item->{$action}();
        });

        $this->notify('Erfolgreich: '.$action);

        //dd('bulk', $action, $this->selected);
    }

    public function toggleColumn($column)
    {
        dd($column, $this->columns);
    }

    public function addFilter()
    {
        $this->filters[] = [
            'name' => $this->fieldsForFilter->keys()->first(),
            'operator' => null,
            'value' => null,
        ];
    }

    public function search()
    {
    }

    public function getFieldsForFilterProperty()
    {
        return $this->fields->pluck('name', 'slug');
    }

    public function getHeadersProperty()
    {
        return $this->model->getHeaders();
    }

    public function resetFilter()
    {
        $this->filters = [];
    }

    public function updatedColumns($columns)
    {
        //dump('updated columns', $columns, $this->columns);
        // Save Columns per User
        if ($this->columns) {
            auth()->user()->updateOption('columns.'.$this->model->getType(), $this->columns);
        }
    }

    public function getRowsQueryProperty()
    {
        $query = $this->model->query()->with('meta');

        if ($this->query) {
            // dump('query here', $this->query);
            $query = ($this->query)($query);
        }

        // dd($query, $this->query);

        $operators = [
            'conains' => '%LIKE%',
            'does_not_contain' => '',
            'starts_with' => '',
            'ends_with' => '',
            'is' => '==',
            'greater_than' => '>=',
        ];

        if ($this->filters) {
            foreach ($this->filters as $filter) {
                if (! $filter['name'] || ! $filter['value']) {
                    continue;
                }
                $query->whereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', '=', $filter['name'])->where('value', 'like', $filter['value'].'%');
                });
            }
        }

        // More advanced Search
        if ($this->search) {
            $query->where('title', 'LIKE', $this->search.'%');
        }

        // dd($this->model->paginate(5)->toArray());
        return $query;
    }

    public function getRowsProperty()
    {
        return $this->rowsQuery->paginate(10);
    }
}
