<?php

namespace Aura\Base\Traits;

use Illuminate\Auth\Access\AuthorizationException;
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

    public function singleAction($action)
    {
        // Authorize
        if (! $this->model->allowedToPerformActions()) {
            $this->authorize('update', $this->model);
        }

        // Get the action configuration
        $actions = $this->model->actions();
        if (isset($actions[$action]['conditional_logic']) && ! $actions[$action]['conditional_logic']()) {
            abort(403, 'You are not authorized to perform this action.');
        }

        try {
            $response = $this->model->{$action}();

            if ($response instanceof RedirectResponse) {
                return $response; // Perform the redirect.
            }

            $this->notify(__('Successfully ran: :action', ['action' => __($action)]));
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }
    }
}
