<?php

namespace App\Models;

use App\Aura\Resources\Role;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserMetaPivot extends Pivot
{
    protected $table = 'user_meta';

    public function roles()
    {
        dd('hier in pivot');
        $roleIds = json_decode($this->value);

        return Role::whereIn('id', $roleIds)->get();
    }
}
