<?php

namespace Aura\Base\Policies;

use App\Models\Post;
use Aura\Base\Contracts\ScopesMediaVisibility;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResourcePolicy implements ScopesMediaVisibility
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return Response|bool
     */
    public function create($user, $resource)
    {
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

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
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

        if ($this->deniesGlobalRoleWrite($user, $resource)) {
            return false;
        }

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
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

        if ($this->deniesGlobalRoleWrite($user, $resource)) {
            return false;
        }

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
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

        if ($this->deniesGlobalRoleWrite($user, $resource)) {
            return false;
        }

        if ($this->hasBlanketAccess($user)) {
            return true;
        }
        if ($user->hasPermissionTo('restore', $resource)) {
            return true;
        }

        return false;
    }

    /**
     * Refuse authorization when actor and resource clearly live on different
     * database connections. If either side is not an Eloquent model (or lacks
     * connection info), allow the rest of the policy to decide — do not break
     * non-model ability checks (e.g. class-level create/viewAny).
     */
    public function scopeMediaVisibility(Builder $query, Authenticatable $actor, Resource $resource): Builder
    {
        if (! $actor instanceof User
            || ! $this->usesSameConnection($actor, $resource)
            || config('aura.resource-view-enabled') === false
            || $resource::$viewEnabled === false) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->hasBlanketAccess($actor)) {
            return $query;
        }

        if ($actor->hasPermissionTo('scope', $resource) && $actor->hasPermissionTo('view', $resource)) {
            return $query->where($resource->getTable().'.user_id', $actor->getKey());
        }

        if ($actor->hasPermissionTo('view', $resource) || $actor->hasPermissionTo('viewAny', $resource)) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Post  $resource
     * @return Response|bool
     */
    public function update($user, $resource)
    {
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

        if ($resource::$editEnabled === false) {
            return false;
        }

        if ($this->deniesGlobalRoleWrite($user, $resource)) {
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
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

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
        if ($resource instanceof Model && ! $this->usesSameConnection($user, $resource)) {
            return false;
        }

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
     * Refuse a mutating write to a Global Role from a team context unless the
     * actor is a Global Admin. A Global Role (team_id = null) belongs to the
     * shared catalog: a team Super Admin — who otherwise clears every ability
     * via hasBlanketAccess — must not edit or delete it, or one team could
     * silently rewrite permissions for every other team. Checked BEFORE
     * hasBlanketAccess so a team Super Admin's blanket power does not leak here;
     * a Global Admin passes and is then cleared normally. A team may still
     * Shadow the global role (create its own Team Role of the same slug) — that
     * is a separate, allowed create. No-op in Teams-off mode (no catalog).
     */
    protected function deniesGlobalRoleWrite($user, $resource): bool
    {
        if (! config('aura.teams')) {
            return false;
        }

        if (! ($resource instanceof Role) || ! $resource->exists || $resource->getAttribute('team_id') !== null) {
            return false;
        }

        return ! $user->isAuraGlobalAdmin();
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

    private function usesSameConnection(mixed $user, mixed $resource): bool
    {
        if (! $user instanceof Model || ! $resource instanceof Model) {
            return true;
        }

        if (! method_exists($user, 'getConnectionName') || ! method_exists($resource, 'getConnectionName')) {
            return true;
        }

        $default = (string) config('database.default');
        $userConnection = $user->getConnectionName() ?: $default;
        $resourceConnection = $resource->getConnectionName() ?: $default;

        return $userConnection === $resourceConnection;
    }
}
