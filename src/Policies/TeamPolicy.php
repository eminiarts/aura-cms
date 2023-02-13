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
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function addTeamMember(User $user, Team $team)
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function delete(User $user, Team $team)
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can remove team members.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function removeTeamMember(User $user, Team $team)
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function update(User $user, Team $team)
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update team member permissions.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function updateTeamMember(User $user, Team $team)
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \Eminiarts\Aura\Resources\Team  $team
     * @return mixed
     */
    public function view(User $user, Team $team)
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }
}
