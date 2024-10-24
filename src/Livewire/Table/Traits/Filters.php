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
        'taxonomy' => [],
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
        $this->setTaxonomyFilters();
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

    /**
     * Set taxonomy filters.
     */
    public function setTaxonomyFilters()
    {
        $this->filters['taxonomy'] = $this->model?->taxonomyFields()
            ->values()
            ->mapWithKeys(fn ($field) => [$field['slug'] => []])
            ->toArray();
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
        if ($filter) {
            $this->filters = $this->userFilters[$filter];
        } else {
            $this->reset('filters');
        }

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
