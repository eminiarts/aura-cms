<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Illuminate\Support\Str;
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
    public function bulkAction(string $action, TableMutationDispatcher $mutations): void
    {
        $this->ensureBulkActionAllowed($action);

        $ability = $this->resolveBulkActionAbility($action, $mutations);
        $selectedRows = $this->selectedRowsQuery->get();

        // Preflight every record so a later denial cannot leave a partial mutation.
        $selectedRows->each(function ($item) use ($ability, $mutations): void {
            $mutations->authorize($item, $ability);
        });

        $selectedRows->each(function ($item) use ($action, $selectedRows): void {
            if (str_starts_with($action, 'callFlow.')) {
                $item->callFlow(explode('.', $action)[1]);
            } elseif (str_starts_with($action, 'multiple')) {
                $item->{$action}($selectedRows);

            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        // Clear the selected array
        $this->selected = [];

        $this->notify('Success: '.$action);
    }

    public function bulkCollectionAction(string $action, TableMutationDispatcher $mutations): ?StreamedResponse
    {
        $this->ensureBulkActionAllowed($action);

        $ability = $this->resolveBulkActionAbility($action, $mutations);

        // Authorize the action against every selected model before running it.
        $this->selectedRowsQuery->each(function ($item) use ($ability, $mutations) {
            $mutations->authorize($item, $ability);
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

        return null;
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
     * Kept as an override seam for existing custom Table components.
     */
    protected function bulkActionAbility(string $action): string
    {
        $normalizedAction = Str::lower($action);

        if (str_contains($normalizedAction, 'forcedelete')) {
            return 'forceDelete';
        }

        if (str_contains($normalizedAction, 'restore')) {
            return 'restore';
        }

        if (str_contains($normalizedAction, 'delete') || str_contains($normalizedAction, 'trash')) {
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

    protected function resolveBulkActionAbility(string $action, TableMutationDispatcher $mutations): string
    {
        $definitions = (array) $this->getBulkActionsProperty();
        $definition = $definitions[$action] ?? null;

        if (is_array($definition) && array_key_exists('ability', $definition)) {
            return $mutations->abilityFor($action, $definition);
        }

        return $this->bulkActionAbility($action);
    }
}
