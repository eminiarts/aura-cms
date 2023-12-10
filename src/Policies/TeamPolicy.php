<?php

namespace Eminiarts\Aura\Policies;

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can add team members.
     *
     * @return mixed
     */
    public function addTeamMember(User $user, Team $team)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        // if ($resource::$createEnabled === false) {
        //     return false;
        // }

        // if ($user->resource->isSuperAdmin()) {
        //     return true;
        // }

        // todo: maybe do this as a setting

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Team $team)
    {
        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    public function inviteUsers(User $user, Team $team)
    {
        // ray('team policy', $user->resource->hasPermissionTo('invite-users', $team));

        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        if ($user->resource->hasPermissionTo('invite-users', $team)) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can remove team members.
     *
     * @return mixed
     */
    public function removeTeamMember(User $user, Team $team)
    {

        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Team $team)
    {
        if ($team::$editEnabled === false) {
            return false;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update team member permissions.
     *
     * @return mixed
     */
    public function updateTeamMember(User $user, Team $team)
    {

        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Team $team)
    {
        // if ($user->resource->isSuperAdmin()) {
        //     return true;
        // }

        // Check if the resource view is enabled
        if ($team::$viewEnabled === false) {
            return false;
        }


        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user, Team $team)
    {
        if ($team::$indexViewEnabled === false) {
            return false;
        }

        if ($user->resource->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }
}
