<?php

namespace Eminiarts\Aura\Traits;

trait HasActions
{
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

    public function singleAction($action)
    {
        $response = $this->model->{$action}();


        $this->notify('Successfully ran: '.$action);
    }
}
