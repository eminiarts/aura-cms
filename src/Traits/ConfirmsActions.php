<?php

namespace Eminiarts\Aura\Traits;

trait ConfirmsActions
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
}
