<?php

namespace Aura\Base\Fields;

use Aura\Base\Resources\Role;

class Roles extends AdvancedSelect
{
    public function display($field, $value, $model)
    {
        if (! $model->exists) {
            return '';
        }

        $roles = $this->relationship($model, $field)->get();

        if ($roles->isEmpty()) {
            return '';
        }

        return $roles->pluck('name')->implode(', ');
    }

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
        if (config('aura.teams')) {
            return $model->roles()->where('roles.team_id', $model->current_team_id);
        }

        return $model->roles();
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        // Normalize single value to array for consistent handling
        if (! is_array($value)) {
            $value = $value ? [$value] : [];
        }

        // Flatten nested arrays (e.g. [[1]] -> [1])
        $roleIds = collect($value)->flatten()->filter()->values()->all();

        $currentRoles = $post->roles();
        $assignableRoles = Role::query();

        if (config('aura.teams')) {
            $teamId = $post->current_team_id ?? optional(auth()->user())->current_team_id;
            $currentRoles->wherePivot('team_id', $teamId);
            $assignableRoles->where('team_id', $teamId);
        }

        $requestedRoles = $assignableRoles->whereKey($roleIds)->get();
        $existingRoles = $currentRoles->get();

        abort_unless($requestedRoles->count() === count(array_unique($roleIds)), 403);

        $currentRoleIds = $existingRoles->pluck('id')->all();
        $rolesToAdd = array_diff($roleIds, $currentRoleIds);
        $rolesToRemove = array_diff($currentRoleIds, $roleIds);

        $changesSuperAdminAccess = $requestedRoles
            ->whereIn('id', $rolesToAdd)
            ->contains('super_admin', true)
            || $existingRoles
                ->whereIn('id', $rolesToRemove)
                ->contains('super_admin', true);

        if ($changesSuperAdminAccess) {
            $actingUser = auth()->user();
            if ($actingUser && ! method_exists($actingUser, 'isSuperAdmin')) {
                $actingUser = app(config('aura.resources.user'))->find($actingUser->getAuthIdentifier());
            }

            abort_if($actingUser && (! method_exists($actingUser, 'isSuperAdmin') || ! $actingUser->isSuperAdmin()), 403);
        }

        if (empty($roleIds)) {
            // Remove all roles for this user in the current team
            if (config('aura.teams')) {
                $post->roles()->wherePivot('team_id', $post->current_team_id)->detach();
            } else {
                $post->roles()->detach();
            }

            return;
        }

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
                $currentTeamId = $post->current_team_id ?? optional(auth()->user())->current_team_id;

                $post->roles()->attach($roleId, ['team_id' => $currentTeamId]);
            } else {
                $post->roles()->attach($roleId);
            }
        }
    }
}
