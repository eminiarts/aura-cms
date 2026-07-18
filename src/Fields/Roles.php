<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\PreloadsTableDisplay;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Illuminate\Database\Eloquent\Collection;

class Roles extends AdvancedSelect implements PreloadsTableDisplay
{
    public function display($field, $value, $model)
    {
        if (! $model->exists) {
            return '';
        }

        $slug = $field['slug'] ?? null;

        // Reuse the batch-loaded, team-filtered roles primed by the table.
        if ($slug && $model instanceof Resource && $model->hasTableDisplayValue($slug)) {
            $roles = $model->getTableDisplayValue($slug);
        } else {
            $roles = $this->relationship($model, $field)->get();
        }

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

    public function preloadTableDisplay(Collection $rows, array $field): void
    {
        $slug = $field['slug'];
        $existing = $rows->filter(fn ($row) => $row->exists);

        $first = $existing->first();

        // The team constraint on the base roles() relationship depends on each
        // row's current_team_id, which is unavailable during generic Eloquent
        // eager loading. Load the unconstrained roles relation once for the
        // whole page, then filter per-row by team here.
        if (! $first || ! method_exists($first, 'roles')) {
            return;
        }

        $existing->loadMissing('roles');

        foreach ($existing as $row) {
            if (! $row instanceof Resource || ! $row->relationLoaded('roles')) {
                continue;
            }

            $roles = $row->getRelation('roles');

            if (config('aura.teams')) {
                // Filter by the Membership pivot's team_id so Global Roles (held
                // via a Membership but carrying team_id = null on the role row)
                // are kept for the row's current team.
                $currentTeamId = $row->getAttribute('current_team_id');
                $roles = $roles
                    ->filter(fn ($role) => optional($role->pivot)->team_id == $currentTeamId)
                    ->values();
            }

            $row->setTableDisplayValue($slug, $roles);
        }
    }

    public function relationship($model, $field)
    {
        if (config('aura.teams')) {
            // A user's roles in a team are resolved through the Membership pivot,
            // not the role row's team_id. Filtering on roles.team_id would drop
            // Global Roles (team_id = null) the user holds via a Membership, e.g.
            // the shared global admin role. Filter on the pivot's team_id instead.
            return $model->roles()->wherePivot('team_id', $model->current_team_id);
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

            // Assignable roles are the current team's own Team Roles plus the
            // shared Global Roles (team_id = null) from the catalog. Roles owned
            // by another team stay unassignable, so cross-team injection is still
            // refused. (The super_admin escalation guard below is unchanged.)
            $assignableRoles->where(function ($query) use ($teamId) {
                $query->where('team_id', $teamId)->orWhereNull('team_id');
            });
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

    public function tableEagerLoad(array $field): string|array|null
    {
        // Roles use a per-row team constraint that generic eager loading cannot
        // express; they are batched via preloadTableDisplay() instead.
        return null;
    }
}
