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
        // Get the action configuration. getActions() resolves both the actions()
        // method and the documented $actions property; actions() alone blows up
        // with a BadMethodCallException on property-only resources.
        $actions = (array) $this->model->getActions();

        // Package-contributed actions bring their own authorization instead of `update`.
        $contributed = array_key_exists($action, $actions) || ! app()->bound(ResourceActionRegistry::class)
            ? []
            : app(ResourceActionRegistry::class)->actionsFor($this->model, auth()->user());

        if (! array_key_exists($action, $contributed)) {
            // Authorize
            if (! $this->model->allowedToPerformActions()) {
                $this->authorize('update', $this->model);
            }

            // Only declared actions may be invoked. Without this, any public model
            // method (delete, forceDelete, ...) could be called through the `update`
            // authorization, bypassing its own policy. 404 mirrors an unknown route:
            // an undeclared action simply does not exist for this resource.
            abort_unless(array_key_exists($action, $actions), 404);

            if (isset($actions[$action]['conditional_logic']) && ! $actions[$action]['conditional_logic']()) {
                abort(403, 'You are not authorized to perform this action.');
            }
        }

        try {
            $response = array_key_exists($action, $contributed)
                ? app(ResourceActionRegistry::class)->execute($action, $this->model, auth()->user())
                : $this->model->{$action}();

            if ($response instanceof RedirectResponse) {
                return $response; // Perform the redirect.
            }

            $this->notify(__('Successfully ran: :action', ['action' => __($this->actionLabel($contributed[$action] ?? $actions[$action], $action))]));
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
