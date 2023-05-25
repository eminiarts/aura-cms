<?php

namespace Eminiarts\Aura\Http\Livewire\Table\Traits;

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

    /**
     * Clear the user filters cache.
     *
     * @return void
     */
    public function clearUserFiltersCache()
    {
        Cache::forget('user.'.auth()->user()->id.'.'.$this->model->getType().'.filters.*');
    }

    /**
     * Delete a filter.
     *
     * @param  mixed  $filter
     * @return void
     */
    public function deleteFilter($filter)
    {
        auth()->user()->deleteOption($this->model->getType().'.filters.'.$filter);
        $this->notify('Success: Filter deleted!');
        $this->clearUserFiltersCache();
        $this->reset('filters');
        $this->reset('selectedFilter');
        $this->setTaxonomyFilters();
    }

    /**
     * Get the fields for filter property.
     *
     * @return mixed
     */
    public function getFieldsForFilterProperty()
    {
        return $this->fields->pluck('name', 'slug');
    }

    /**
     * Get the user filters property.
     *
     * @return mixed
     */
    public function getUserFiltersProperty()
    {
        $userFilters = auth()->user()->getOption($this->model->getType().'.filters.*') ?? [];

        $teamFilters = auth()->user()->currentTeam->getOption($this->model->getType().'.filters.*') ?? [];

        // dd($userFilters, $teamFilters);

        return array_merge($userFilters->toArray(), $teamFilters->toArray());

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
        ]);

        if ($this->filters) {

            // Save for Team
            if($this->filter['global']) {
                auth()->user()->currentTeam->updateOption($this->model->getType().'.filters.'.$this->filter['name'], $this->filters);

            }
            // Save for User
            else {

                auth()->user()->updateOption($this->model->getType().'.filters.'.$this->filter['name'], $this->filters);

            }

        }

        $this->clearUserFiltersCache();

        $this->selectedFilter = $this->filter['name'];
        $this->notify('Filter saved successfully!');
        $this->showSaveFilterModal = false;
        $this->reset('filter');
    }

    /**
     * Set taxonomy filters.
     */
    public function setTaxonomyFilters()
    {
        $this->filters['taxonomy'] = $this->model->taxonomyFields()->pluck('model')->mapWithKeys(fn ($i) => [app($i)->getType() => []])->toArray();
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
        if($filter) {
            $this->filters = $this->userFilters[$filter];
        } else {
            $this->reset('filters');
        }

    }
}
