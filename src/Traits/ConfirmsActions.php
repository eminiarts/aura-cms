<?php

namespace Eminiarts\Aura\Traits;

trait ConfirmsActions
{
    /**
     * The ID of the operation being confirmed.
     *
     * @var string|null
     */
    public $confirmableId = null;

    /**
     * Indicates if the user's action is being confirmed.
     *
     * @var bool
     */
    public $confirmingAction = false;

    /**
     * Confirm the user's action.
     *
     * @return void
     */
    public function confirmAction()
    {
        $this->dispatchBrowserEvent('action-confirmed', [
            'id' => $this->confirmableId,
        ]);

        $this->stopConfirmingAction();
    }

    /**
     * Start confirming the user's action.
     *
     * @return void
     */
    public function startConfirmingAction(string $confirmableId)
    {
        $this->confirmingAction = true;
        $this->confirmableId = $confirmableId;

        $this->dispatchBrowserEvent('confirming-action');
    }

    /**
     * Stop confirming the user's action.
     *
     * @return void
     */
    public function stopConfirmingAction()
    {
        $this->confirmingAction = false;
        $this->confirmableId = null;
        $this->confirmableAction = '';
    }
}
