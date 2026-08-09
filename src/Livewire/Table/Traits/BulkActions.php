<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
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
        $model = $this->mutationModel();
        $trustedModel = new TableMutationModelDescriptor($model);
        $declaredActions = (array) $model->getBulkActions();

        $mutations->dispatchBulk(
            clone $this->mutationQuery(),
            $trustedModel,
            $action,
            $declaredActions,
            $this->selected,
            (bool) $this->selectAll,
            'record',
        );

        // Clear the selected array
        $this->selected = [];

        $this->notify('Success: '.$action);
    }

    public function bulkCollectionAction(string $action, TableMutationDispatcher $mutations): ?StreamedResponse
    {
        $model = $this->mutationModel();
        $trustedModel = new TableMutationModelDescriptor($model);
        $declaredActions = (array) $model->getBulkActions();

        $response = $mutations->dispatchBulk(
            clone $this->mutationQuery(),
            $trustedModel,
            $action,
            $declaredActions,
            $this->selected,
            (bool) $this->selectAll,
            'collection',
        );

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
}
