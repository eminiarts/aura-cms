<?php

namespace Aura\Base\Livewire\Table\Traits;

use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $this->ensureBulkActionAllowed($action);

        $ability = $this->bulkActionAbility($action);

        $this->selectedRowsQuery->each(function ($item, $key) use ($action, $ability) {
            // Authorize the action against each selected model before running it.
            Gate::authorize($ability, $item);

            if (str_starts_with($action, 'callFlow.')) {
                $item->callFlow(explode('.', $action)[1]);
            } elseif (str_starts_with($action, 'multiple')) {
                $posts = $this->selectedRowsQuery->get();
                $response = $item->{$action}($posts);

            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        // Clear the selected array
        $this->selected = [];

        $this->notify('Success: '.$action);
    }

    public function bulkCollectionAction($action)
    {
        $this->ensureBulkActionAllowed($action);

        $ability = $this->bulkActionAbility($action);

        // Authorize the action against every selected model before running it.
        $this->selectedRowsQuery->each(function ($item) use ($ability) {
            Gate::authorize($ability, $item);
        });

        $ids = $this->selectedRowsQuery->pluck('id')->toArray();

        $response = $this->model->{$action}($ids);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        // reset selected rows
        $this->selected = [];

        $this->notify('Success: '.$action);

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

    /**
     * Map a declared bulk action to the policy ability it requires.
     *
     * Destructive actions are matched by name so they are authorized with the
     * matching ability; anything else defaults to the mutating 'update' ability.
     */
    protected function bulkActionAbility(string $action): string
    {
        $normalized = strtolower($action);

        if (str_contains($normalized, 'forcedelete')) {
            return 'forceDelete';
        }

        if (str_contains($normalized, 'restore')) {
            return 'restore';
        }

        if (str_contains($normalized, 'delete') || str_contains($normalized, 'trash')) {
            return 'delete';
        }

        return 'update';
    }

    /**
     * Ensure the requested action is one the resource explicitly declares.
     *
     * This prevents a client from invoking arbitrary methods on the model by
     * passing an unlisted action string to bulkAction()/bulkCollectionAction().
     */
    protected function ensureBulkActionAllowed(string $action): void
    {
        $allowed = array_keys((array) $this->getBulkActionsProperty());

        if (! in_array($action, $allowed, true)) {
            abort(403, 'This bulk action is not allowed.');
        }
    }
}
