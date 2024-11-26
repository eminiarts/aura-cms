## ./Traits/SwitchView.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait SwitchView
{
    public $currentView;

    public function mountSwitchView()
    {
        $userPreference = auth()->user()->getOption('table_view.'.$this->model()->getType());

        $this->currentView = $userPreference ?? $this->settings['default_view'];
    }

    public function switchView($view)
    {
        if (in_array($view, ['list', 'kanban', 'grid'])) {
            $this->currentView = $view;
            $this->saveViewPreference();
        }
    }

    protected function saveViewPreference()
    {
        auth()->user()->updateOption('table_view.'.$this->model()->getType(), $this->currentView);
    }
}
```

## ./Traits/BulkActions.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

/**
 * Trait for bulk actions in Livewire table component
 */
trait BulkActions
{
    public $bulkActionsView = 'aura::components.table.bulkActions';

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

        // Clear the selected array
        $this->selected = [];

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

        $this->dispatch('refreshTable');
    }

    /**
     * Get the available bulk actions.
     *
     * @return mixed
     */
    public function getBulkActionsProperty()
    {
        return $this->model->getBulkActions();
    }
}
```

## ./Traits/Filters.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;

/**
 * Trait for handling filters in Livewire Table component.
 */
trait Filters
{
    /**
     * An array of filters, with two keys: taxonomy and custom.
     *
     * @var array
     */
    // #[Reactive]
    public $filters = [
        'custom' => [],
    ];

    /**
     * The selected filter.
     *
     * @var mixed
     */
    public $selectedFilter;

    /**
     * A boolean value indicating whether the save filter modal is shown.
     *
     * @var bool
     */
    public $showSaveFilterModal = false;

    /**
     * Add a custom filter.
     *
     * @return void
     */
    public function addFilter()
    {
        $this->filters['custom'][] = [
            'name' => $this->fieldsForFilter->keys()->first(),
            'operator' => 'contains',
            'value' => null,
            'main_operator' => 'and',
        ];
    }

    public function clearFiltersCache()
    {
        auth()->user()->clearCachedOption($this->model->getType().'.filters.*');
        auth()->user()->currentTeam->clearCachedOption($this->model->getType().'.filters.*');
    }

    /**
     * Delete a filter.
     *
     * @param  mixed  $filter
     * @return void
     */
    public function deleteFilter($filterName)
    {
        // Retrieve the filter using the provided key
        $filter = $this->userFilters[$filterName] ?? null;

        if (! $filter) {
            throw new \InvalidArgumentException('Invalid filter name: '.$filterName);
        }

        switch ($filter['type']) {
            case 'user':
                auth()->user()->deleteOption($this->model->getType().'.filters.'.$filterName);

                break;
            case 'team':
                auth()->user()->currentTeam->deleteOption($this->model->getType().'.filters.'.$filterName);

                break;
            default:
                // Handle unexpected type value
                throw new \InvalidArgumentException('Invalid filter type: '.$filter['type']);
        }

        $this->notify('Success: Filter deleted!');
        $this->clearFiltersCache();
        $this->reset('filters');

        $filters = $this->userFilters;

        $this->reset('selectedFilter');

        // Refresh Component
        $this->dispatch('refreshTable');
    }

    #[Computed]
    public function fieldsForFilter()
    {
        return $this->fields->mapWithKeys(function ($field) {
            $fieldInstance = app($field['type']);

            return [
                $field['slug'] => [
                    'name' => $field['name'],
                    'type' => class_basename($field['type']),
                    'filterOptions' => $fieldInstance->filterOptions(),
                    'filterValues' => $fieldInstance->getFilterValues($this->model, $field),
                ],
            ];
        });
    }

    #[Computed]
    public function getFields()
    {
        return $this->fields->mapWithKeys(function ($field) {
            return [$field['slug'] => $field];
        });
    }

    // /**
    //  * Get the fields for filter .
    //  *
    //  * @return mixed
    //  */
    // #[Computed]
    // public function fieldsForFilter()
    // {
    //     return $this->fields->pluck('name', 'slug');
    // }

