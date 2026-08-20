<?php

namespace Aura\Base\Livewire\Table\Traits;

use Aura\Base\Livewire\Table\TableMutationAuthorizer;
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
        $records = $this->tableMutationAuthorizer()->authorizeBulk(
            scope: clone $this->query(),
            action: $action,
            declared: (array) $this->getBulkActionsProperty(),
            selected: $this->selected,
            selectAll: (bool) $this->selectAll,
        );

        foreach ($records as $item) {
            if (str_starts_with($action, 'callFlow.')) {
                if (! method_exists($item, 'callFlow')) {
                    continue;
                }

                $item->callFlow(explode('.', $action)[1]);
            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        $this->notify('Success: '.$action);
    }

    public function bulkCollectionAction($action)
    {
        $records = $this->tableMutationAuthorizer()->authorizeBulk(
            scope: clone $this->query(),
            action: $action,
            declared: (array) $this->getBulkActionsProperty(),
            selected: $this->selected,
            selectAll: (bool) $this->selectAll,
        );

        $ids = $records->map(fn ($item) => $item->getKey())->all();

        $response = $this->model->{$action}($ids);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $this->selected = [];
        $this->selectAll = false;

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

    protected function tableMutationAuthorizer(): TableMutationAuthorizer
    {
        return app(TableMutationAuthorizer::class);
    }
}
