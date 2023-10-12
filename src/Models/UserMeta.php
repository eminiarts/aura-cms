<?php

namespace Eminiarts\Aura\Models;

use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Resources\Role;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Meta
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_meta';

    public function roles()
    {
        $roleIds = json_decode($this->value);

        return Role::whereIn('id', $roleIds)->get();
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TeamScope());

        static::saving(function ($post) {
            // ray(config('aura.teams'), $post->team_id, auth()->user(), $post);
            if (config('aura.teams') && ! $post->team_id && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }
        });
    }
}
