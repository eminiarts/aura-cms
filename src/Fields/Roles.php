<?php

namespace Aura\Base\Fields;

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

    // public function get($class, $value, $field = null)
    // {
    //      ray('get roles........', $class, $value, $field)->blue();

    //      return $value;
    // }

    public function relationship($model, $field)
    {
        if (config('aura.teams')) {
            return $model->roles()->where('team_id', $model->current_team_id);
        }

        return $model->roles();
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $roleIds = $value;

        if (empty($roleIds)) {
            // Remove all roles for this user in the current team
            if (config('aura.teams')) {
                $post->roles()->wherePivot('team_id', $post->current_team_id)->detach();
            } else {
                $post->roles()->detach();
            }

            return;
        }

        // Get current roles for this user in the current team
        if (config('aura.teams')) {
            $currentRoleIds = $post->roles()
                ->wherePivot('team_id', $post->current_team_id)
                ->pluck('roles.id')
                ->toArray();
        } else {
            $currentRoleIds = $post->roles()
                ->pluck('roles.id')
                ->toArray();
        }

        // Roles to add
        $rolesToAdd = array_diff($roleIds, $currentRoleIds);

        // Roles to remove
        $rolesToRemove = array_diff($currentRoleIds, $roleIds);

        // Remove roles
        if (! empty($rolesToRemove)) {
            if (config('aura.teams')) {
                $post->roles()->wherePivot('team_id', $post->current_team_id)->detach($rolesToRemove);
            } else {
                $post->roles()->detach($rolesToRemove);
            }
        }

        // Add new roles
        foreach ($rolesToAdd as $roleId) {
            if (config('aura.teams')) {
                $post->roles()->attach($roleId, ['team_id' => $post->current_team_id]);
            } else {
                $post->roles()->attach($roleId);
            }
        }

        // Clear any relevant cache
        // For example:
        // Cache::forget('user.'.$post->id.'.roles');
    }
}
