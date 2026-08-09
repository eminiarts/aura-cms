<?php

namespace Aura\Base\Models;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role_id',
    ];

    protected $table = 'user_role';

    protected static function booted(): void
    {
        static::saved(fn (TeamUser $membership) => $membership->clearUserTeamsCache());
        static::deleted(fn (TeamUser $membership) => $membership->clearUserTeamsCache());
    }

    private function clearUserTeamsCache(): void
    {
        Role::bumpCatalogVersion($this->getConnection());

        $userId = $this->getAttribute('user_id');
        $teamId = $this->getAttribute('team_id');

        if (! config('aura.teams') || $userId === null || $teamId === null) {
            return;
        }

        User::clearTeamsCache($userId, $this->getConnection());
    }
}
