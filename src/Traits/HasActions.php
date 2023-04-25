<?php

namespace Eminiarts\Aura\Traits;

trait HasActions
{
    public function getActionsProperty()
    {
        return $this->model->getActions();
    }

    /**
     * Confirm the user's action.
     *
     * @return void
     */
    public function confirmAction($id)
    {
        $this->dispatchBrowserEvent('action-confirmed', [
            'id' => $id,
        ]);

    }

    public function singleAction($action)
    {
        $this->model->{$action}();

        $this->notify('Successfully ran: '.$action);
    }
}