    /**
     * Remove a custom filter.
     *
     * @param  int  $index
     * @return void
     */
    public function removeCustomFilter($index)
    {
        unset($this->filters['custom'][$index]);
        $this->filters['custom'] = array_values($this->filters['custom']);
    }

    /**
     * Reset the filters.
     *
     * @return void
     */
    public function resetFilter()
    {
        $this->reset('filters');
    }

    /**
     * Save the selected filter.
     *
     * Validate the filter name is required, save the filter per user, and set the selected filter.
     */
    public function saveFilter()
    {
        $this->validate([
            'filter.name' => 'required',
            'filter.public' => 'required',
            'filter.global' => 'required',
            'filter.icon' => '',
        ]);

        $newFilter = array_merge($this->filters, $this->filter);
        $slug = Str::slug($this->filter['name']);

        // If the slug is empty (e.g., for numbers or special characters), generate a unique identifier
        if (empty($slug)) {
            $slug = 'filter_'.Str::random(10);
        }

        $newFilter['slug'] = $slug;

        if ($this->filters) {
            // Save for Team
            if ($this->filter['global']) {
                auth()->user()->currentTeam->updateOption($this->model->getType().'.filters.'.$slug, $newFilter);
            }
            // Save for User
            else {
                auth()->user()->updateOption($this->model->getType().'.filters.'.$slug, $newFilter);
            }
        }

        $this->selectedFilter = $slug;
        $this->notify('Filter saved successfully!');
        $this->showSaveFilterModal = false;
        $this->reset('filter');
        $this->clearFiltersCache();
    }


