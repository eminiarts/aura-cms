<?php

namespace Eminiarts\Aura\Policies;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, $post)
    {
        if ($user->resource->hasPermissionTo('create', $post)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Post $post)
    {
        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $post) && $user->resource->hasPermissionTo('delete', $post)) {
            if ($post->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('delete', $post)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Post $post)
    {
        if ($user->resource->hasPermissionTo('forceDelete', $post)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Post $post)
    {
        if ($user->resource->hasPermissionTo('restore', $post)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, $post)
    {
        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $post) && $user->resource->hasPermissionTo('update', $post)) {
            //dd('scope should be called', $post->user_id == $user->id, $post->user_id, $user->id);

            if ($post->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('update', $post)) {
            return true;
        }

        // dd('hier', $user->resource->hasPermissionTo('update', $post), $post);

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Post $post)
    {
        dd('view');
        // Scoped Posts
        if ($user->resource->hasPermissionTo('scope', $post) && $user->resource->hasPermissionTo('view', $post)) {
            if ($post->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        }

        if ($user->resource->hasPermissionTo('view', $post)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, $post)
    {
        dd('hier');
        if ($user->resource->hasPermissionTo('viewAny', $post)) {
            return true;
        }

        return false;
    }
}
