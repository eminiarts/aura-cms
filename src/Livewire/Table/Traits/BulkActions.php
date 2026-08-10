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
    public function bulkAction(
        string $action,
        TableMutationDispatcher $mutations,
        array $parameters = [],
    ): void {
        $model = $this->mutationModel();
        $trustedModel = new TableMutationModelDescriptor($model);
        $declaredActions = (array) $model->getBulkActions();

        $mutations->dispatchBulk(
            clone $this->bulkMutationQuery(),
            $trustedModel,
            $action,
            $declaredActions,
            $this->selected,
            (bool) $this->selectAll,
            'record',
            $this->selectAllExclusions,
            $parameters,
        );

        $this->resetSelectionForScopeChange();

        $this->notify('Success: '.$action);
    }

    public function bulkCollectionAction(
        string $action,
        TableMutationDispatcher $mutations,
        array $parameters = [],
    ): ?StreamedResponse {
        $model = $this->mutationModel();
        $trustedModel = new TableMutationModelDescriptor($model);
        $declaredActions = (array) $model->getBulkActions();

        $response = $mutations->dispatchBulk(
            clone $this->bulkMutationQuery(),
            $trustedModel,
            $action,
            $declaredActions,
            $this->selected,
            (bool) $this->selectAll,
            'collection',
            $this->selectAllExclusions,
            $parameters,
        );

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $this->resetSelectionForScopeChange();

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
