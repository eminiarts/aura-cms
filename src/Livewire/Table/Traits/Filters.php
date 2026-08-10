<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FieldFilterCapabilityResolver;
use Aura\Base\Table\FilterGroupStateMutator;
use Aura\Base\Table\TableQueryState;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

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
        $this->resetSelectionForScopeChange();
        $fieldSlug = $this->fieldsForFilter->keys()->first();

        $this->filters['custom'][] = [
            'name' => $fieldSlug,
            'operator' => $this->defaultOperatorFor($fieldSlug),
            'value' => null,
            'main_operator' => 'and',
        ];
        $this->syncSerializedTableState();
    }

    public function addFilterGroup()
    {
        $this->resetSelectionForScopeChange();
        $this->filters['custom'] = (new FilterGroupStateMutator)->addGroup(
            $this->filters['custom'],
            $this->newFilter(),
        );
        $this->syncSerializedTableState();
    }

    public function addSubFilter($groupKey)
    {
        $this->resetSelectionForScopeChange();
        $this->filters['custom'] = (new FilterGroupStateMutator)->addFilter(
            $this->filters['custom'],
            (int) $groupKey,
            $this->newFilter(),
        );
        $this->syncSerializedTableState();
    }

    public function clearFiltersCache()
    {
        auth()->user()->clearCachedOption($this->model->getType().'.filters.*');
        if (config('aura.teams')) {
            auth()->user()->currentTeam?->clearCachedOption($this->model->getType().'.filters.*');
        }
    }

    /**
     * Delete a filter.
     *
     * @param  mixed  $filter
     * @return void
     */
    public function deleteFilter($filterName)
    {
        $this->resetSelectionForScopeChange();
        // Retrieve the filter using the provided key
        $filter = $this->userFilters[$filterName] ?? null;

        if (! $filter) {
            throw new \InvalidArgumentException('Invalid filter name: '.$filterName);
        }

        switch ($filter['type']) {
            case 'user':
                auth()->user()->deleteOption($this->model->getType().'.filters.'.$filter['slug']);
                break;
            case 'team':
                auth()->user()->currentTeam->deleteOption($this->model->getType().'.filters.'.$filterName);
                break;
            default:
                throw new \InvalidArgumentException('Invalid filter type: '.$filter['type']);
        }

        $this->notify('Success: Filter deleted!');
        $this->clearFiltersCache();
        $this->reset(['filters', 'selectedFilter']);

        // Refresh Component
        $this->dispatch('refreshTable');
    }

    #[Computed]
    public function fieldsForFilter()
    {
        return $this->fields->mapWithKeys(function ($field) {
            $fieldInstance = app($field['type']);

            if (! $fieldInstance instanceof Field) {
                return [];
            }

            $filter = (new FieldFilterCapabilityResolver)->resolve($fieldInstance, $this->model, $field)->toArray();

            return [
                $field['slug'] => [
                    'name' => $field['name'],
                    'type' => class_basename($field['type']),
                    'filterOptions' => $filter['operators'],
                    'filterValues' => $fieldInstance->getFilterValues($this->model, $field),
                    'canonicalFilterValues' => $filter['values'],
                    'filter' => $filter,
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

    /**
     * @param  array<string, mixed>  $filterData
     */
    public function loadSavedFilterState(array $filterData): void
    {
        if (is_array($filterData['query_state'] ?? null)) {
            $this->tableState = TableQueryState::fromArray($filterData['query_state'])->toQueryString();
            $this->hydrateSerializedTableState();

            return;
        }

        $this->filters = [
            'custom' => array_values($filterData['custom'] ?? []),
        ];
        $this->syncSerializedTableState();
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
        $this->resetSelectionForScopeChange();
        unset($this->filters['custom'][$index]);
        $this->filters['custom'] = array_values($this->filters['custom']);
        $this->syncSerializedTableState();
    }

    public function removeFilter($groupKey, $filterKey)
    {
        $this->resetSelectionForScopeChange();
        $this->filters['custom'] = (new FilterGroupStateMutator)->removeFilter(
            $this->filters['custom'],
            (int) $groupKey,
            (int) $filterKey,
        );
        $this->syncSerializedTableState();
    }

    public function removeFilterGroup($groupKey)
    {
        $this->resetSelectionForScopeChange();
        $this->filters['custom'] = (new FilterGroupStateMutator)->removeGroup(
            $this->filters['custom'],
            (int) $groupKey,
        );
        $this->syncSerializedTableState();
    }

    /**
     * Reset the filters.
     *
     * @return void
     */
    public function resetFilter()
    {
        $this->resetSelectionForScopeChange();
        $this->reset('filters');
        $this->syncSerializedTableState();
    }

    /**
     * Save the selected filter.
     *
     * Validate the filter name is required, save the filter per user, and set the selected filter.
     */
    public function saveFilter()
    {
        $this->resetSelectionForScopeChange();
        $this->validate([
            'filter.name' => 'required',
            'filter.public' => 'required',
            'filter.global' => 'required',
            'filter.icon' => '',
        ]);

        $state = TableQueryState::fromLegacy(
            $this->filters,
            $this->search,
            $this->sorts,
            $this->tableState === '' ? null : $this->currentTableQueryState()->parent,
        );
        $newFilter = array_merge($this->filters, $this->filter, [
            'query_state' => $state->toArray(),
        ]);
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

    /**
     * Livewire 4 invokes this property hook with the value followed by the key
     * beneath filters. The full-path branch retains direct-call compatibility.
     */
    public function updatedFilters(mixed $value, mixed $key = null): void
    {
        if (is_string($key) && ($key === 'custom' || str_starts_with($key, 'custom.'))) {
            $path = 'filters.'.$key;
        } elseif (is_string($value) && str_starts_with($value, 'filters.custom.')) {
            $path = $value;
            $value = $key;
        } else {
            return;
        }

        $this->resetSelectionForScopeChange();

        if ($path === 'filters.custom') {
            $this->syncSerializedTableState();

            return;
        }

        $parts = explode('.', substr($path, strlen('filters.custom.')));

        if (count($parts) !== 4 || $parts[1] !== 'filters' || ! ctype_digit($parts[0]) || ! ctype_digit($parts[2])) {
            return;
        }

        [$groupKey, , $filterKey, $property] = $parts;

        if (! isset($this->filters['custom'][(int) $groupKey]['filters'][(int) $filterKey])) {
            return;
        }

        if ($property === 'operator') {
            $this->filters['custom'][(int) $groupKey]['filters'][(int) $filterKey]['value'] = null;
            $this->syncSerializedTableState();

            return;
        }

        if ($property !== 'name') {
            $this->syncSerializedTableState();

            return;
        }

        $operator = is_string($value) && isset($this->fieldsForFilter[$value])
            ? $this->defaultOperatorFor($value)
            : null;

        $this->filters['custom'][(int) $groupKey]['filters'][(int) $filterKey]['operator'] = $operator;
        $this->filters['custom'][(int) $groupKey]['filters'][(int) $filterKey]['value'] = null;
        $this->syncSerializedTableState();
    }

    /**
     * @deprecated Livewire 4 invokes updatedFilters() with the complete property path.
     */
    public function updatedFiltersCustom($value, $key)
    {
        if (! is_string($key)) {
            return;
        }

        $path = str_starts_with($key, 'filters.custom.') ? $key : 'filters.'.$key;

        $this->updatedFilters($path, $value);
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
        $this->resetSelectionForScopeChange();
        $this->clearFiltersCache();

        // Reset filters first
        $this->reset('filters');

        if ($filter) {
            // Get the filter data
            $filterData = $this->userFilters[$filter];
            $this->loadSavedFilterState($filterData);
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

    private function defaultOperatorFor(?string $fieldSlug): ?string
    {
        if ($fieldSlug === null) {
            return null;
        }

        return array_key_first($this->fieldsForFilter[$fieldSlug]['filterOptions'] ?? []);
    }

    private function newFilter(): array
    {
        $fieldSlug = $this->fieldsForFilter->keys()->first();

        return [
            'name' => $fieldSlug,
            'operator' => $this->defaultOperatorFor($fieldSlug),
            'value' => null,
            'options' => [],
        ];
    }
}