    public function updatedFiltersCustom($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 5 && $parts[4] === 'name') {
            $groupKey = $parts[1];
            $filterKey = $parts[3];
            // Reset the operator when the field changes
            $this->filters['custom'][$groupKey]['filters'][$filterKey]['operator'] = array_key_first($this->fieldsForFilter[$value]['filterOptions']);
            // Also reset the value
            $this->filters['custom'][$groupKey]['filters'][$filterKey]['value'] = null;
        }
    }

    /**
     * Update the selected filter.
     *
     * Get the filter from options in userFilters.
     *
     * @param  string  $filter
     */
    public function updatedSelectedFilter($filter)
    {
        ray('updatedSelectedFilter', $filter);
        $this->clearFiltersCache();

        // Reset filters first
        $this->reset('filters');

        if ($filter) {
            // Get the filter data
            $filterData = $this->userFilters[$filter];

            // Force a new array assignment to trigger reactivity
            $this->filters = [
                'custom' => array_values($filterData['custom'] ?? [])
            ];
        }

        // Force a rerender of the component
        $this->dispatch('refresh');
    }

    /**
     * Get the user filters .
     *
     * @return mixed
     */
    #[Computed]
    public function userFilters()
    {
        $userFilters = auth()->user()->getOption($this->model()->getType().'.filters.*') ?? collect();
        $teamFilters = collect();

        if (config('aura.teams')) {
            $teamFilters = optional(auth()->user()->currentTeam)->getOption($this->model()->getType().'.filters.*') ?? collect();
        }

        // Add 'type' => 'user' and ensure 'slug' exists for each user filter
        $userFilters = $userFilters->map(function ($filter, $key) {
            $filter['type'] = 'user';
            $filter['slug'] = $filter['slug'] ?? $key;

            return $filter;
        });

        // Add 'type' => 'team' and ensure 'slug' exists for each team filter
        $teamFilters = $teamFilters->map(function ($filter, $key) {
            $filter['type'] = 'team';
            $filter['slug'] = $filter['slug'] ?? $key;

            return $filter;
        });

        // Use concat to merge collections and convert to array
        return collect($userFilters)->merge($teamFilters)->keyBy('slug')->toArray();
    }

    public function addFilterGroup()
    {
        $this->filters['custom'][] = [
            'filters' => [
                $this->newFilter(),
            ],
        ];
    }

    public function addSubFilter($groupKey)
    {
        $this->filters['custom'][$groupKey]['filters'][] = $this->newFilter();
    }

    private function newFilter()
    {
        return [
            'name' => $this->fieldsForFilter->keys()->first(),
            'operator' => 'contains',
            'value' => null,
            'options' => [],
        ];
    }

    public function removeFilterGroup($groupKey)
    {
        unset($this->filters['custom'][$groupKey]);
        $this->filters['custom'] = array_values($this->filters['custom']);
    }

    public function removeFilter($groupKey, $filterKey)
    {
        unset($this->filters['custom'][$groupKey]['filters'][$filterKey]);
        $this->filters['custom'][$groupKey]['filters'] = array_values($this->filters['custom'][$groupKey]['filters']);

        if (empty($this->filters['custom'][$groupKey]['filters'])) {
            $this->removeFilterGroup($groupKey);
        }
    }
}
```

## ./Traits/Kanban.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Livewire\Attributes\On;

/**
 * Trait to handle sorting functionality.
 */
trait Kanban
{
    public $kanbanStatuses = [];

    public function mountKanban()
    {
        if ($this->currentView != 'kanban') {
            return;
        }

        $this->initializeKanbanStatuses();

        if (method_exists($this->model, 'kanbanPagination')) {
            $this->perPage = $this->model->kanbanPagination();
        }

    }

    public function reorderKanbanColumns($newOrder)
    {
        // Filter out empty values from $newOrder using Laravel's collection methods
        $newOrder = collect($newOrder)->filter()->values();

        $reorderedStatuses = collect();

        // Reorder based on $newOrder
        foreach ($newOrder as $key) {
            if (isset($this->kanbanStatuses[$key])) {
                $reorderedStatuses[$key] = $this->kanbanStatuses[$key];
            }
        }

        // Add any remaining statuses that weren't in $newOrder
        foreach ($this->kanbanStatuses as $key => $status) {
            if (! $reorderedStatuses->has($key)) {
                $reorderedStatuses[$key] = $status;
            }
        }

        $this->kanbanStatuses = $reorderedStatuses->toArray();

        $this->saveKanbanStatusesOrder();
    }

    public function reorderKanbanStatuses($statuses)
    {
        // Create a new collection from the ordered status keys
        $orderedStatuses = collect($statuses);

        // Create a new collection to store the reordered kanban statuses
        $reorderedKanbanStatuses = collect();

        // Iterate through the ordered status keys and rebuild the kanban statuses array
        foreach ($orderedStatuses as $statusKey) {
            if (isset($this->kanbanStatuses[$statusKey])) {
                $reorderedKanbanStatuses[$statusKey] = $this->kanbanStatuses[$statusKey];
            }
        }

        // Update the kanban statuses with the new order
        $this->kanbanStatuses = $reorderedKanbanStatuses->toArray();

        $this->saveKanbanStatusesOrder();
    }

    public function updatedKanbanStatuses()
    {
        $this->saveKanbanStatusesOrder();
    }

    protected function applyKanbanQuery($query)
    {

        if ($this->model->kanbanQuery($query)) {
            return $this->model->kanbanQuery($query);
        }

        return $query;
    }

    protected function initializeKanbanStatuses()
    {
        $statuses = $this->model->fieldBySlug('status')['options'];
        $this->kanbanStatuses = collect($statuses)->mapWithKeys(function ($status) {
            return [$status['key'] => [
                'value' => $status['value'],
                'color' => $status['color'],
                'visible' => true,
            ]];
        })->toArray();

        // Load user preferences if they exist
        $userPreferences = auth()->user()->getOption('kanban_statuses.'.$this->model()->getType());
        if ($userPreferences) {
            $this->kanbanStatuses = $userPreferences;
        }
    }

    protected function saveKanbanStatusesOrder()
    {
        auth()->user()->updateOption('kanban_statuses.'.$this->model()->getType(), $this->kanbanStatuses);
    }
}
```

