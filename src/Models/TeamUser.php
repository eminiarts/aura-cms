<?php

namespace Aura\Base\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $fillable = [
        'team_id',
        'user_id',
        'role_id',
    ];

    protected $table = 'user_role';
}
