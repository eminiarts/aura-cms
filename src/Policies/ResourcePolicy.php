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
    public function delete(User $user, Resource $resource)
    {
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
    public function forceDelete(User $user, Resource $resource)
    {
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
    public function restore(User $user, Resource $resource)
    {
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
        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $resource) && $user->resource->hasPermissionTo('update', $resource)) {
            //dd('scope should be called', $resource->user_id == $user->id, $resource->user_id, $user->id);

            if ($resource->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('update', $resource)) {
            return true;
        }

        // dd('hier', $user->resource->hasPermissionTo('update', $resource), $resource);

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Post  $resource
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Resource $resource)
    {
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
        if ($user->resource->hasPermissionTo('viewAny', $resource)) {
            return true;
        }

        return false;
    }
}