## ./Traits/Settings.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait Settings
{
    public function defaultSettings()
    {
        return [
            'per_page' => 10,
            'columns' => $this->model->getTableHeaders(),
            'filters' => [],
            'search' => '',
            'sort' => [
                'column' => 'id',
                'direction' => 'desc',
            ],
            'settings' => true,
            'sort_columns' => true,
            'columns_global_key' => false,
            'columns_user_key' => 'columns.'.$this->model->getType(),
            'search' => true,
            'filters' => true,
            'global_filters' => true,
            'title' => true,
            'selectable' => true,
            'default_view' => $this->model->defaultTableView(),
            // 'current_view' => $this->model->defaultTableView(),
            'header_before' => true,
            'header_after' => true,
            'table_before' => true,
            'table_after' => true,
            'create' => true,
            'actions' => true,
            'bulk_actions' => true,
            'header' => true,
            'edit_in_modal' => false,
            'create_in_modal' => false,
            'views' => [
                'table' => 'aura::components.table.index',
                'list' => $this->model->tableView(),
                'grid' => $this->model->tableGridView(),
                'kanban' => $this->model->tableKanbanView(),
                'filter' => 'aura::components.table.filter',
                'header' => 'aura::components.table.header',
                'row' => $this->model->rowView(),
                'bulkActions' => 'aura::components.table.bulkActions',
                'table-header' => 'aura::components.table.table-header',
                'table_footer' => 'aura::components.table.footer',
            ],
        ];
    }

    public function mountSettings()
    {
        $this->settings = $this->array_merge_recursive_distinct($this->defaultSettings(), $this->settings ?: []);
    }

    protected function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
```

## ./Traits/Sorting.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;

/**
 * Trait to handle sorting functionality.
 */
trait Sorting
{
    /**
     * Collection of sort field and direction.
     *
     * @var array
     */
    public $sorts = [];

    /**
     * Sort by the specified field.
     *
     * @param  string  $field
     * @return void
     */
    public function sortBy($field)
    {
        $this->sorts = collect($this->sorts)->filter(function ($value, $key) use ($field) {
            return $key === $field;
        })->toArray();

        if (! isset($this->sorts[$field])) {
            $this->sorts[$field] = 'asc';

            return;
        }

        if ($this->sorts[$field] === 'asc') {
            $this->sorts[$field] = 'desc';

            return;
        }

        unset($this->sorts[$field]);
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySorting($query)
    {
        if ($this->sorts) {
            $query->getQuery()->orders = null;
        }

        foreach ($this->sorts as $field => $direction) {
            if ($this->model->isTaxonomyField($field)) {
                $taxonomy = Str::singular(ucfirst($field));

                $query->withFirstTaxonomy($taxonomy, $this->model->getMorphClass())
                    ->orderByRaw('CASE WHEN first_taxonomy IS NULL THEN 1 WHEN first_taxonomy = "" THEN 1 ELSE 0 END')
                    ->orderBy('first_taxonomy', $direction)
                    ->orderBy('id', 'desc');

                return $query;
            }

            if ($this->model->usesMeta() && $this->model->isMetaField($field)) {
                $query->leftJoin('meta', function ($join) use ($field) {
                    $join->on('posts.id', '=', 'meta.metable_id')
                        ->where('meta.metable_type', '=', $this->model->getMorphClass())
                        ->where('meta.key', '=', "$field");
                })
                    ->select('posts.*')
                    ->when($this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS DECIMAL(10,2)) '.$direction);
                    })
                    ->when(! $this->model->isNumberField($field), function ($query) use ($direction) {
                        $query->orderByRaw('CAST(meta.value AS CHAR) '.$direction);
                    })
                    ->orderBy('id', 'desc');

                return $query;
            } else {
                $query->orderBy($field, $direction);

                return $query;
            }
        }

        $query->getQuery()->orders = null;

        // default sort
        $query->orderBy($this->model->getTable().'.'.$this->model->defaultTableSort(), $this->model->defaultTableSortDirection());

        return $query;
    }
}
```

## ./Traits/Search.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Facades\DB;

trait Search
{
    public $search;

    public function applySearch($query)
    {
        if ($this->search) {

            ray($this->search);

            // Check if there is a search method in the model (modifySearch()), and call it.
            if (method_exists($this->model, 'modifySearch')) {
                $query = $this->model->modifySearch($query, $this->search);

                return $query;
            }

            $searchableFields = $this->model->getSearchableFields()->pluck('slug');
            $metaFields = $searchableFields->filter(fn ($field) => $this->model->isMetaField($field));

            $query->where(function ($query) use ($searchableFields, $metaFields) {
                // Search in regular fields
                foreach ($searchableFields as $field) {
                    if (! $metaFields->contains($field)) {
                        $query->orWhere($this->model->getTable().'.'.$field, 'like', '%'.$this->search.'%');
                    }
                }

                // Search in meta fields
                if ($metaFields->count() > 0) {
                    $metaTable = $this->model->getMetaTable();
                    $query->orWhereExists(function ($query) use ($metaTable, $metaFields) {
                        $query->select(DB::raw(1))
                            ->from($metaTable)
                            ->whereColumn($this->model->getTable().'.id', $metaTable.'.'.$this->model->getMetaForeignKey())
                            ->whereIn($metaTable.'.key', $metaFields)
                            ->where($metaTable.'.value', 'like', '%'.$this->search.'%');
                    });
                }
            });

        }

        return $query;
    }
}
```

## ./Traits/QueryFilters.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

trait QueryFilters
{
    protected function applyCustomFilter(Builder $query): Builder
{
    if (empty($this->filters['custom'])) {
        return $query;
    }

    $groups = $this->filters['custom'];

    // Start by building the conditions from the first group
    $condition = function ($query) use ($groups) {
        $this->applyFilterGroup($query, $groups[0]);
    };

    for ($i = 1; $i < count($groups); $i++) {
        $group = $groups[$i];
        $operator = $group['operator'] ?? 'and';

        // Create a new condition that wraps the previous condition and combines it with the current group
        $previousCondition = $condition;

        $condition = function ($query) use ($previousCondition, $group, $operator) {
            $query->where(function ($q) use ($previousCondition, $group, $operator) {
                // Wrap previous conditions
                $q->where(function ($subQ) use ($previousCondition) {
                    $previousCondition($subQ);
                });

                // Combine with current group using its operator
                $method = $operator === 'and' ? 'where' : 'orWhere';

                $q->$method(function ($subQ) use ($group) {
                    $this->applyFilterGroup($subQ, $group);
                });
            });
        };
    }

    // Apply the accumulated condition to the main query
    $query->where(function ($q) use ($condition) {
        $condition($q);
    });

    return $query;
}


    protected function applyFilterGroup(Builder $query, array $group): void
    {
        foreach ($group['filters'] as $filterIndex => $filter) {
            if ($this->isValidFilter($filter)) {
                if ($filterIndex > 0) {
                    $groupOperator = $filter['main_operator'] ?? 'and';
                    $this->applyFilter($query, $filter, $groupOperator);
                } else {
                    $this->applyFilter($query, $filter, 'and');
                }
            }
        }
    }

    protected function applyFilter(Builder $query, array $filter, string $groupOperator): void
    {
        $method = $groupOperator === 'or' ? 'orWhere' : 'where';

        $query->$method(function ($subQuery) use ($filter) {
            $this->applyFilterBasedOnType($subQuery, $filter);
        });
    }

    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        if ($this->model->usesCustomTable() || $this->model->isTableField($filter['name'])) {
            $this->applyTableFieldFilter($query, $filter);
        } else {
            $this->applyMetaFieldFilter($query, $filter);
        }
    }

    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->where(function ($query) use ($filter) {
            $query->whereDoesntHave('meta', function (Builder $query) use ($filter) {
                $query->where('key', '=', $filter['name']);
            })
                ->orWhereHas('meta', function (Builder $query) use ($filter) {
                    $query->where('key', '=', $filter['name'])
                        ->where(function ($query) {
                            $query->where('value', '=', '')
                                ->orWhereNull('value');
                        });
                });
        });
    }

    protected function applyIsNotEmptyMetaFilter(Builder $query, array $filter): void
    {
        $query->whereHas('meta', function (Builder $query) use ($filter) {
            $query->where('key', '=', $filter['name'])
                ->where(function ($query) {
                    $query->where('value', '!=', '')
                        ->whereNotNull('value');
                });
        });
    }

    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        switch ($filter['operator']) {
            case 'is_empty':
                $this->applyIsEmptyMetaFilter($query, $filter);
                break;
            case 'is_not_empty':
                $this->applyIsNotEmptyMetaFilter($query, $filter);
                break;
            default:
                $this->applyStandardMetaFilter($query, $filter);
        }

        return $query;
    }

    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        switch ($filter['operator']) {
            case 'contains':
                $query->where('value', 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where('value', 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where('value', 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where('value', 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where('value', '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where('value', '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where('value', '>', $filter['value']);
                break;
            case 'less_than':
                $query->where('value', '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where('value', '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where('value', '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn('value', explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn('value', explode(',', $filter['value']));
                break;
            case 'like':
                $query->where('value', 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where('value', 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where('value', 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where('value', 'not regexp', $filter['value']);
                break;
            case 'date_is':
                $query->whereDate('value', '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate('value', '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate('value', '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate('value', '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate('value', '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate('value', '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) {
                    $query->whereNull('value')
                        ->orWhere('value', '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull('value')
                    ->where('value', '!=', '');
                break;
        }
    }

protected function applyStandardMetaFilter(Builder $query, array $filter): void
{
    if (isset($filter['options']) && isset($filter['options']['resource_type'])) {

        // dd($filter);

        $resourceType = $filter['options']['resource_type'];
        $values = (array) $filter['value'];

        $slug = $filter['name'];
        $relatedType = get_class($query->getModel());

        // dd($resourceType, $values, $filter, $relatedType);
        if ($filter['operator'] === 'contains') {
            $query->whereIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                $subQuery->select('related_id')
                    ->from('post_relations')
                    ->where('post_relations.related_type', $relatedType)
                    ->where('post_relations.resource_type', $resourceType)
                    ->where('post_relations.slug', $slug)
                    ->whereIn('post_relations.resource_id', $values);
            });
        } elseif ($filter['operator'] === 'does_not_contain') {
            $query->whereNotIn('id', function ($subQuery) use ($resourceType, $values, $slug, $relatedType) {
                $subQuery->select('related_id')
                    ->from('post_relations')
                    ->where('post_relations.related_type', $relatedType)
                    ->where('post_relations.resource_type', $resourceType)
                    ->where('post_relations.slug', $slug)
                    ->whereIn('post_relations.resource_id', $values);
            });
        }

        return;
    }

    $query->whereHas('meta', function (Builder $query) use ($filter) {
        $query->where('key', '=', $filter['name']);
        $this->applyOperatorCondition($query, $filter);
    });
}

    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
        if (is_array($filter['value'])) {
            $filter['value'] = implode(',', $filter['value']);
        }
        switch ($filter['operator']) {
            case 'contains':
                $query->where($filter['name'], 'like', '%'.$filter['value'].'%');
                break;
            case 'does_not_contain':
                $query->where($filter['name'], 'not like', '%'.$filter['value'].'%');
                break;
            case 'starts_with':
                $query->where($filter['name'], 'like', $filter['value'].'%');
                break;
            case 'ends_with':
                $query->where($filter['name'], 'like', '%'.$filter['value']);
                break;
            case 'is':
            case 'equals':
                $query->where($filter['name'], '=', $filter['value']);
                break;
            case 'is_not':
            case 'not_equals':
                $query->where($filter['name'], '!=', $filter['value']);
                break;
            case 'greater_than':
                $query->where($filter['name'], '>', $filter['value']);
                break;
            case 'less_than':
                $query->where($filter['name'], '<', $filter['value']);
                break;
            case 'greater_than_or_equal':
                $query->where($filter['name'], '>=', $filter['value']);
                break;
            case 'less_than_or_equal':
                $query->where($filter['name'], '<=', $filter['value']);
                break;
            case 'in':
                $query->whereIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'not_in':
                $query->whereNotIn($filter['name'], explode(',', $filter['value']));
                break;
            case 'like':
                $query->where($filter['name'], 'like', $filter['value']);
                break;
            case 'not_like':
                $query->where($filter['name'], 'not like', $filter['value']);
                break;
            case 'regex':
                $query->where($filter['name'], 'regexp', $filter['value']);
                break;
            case 'not_regex':
                $query->where($filter['name'], 'not regexp', $filter['value']);
                break;
            case 'is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
            case 'date_is':
                $query->whereDate($filter['name'], '=', $filter['value']);
                break;
            case 'date_is_not':
                $query->whereDate($filter['name'], '!=', $filter['value']);
                break;
            case 'date_before':
                $query->whereDate($filter['name'], '<', $filter['value']);
                break;
            case 'date_after':
                $query->whereDate($filter['name'], '>', $filter['value']);
                break;
            case 'date_on_or_before':
                $query->whereDate($filter['name'], '<=', $filter['value']);
                break;
            case 'date_on_or_after':
                $query->whereDate($filter['name'], '>=', $filter['value']);
                break;
            case 'date_is_empty':
                $query->where(function ($query) use ($filter) {
                    $query->whereNull($filter['name'])
                        ->orWhere($filter['name'], '=', '');
                });
                break;
            case 'date_is_not_empty':
                $query->whereNotNull($filter['name'])
                    ->where($filter['name'], '!=', '');
                break;
        }

        return $query;
    }

    protected function isValidFilter(array $filter): bool
    {
        return !empty($filter['name']) &&
               (!empty($filter['value']) || in_array($filter['operator'], ['is_empty', 'is_not_empty']));
    }
}
```

## ./Traits/Select.php
```
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
```

## ./Traits/CachedRows.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

trait CachedRows
{
    /**
     * Use cache flag
     *
     * @var bool
     */
    protected $useCache = false;

    /**
     * Enable the use of cache
     *
     * @return void
     */
    public function useCachedRows()
    {
        $this->useCache = true;
    }

    /**
     * Store result in cache and return result
     *
     * @return mixed
     */
    protected function cache(callable $callback)
    {
        $cacheKey = $this->id;

        if ($this->useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $result = $callback();

        cache()->put($cacheKey, $result);

        return $result;
    }
}
```

## ./Traits/PerPagePagination.php
```
<?php

namespace Aura\Base\Livewire\Table\Traits;

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
    public function mountPerPagePagination()
    {
        $this->perPage = session()->has('perPage') ? session()->get('perPage') : $this->model()->defaultPerPage();
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
```

## ./Table.php
```
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

        // if custom
        // dd($data);

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
        if ($this->model()->createUrl()) {
            return $this->model()->createUrl();
        }

        if ($this->parent) {
            return route('aura.'.$this->model()->getSlug().'.create', [
                'for' => $this->parent->getType(),
                'id' => $this->parent->id,
            ]);
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

        // ray('headers', $sort);

        return $headers;
    }

    public function loadTable()
    {
        $this->loaded = true;
    }

    #[Computed]
    public function model()
    {
        // ray('hier', $this->model);

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
        // if ($this->parentModel) {
        //     // dd($this->parentModel);
        // }

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
        ray('render', $this->search, count($this->rows()), $this->rows()->toArray());
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
            //ray('Save the columns for the current user', $this->columns);
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
        // ray('table updatedSelected', $this->selected);
        // return;

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
            $query = $this->model->indexQuery($query, $this);
        }

        if ($this->field && method_exists(app($this->field['type']), 'queryFor')) {
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
        $query = $this->query();

        if ($this->filters) {
            $query = $this->applyCustomFilter($query);
        }

        // Search
        $query = $this->applySearch($query);

        $query = $this->applySorting($query);

        $query = $query->paginate($this->perPage);

        return $query;
    }
}
```

