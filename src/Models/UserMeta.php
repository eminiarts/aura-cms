<?php

namespace Aura\Base\Models;

use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
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
        static::addGlobalScope(new TeamScope);

        static::saving(function ($post) {
            if (config('aura.teams') && ! $post->team_id && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }
        });
    }
}
