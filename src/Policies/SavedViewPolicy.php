<?php

namespace Aura\Base\Policies;

use Aura\Base\Models\SavedView;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\SavedViews\SavedViewVisibility;
use Illuminate\Support\Facades\Gate;

class SavedViewPolicy
{
    public function createPrivate(User $user, Resource $resource, ?Team $team): bool
    {
        return $this->hasResourceAccess($user, $resource) && $this->hasContextAccess($user, $team);
    }

    public function createShared(User $user, Resource $resource, ?Team $team): bool
    {
        return $this->createPrivate($user, $resource, $team) && $this->canManageSharedViews($user);
    }

    public function delete(User $user, SavedView $savedView, Resource $resource, ?Team $team): bool
    {
        return $this->update($user, $savedView, $resource, $team);
    }

    public function duplicate(User $user, SavedView $savedView, Resource $resource, ?Team $team): bool
    {
        return $this->view($user, $savedView, $resource, $team)
            && ($savedView->visibility === SavedViewVisibility::Private || $this->canManageSharedViews($user));
    }

    public function setDefault(User $user, SavedView $savedView, Resource $resource, ?Team $team): bool
    {
        return $this->update($user, $savedView, $resource, $team);
    }

    public function update(User $user, SavedView $savedView, Resource $resource, ?Team $team): bool
    {
        if (! $this->view($user, $savedView, $resource, $team)) {
            return false;
        }

        return $savedView->visibility === SavedViewVisibility::Private
            ? (string) $savedView->owner_id === (string) $user->getKey()
            : $this->canManageSharedViews($user);
    }

    public function view(User $user, SavedView $savedView, Resource $resource, ?Team $team): bool
    {
        if (! $this->hasResourceAccess($user, $resource)
            || ! $this->hasContextAccess($user, $team)
            || $savedView->resource_type !== $resource::class
            || ! $this->matchesTeam($savedView, $team)) {
            return false;
        }

        return $savedView->visibility === SavedViewVisibility::Team
            || (string) $savedView->owner_id === (string) $user->getKey();
    }

    private function canManageSharedViews(User $user): bool
    {
        return $user->isAuraGlobalAdmin()
            || $user->isSuperAdmin()
            || $user->hasPermission('manage-aura-saved-views');
    }

    private function hasContextAccess(User $user, ?Team $team): bool
    {
        if (! config('aura.teams')) {
            return $team === null;
        }

        if ($team === null
            || User::connectionCacheIdentity($user->getConnection()) !== User::connectionCacheIdentity($team->getConnection())) {
            return false;
        }

        $persistedTeam = $team->newQueryWithoutScopes()->whereKey($team->getKey())->first();

        if (! $persistedTeam instanceof Team
            || (string) $persistedTeam->getKey() !== (string) $team->getKey()) {
            return false;
        }

        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        return (string) $user->currentTeamIdForAuthorization() === (string) $team->getKey()
            && $user->teams()->whereKey($team->getKey())->exists();
    }

    private function hasResourceAccess(User $user, Resource $resource): bool
    {
        return User::connectionCacheIdentity($user->getConnection()) === User::connectionCacheIdentity($resource->getConnection())
            && Gate::forUser($user)->allows('viewAny', $resource);
    }

    private function matchesTeam(SavedView $savedView, ?Team $team): bool
    {
        return config('aura.teams')
            ? $team !== null && (string) $savedView->team_id === (string) $team->getKey()
            : $savedView->team_id === null;
    }
}
