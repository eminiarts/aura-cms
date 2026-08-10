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

    protected function getDeleteQuery()
    {
        $query = parent::getDeleteQuery()
            ->where('user_id', $this->getOriginal('user_id', $this->getAttribute('user_id')))
            ->where('role_id', $this->getOriginal('role_id', $this->getAttribute('role_id')));

        if (array_key_exists('team_id', $this->getAttributes())) {
            $query->where('team_id', $this->getOriginal('team_id', $this->getAttribute('team_id')));
        }

        return $query;
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
