<?php

namespace Aura\Base\Policies;

use App\Models\Post;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create($user, $resource)
    {
        if ($resource::$createEnabled === false) {
            return false;
        }

        if ($this->hasBlanketAccess($user)) {
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
     * @param  Post  $resource
     * @return Response|bool
     */
    public function delete($user, $resource)
    {
        if ($this->hasBlanketAccess($user)) {
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
     * @param  Post  $resource
     * @return Response|bool
     */
    public function forceDelete($user, $resource)
    {
        if ($this->hasBlanketAccess($user)) {
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
     * @param  Post  $resource
     * @return Response|bool
     */
    public function restore(User $user, $resource)
    {
        if ($this->hasBlanketAccess($user)) {
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
     * @param  Post  $resource
     * @return Response|bool
     */
    public function update($user, $resource)
    {
        if ($resource::$editEnabled === false) {
            return false;
        }

        if ($this->hasBlanketAccess($user)) {
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
     * @param  Post  $resource
     * @return Response|bool
     */
    public function view($user, $resource)
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
        if ($this->hasBlanketAccess($user)) {
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
     * @return Response|bool
     */
    public function viewAny($user, $resource)
    {
        if ($resource::$indexViewEnabled === false) {
            return false;
        }

        if ($this->hasBlanketAccess($user)) {
            return true;
        }

        if ($user->hasPermissionTo('viewAny', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Blanket access: a Super Admin (per-team) or a Global Admin (instance-wide,
     * including a Global Admin visiting a team where they hold no role) clears
     * every resource ability. The single gate every method funnels through.
     */
    protected function hasBlanketAccess($user): bool
    {
        return $user->isSuperAdmin() || $user->isAuraGlobalAdmin();
    }
}
