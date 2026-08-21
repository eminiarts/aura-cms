<?php

namespace Aura\Base\Traits;

use Aura\Base\Contracts\ResourceActionRegistry;
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
        $actor = auth()->user();
        $legacyActions = $this->model->getActions() ?? [];

        if (! $this->model->allowedToPerformActions() && ! $actor?->can('update', $this->model)) {
            $legacyActions = [];
        }

        $contributedActions = app()->bound(ResourceActionRegistry::class)
            ? app(ResourceActionRegistry::class)->actionsFor($this->model, $actor)
            : [];

        // Resource-defined actions retain precedence over package-contributed
        // actions when a legacy key happens to use the same name.
        $actions = array_replace($contributedActions, $legacyActions);

        return collect($actions)->filter(function ($item) {
            if (isset($item['conditional_logic'])) {
                return $item['conditional_logic']();
            }

            return true;
        })->all();
    }

    public function singleAction($action)
    {
        $actor = auth()->user();
        $legacyActions = $this->model->getActions() ?? [];

        if (! array_key_exists($action, $legacyActions) && app()->bound(ResourceActionRegistry::class)) {
            try {
                $response = app(ResourceActionRegistry::class)->execute($action, $this->model, $actor);

                if ($response instanceof RedirectResponse) {
                    return $response;
                }

                $this->notify(__('Successfully ran: :action', ['action' => __($action)]));

                return $response;
            } catch (AuthorizationException $e) {
                abort(403, $e->getMessage());
            }
        }

        // Authorize
        if (! $this->model->allowedToPerformActions()) {
            $this->authorize('update', $this->model);
        }

        // Get the action configuration
        $actions = $legacyActions;
        abort_unless(array_key_exists($action, $actions), 404);
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
