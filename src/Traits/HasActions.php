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

        // Get the action configuration. getActions() resolves both the actions()
        // method and the documented $actions property; actions() alone blows up
        // with a BadMethodCallException on property-only resources.
        $actions = (array) $this->model->getActions();

        // Only declared actions may be invoked. Without this, any public model
        // method (delete, forceDelete, ...) could be called through the `update`
        // authorization, bypassing its own policy. 404 mirrors an unknown route:
        // an undeclared action simply does not exist for this resource.
        abort_unless(array_key_exists($action, $actions), 404);

        if (isset($actions[$action]['conditional_logic']) && ! $actions[$action]['conditional_logic']()) {
            abort(403, 'You are not authorized to perform this action.');
        }

        try {
            $response = $this->model->{$action}();

            if ($response instanceof RedirectResponse) {
                return $response; // Perform the redirect.
            }

            $this->notify(__('Successfully ran: :action', ['action' => __($this->actionLabel($actions[$action], $action))]));
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }
    }

    /**
     * Human readable label of an action definition, which is either an array
     * with a 'label' key or a plain string. Falls back to the action key.
     */
    protected function actionLabel(mixed $definition, string $action): string
    {
        if (is_array($definition)) {
            return $definition['label'] ?? $action;
        }

        if (is_string($definition) && $definition !== '') {
            return $definition;
        }

        return $action;
    }
}
