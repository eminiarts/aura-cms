<?php

namespace Eminiarts\Aura\Policies;

use Eminiarts\Aura\Models\Option;
use Eminiarts\Aura\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Option $option)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Option $option)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Option $option)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Option $option)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @param  \App\Models\Option  $option
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Option $option)
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Eminiarts\Aura\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }
}
