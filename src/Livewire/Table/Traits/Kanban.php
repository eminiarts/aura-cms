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
        $this->prepareKanban();
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

    /**
     * Also called from Table::rowsQuery(): trait mount hooks fire in trait
     * declaration order, so mountKanban() can run before mountSwitchView()
     * has set $currentView — and switchView() never re-initializes statuses.
     */
    protected function prepareKanban()
    {
        if ($this->currentView != 'kanban') {
            return;
        }

        if (empty($this->kanbanStatuses)) {
            $this->initializeKanbanStatuses();
        }

        if (method_exists($this->model, 'kanbanPagination')) {
            $this->perPage = $this->model->kanbanPagination();
        }
    }

    protected function saveKanbanStatusesOrder()
    {
        auth()->user()->updateOption('kanban_statuses.'.$this->model()->getType(), $this->kanbanStatuses);
    }
}
