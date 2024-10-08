<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;

class Roles extends AdvancedSelect
{
    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

        return $this->relationship($model, $field)->get();
    }

    public function isRelation($field = null)
    {
        return true;
    }

    public function relationship($model, $field)
    {
        return $model->roles()->where('team_id', $model->current_team_id);
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $roleIds = $value;

        if (empty($roleIds)) {
            // Remove all roles for this user in the current team
            $post->roles()->wherePivot('team_id', $post->current_team_id)->detach();
            return;
        }

        // Get current roles for this user in the current team
        $currentRoleIds = $post->roles()
            ->wherePivot('team_id', $post->current_team_id)
            ->pluck('roles.id')
            ->toArray();

        // Roles to add
        $rolesToAdd = array_diff($roleIds, $currentRoleIds);

        // Roles to remove
        $rolesToRemove = array_diff($currentRoleIds, $roleIds);

        // Remove roles
        if (!empty($rolesToRemove)) {
            $post->roles()->wherePivot('team_id', $post->current_team_id)->detach($rolesToRemove);
        }

        // Add new roles
        foreach ($rolesToAdd as $roleId) {
            $post->roles()->attach($roleId, ['team_id' => $post->current_team_id]);
        }

        // Clear any relevant cache
        // For example:
        // Cache::forget('user.'.$post->id.'.roles');
    }
}
