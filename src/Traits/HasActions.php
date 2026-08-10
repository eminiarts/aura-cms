<?php

namespace Aura\Base\Traits;

use Aura\Base\Contracts\TableResource;
use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

trait HasActions
{
    /**
     * Confirm the user's action.
     *
     * @return void
     */
    public function confirmAction($id)
    {
        $this->dispatch('action-confirmed', id: $id);

    }

    public function getActionsProperty()
    {
        $actions = $this->model->getActions();

        return collect($actions)->filter(function ($item) {
            if (isset($item['conditional_logic'])) {
                return $item['conditional_logic']();
            }

            return true;
        })->all();
    }

    public function singleAction($action, TableMutationDispatcher $mutations)
    {
        if (! is_string($action) || $action === '') {
            abort(403, 'This resource action is not allowed.');
        }

        $model = $this->model;

        if (! $model instanceof Model || ! $model instanceof TableResource || $model->getKey() === null) {
            abort(422, 'Resource actions require a persisted Aura resource.');
        }

        $response = $mutations->dispatchAction(
            $model->newQuery(),
            new TableMutationModelDescriptor($model),
            $model->getKey(),
            $action,
            (array) $model->getActions(),
        );

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if ($response !== false) {
            $this->notify(__('Successfully ran: :action', ['action' => __($action)]));
        }

        return $response;
    }
}
