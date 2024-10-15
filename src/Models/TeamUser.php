<?php

namespace Aura\Base\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUser extends Pivot
{
    protected $table = 'user_role';

    protected $fillable = [
        'team_id',
        'user_id',
        'role_id',
    ];
}
