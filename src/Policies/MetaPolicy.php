<?php

namespace Aura\Base\Policies;

use Aura\Base\Models\Meta;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MetaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\Meta  $meta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Meta $meta)
    {
        //
    }

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }
}
