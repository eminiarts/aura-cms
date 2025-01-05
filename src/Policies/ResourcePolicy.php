<?php

namespace Aura\Base\Policies;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
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
        if ($resource::$createEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('create', $resource)) {
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
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('delete', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('delete', $resource)) {
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
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('forceDelete', $resource)) {
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
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->hasPermissionTo('restore', $resource)) {
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
        if ($resource::$editEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('update', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('update', $resource)) {
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
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Scoped Posts
        if ($user->hasPermissionTo('scope', $resource) && $user->hasPermissionTo('view', $resource)) {
            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->hasPermissionTo('view', $resource)) {
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
        if ($resource::$indexViewEnabled === false) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('viewAny', $resource)) {
            return true;
        }

        return false;
    }
}
