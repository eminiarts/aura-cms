<?php

namespace Eminiarts\Aura\Policies;

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        if ($user->resource->hasPermissionTo('create', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $resource) && $user->resource->hasPermissionTo('delete', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('delete', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        if ($user->resource->hasPermissionTo('forceDelete', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }
        if ($user->resource->hasPermissionTo('restore', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $resource) && $user->resource->hasPermissionTo('update', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('update', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, $resource)
    {
        // Check if the config resource view is enabled
        if (config('aura.resource-view-enabled') === false) {
            return false;
        }

        // Check if the resource view is enabled
        if ($resource::$viewEnabled === false) {
            return false;
        }

        // Check if the user is a superadmin
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $resource) && $user->resource->hasPermissionTo('view', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('view', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, $resource)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        // ray('hier im view any', $user, $resource);
        if ($user->resource->hasPermissionTo('viewAny', $resource)) {
            return true;
        }

        return false;
    }
}
