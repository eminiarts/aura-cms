<?php

namespace Aura\Base\Policies;

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
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
        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user, $team)
    {
        if ($team::$createEnabled === false) {
            return false;
        }

        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Team $team)
    {
        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }

    public function inviteUsers(User $user, Team $team)
    {
        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('invite-users', $team)) {
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

        if ($user->isAuraGlobalAdmin()) {
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
        if ($user->isAuraGlobalAdmin()) {
            return true;
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

        if ($user->isAuraGlobalAdmin()) {
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
        // if ($user->isSuperAdmin()) {
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

        if ($user->isAuraGlobalAdmin()) {
            return true;
        }

        return $user->ownsTeam($team);
    }
}
