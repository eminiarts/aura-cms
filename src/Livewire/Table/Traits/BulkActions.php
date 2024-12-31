<?php

namespace Aura\Base\Livewire\Table\Traits;

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
        $this->selectedRowsQuery->each(function ($item, $key) use ($action) {
            if (str_starts_with($action, 'callFlow.')) {
                $item->callFlow(explode('.', $action)[1]);
            } elseif (str_starts_with($action, 'multiple')) {
                $posts = $this->selectedRowsQuery->get();
                $response = $item->{$action}($posts);

                // dd($response);
            } elseif (method_exists($item, $action)) {
                $item->{$action}();
            }
        });

        // Clear the selected array
        $this->selected = [];

        $this->notify('Erfolgreich: '.$action);
    }

    public function bulkCollectionAction($action)
    {
        // $action = $this->model->getBulkActions()[$action];
        $ids = $this->selectedRowsQuery->pluck('id')->toArray();

        $response = $this->model->{$action}($ids);

        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return $response;
        }

        // reset selected rows
        $this->selected = [];

        $this->notify('Erfolgreich: '.$action);

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
}
