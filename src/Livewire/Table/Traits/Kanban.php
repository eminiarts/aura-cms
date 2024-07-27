<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\On;

/**
 * Trait to handle sorting functionality.
 */
trait Kanban
{

    public $kanbanStatuses = [];

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

    protected function saveKanbanStatusesOrder()
    {
        auth()->user()->updateOption('kanban_statuses.'.$this->model()->getType(), $this->kanbanStatuses);
    }
}
