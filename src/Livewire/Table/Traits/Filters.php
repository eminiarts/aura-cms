<?php

namespace Eminiarts\Aura\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

use Livewire\Attributes\Reactive;
use Illuminate\Support\Facades\Cache;

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

        // dd('delete', $filterName, $filter, $this->userFilters);

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

        //$this->reset('userFilters');

        $this->reset('selectedFilter');

        // Refresh userFilters
        $this->userFilters = $this->getUserFilters();

        // Refresh Component
        $this->dispatch('refreshTable');
    }

    /**
     * Get the fields for filter .
     *
     * @return mixed
     */
    #[Computed]
    public function FieldsForFilter()
    {
        return $this->fields->pluck('name', 'slug');
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

        // Add 'type' => 'user' to each user filter
        $userFilters = $userFilters->map(function ($filter) {
            $filter['type'] = 'user';

            return $filter;
        });

        // Add 'type' => 'team' to each team filter
        $teamFilters = $teamFilters->map(function ($filter) {
            $filter['type'] = 'team';

            return $filter;
        });

        // Use concat to merge collections and convert to array
        return collect($userFilters)->merge($teamFilters)->toArray();
    }

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
    * Set taxonomy filters.
    */
    public function setTaxonomyFilters()
    {
        $this->filters['taxonomy'] = $this->model?->taxonomyFields()
            ->values()
            ->mapWithKeys(fn ($field) => [$field['slug'] => []])
            ->toArray();
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

        // ray($newFilter)->green();
        // ray($this->filter, $this->filters)->red();

        if ($this->filters) {

            // Save for Team
            if ($this->filter['global']) {
                auth()->user()->currentTeam->updateOption($this->model->getType().'.filters.'.Str::slug($this->filter['name']), $newFilter);
            }
            // Save for User
            else {
                auth()->user()->updateOption($this->model->getType().'.filters.'.Str::slug($this->filter['name']), $newFilter);
            }

        }

        $this->selectedFilter = Str::slug($this->filter['name']);
        $this->notify('Filter saved successfully!');
        $this->showSaveFilterModal = false;
        $this->reset('filter');

        $this->clearFiltersCache();

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
}