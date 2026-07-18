<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Traits\WithLivewireHelpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The Membership editor on the user View page's Teams tab.
 *
 * Displays every Membership of the viewed user — team + the user's role in that
 * team, resolved through the catalog seam (pivot role_id → slug →
 * Role::resolveForTeam, so a Shadow displays as the team's version) — and lets an
 * authorized admin attach the user to a team with a role from that team's merged
 * (shadow-resolved) set, change the per-team role, or detach a Membership.
 *
 * Authorization is enforced server-side per operation, always scoped to the
 * TARGET team, never the actor's implicit current team:
 *   - a Global Admin manages Memberships in any team;
 *   - a team Super Admin (super_admin via the role resolved for that team) only
 *     in the teams they administer;
 *   - everyone else is read-only (refused server-side, not merely hidden).
 * Assigning a role whose resolved definition is a Super Admin additionally
 * requires the actor to be a Super Admin of that team (or a Global Admin),
 * mirroring the Roles field's escalation guard.
 *
 * Teams-only: the whole component 404s in Teams-off mode (the Teams tab's
 * conditional logic already hides it there).
 */
class UserTeams extends Component
{
    use AuthorizesRequests;
    use WithLivewireHelpers;

    /** Attach form: the role (from the target team's merged set) to grant. */
    public $attachRoleId = '';

    /** Attach form: the team to attach the viewed user to. */
    public $attachTeamId = '';

    /**
     * Per-team role select state, keyed by team id — the id the row's select is
     * bound to so a change can be submitted with changeRole().
     *
     * @var array<int|string, int|string|null>
     */
    public array $roleSelections = [];

    /** The viewed user's primary key. */
    public $userId;

    public function attach(): void
    {
        abort_unless(config('aura.teams'), 404);

        $teamId = $this->attachTeamId !== '' ? (int) $this->attachTeamId : null;
        $roleId = $this->attachRoleId !== '' ? (int) $this->attachRoleId : null;

        $this->resetErrorBag();

        if (! $teamId) {
            $this->addError('attachTeamId', __('Please select a team.'));

            return;
        }

        if (! $roleId) {
            $this->addError('attachRoleId', __('Please select a role.'));

            return;
        }

        // Server-side authorization for the TARGET team (never the actor's
        // implicit current team): a Global Admin anywhere, else a Super Admin of
        // this team only.
        abort_unless($this->canManageTeam($teamId), 403);

        $user = $this->user();

        // One role per team: the pivot's unique(team_id, user_id) constraint.
        // Surface it as a validation error instead of a DB exception.
        if ($user->teams()->where('teams.id', $teamId)->exists()) {
            $this->addError('attachTeamId', __('The user is already a member of this team.'));

            return;
        }

        // The role must belong to the target team's assignable, shadow-resolved
        // set — its own Team Roles plus unshadowed Global Roles, each slug once.
        // A Global Role's hidden id for a slug the team shadows is refused here.
        $role = $this->assignableRole($teamId, $roleId);

        abort_if($role === null, 403);

        $this->guardSuperAdmin($teamId, $role);

        $user->roles()->attach($role->id, ['team_id' => $teamId]);

        $this->afterMembershipChange($user, $teamId);

        $this->reset('attachTeamId', 'attachRoleId');

        $this->notify(__('Membership added.'));
    }

    public function changeRole($teamId, $roleId = null): void
    {
        abort_unless(config('aura.teams'), 404);

        $teamId = (int) $teamId;
        $roleId = $roleId ?? ($this->roleSelections[$teamId] ?? null);
        $roleId = $roleId !== null && $roleId !== '' ? (int) $roleId : null;

        abort_unless($this->canManageTeam($teamId), 403);

        $user = $this->user();

        abort_unless($user->teams()->where('teams.id', $teamId)->exists(), 404);

        abort_if($roleId === null, 403);

        $role = $this->assignableRole($teamId, $roleId);

        abort_if($role === null, 403);

        // Guard the escalation both ways: the role being granted and the role
        // currently held (in case it was a Super Admin being removed).
        $this->guardSuperAdmin($teamId, $role);

        if ($current = $this->resolvedMembershipRole($teamId)) {
            $this->guardSuperAdmin($teamId, $current);
        }

        $user->roles()->wherePivot('team_id', $teamId)->detach();
        $user->roles()->attach($role->id, ['team_id' => $teamId]);

        $this->afterMembershipChange($user, $teamId);

        $this->notify(__('Role updated.'));
    }

    public function detach($teamId): void
    {
        abort_unless(config('aura.teams'), 404);

        $teamId = (int) $teamId;

        abort_unless($this->canManageTeam($teamId), 403);

        $user = $this->user();

        abort_unless($user->teams()->where('teams.id', $teamId)->exists(), 404);

        // Guard removing a Super Admin Membership the same way granting one is.
        if ($current = $this->resolvedMembershipRole($teamId)) {
            $this->guardSuperAdmin($teamId, $current);
        }

        $user->roles()->wherePivot('team_id', $teamId)->detach();

        // Current-team fallback: if we just detached the user's current team,
        // fall back to a remaining Membership (or null), mirroring the Team
        // deletion hook. Query fresh after the detach.
        if ((int) $user->getAttribute('current_team_id') === $teamId) {
            $firstTeam = $user->teams()->first();
            $user->forceFill(['current_team_id' => $firstTeam ? $firstTeam->getKey() : null])->save();
            User::clearCurrentTeamCache($user->getKey());
        }

        $this->afterMembershipChange($user, $teamId);

        unset($this->roleSelections[$teamId]);

        $this->notify(__('Membership removed.'));
    }

    /**
     * The assignable roles for the attach form of the currently-selected team:
     * the same merged, shadow-resolved set the pickers offer.
     *
     * @return array<int, string>
     */
    public function getAttachRoleOptionsProperty(): array
    {
        $teamId = $this->attachTeamId !== '' ? (int) $this->attachTeamId : null;

        if (! $teamId) {
            return [];
        }

        return $this->rolesForTeam($teamId);
    }

    /**
     * The Memberships rendered on the tab: one row per team the user belongs to,
     * with the role resolved through the catalog seam and a per-row read-only
     * flag reflecting whether the actor may manage that team.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMembershipsProperty(): array
    {
        $user = $this->user();

        return $user->teams()->get()->map(function ($team) {
            $teamId = (int) $team->getKey();
            $resolved = $this->resolvedMembershipRole($teamId);
            $canManage = $this->canManageTeam($teamId);

            if ($canManage && ! array_key_exists($teamId, $this->roleSelections)) {
                $this->roleSelections[$teamId] = $resolved?->getKey();
            }

            return [
                'team_id' => $teamId,
                'team_name' => $team->getAttribute('name'),
                'role_id' => $resolved?->getKey(),
                'role_name' => $resolved?->getAttribute('name'),
                'can_manage' => $canManage,
                'role_options' => $canManage ? $this->rolesForTeam($teamId) : [],
            ];
        })->all();
    }

    /**
     * The teams the actor may still attach the viewed user to: not yet a member,
     * and within the actor's authority (all teams for a Global Admin; only the
     * teams the actor is a Super Admin of otherwise).
     *
     * @return array<int, string>
     */
    public function getTeamOptionsProperty(): array
    {
        if (! config('aura.teams')) {
            return [];
        }

        $user = $this->user();
        $memberTeamIds = $user->teams()->pluck('teams.id')->map(fn ($id) => (int) $id)->all();

        $teams = app(config('aura.resources.team'))::withoutGlobalScopes()->get();

        return $teams
            ->reject(fn ($team) => in_array((int) $team->getKey(), $memberTeamIds, true))
            ->filter(fn ($team) => $this->canManageTeam((int) $team->getKey()))
            ->mapWithKeys(fn ($team) => [(int) $team->getKey() => $team->getAttribute('name')])
            ->all();
    }

    public function mount($userId): void
    {
        abort_unless(config('aura.teams'), 404);

        $this->userId = $userId;
    }

    public function render()
    {
        abort_unless(config('aura.teams'), 404);

        return view('aura::livewire.user.user-teams');
    }

    /** The authenticated actor as a first-class User model, or null. */
    protected function actor(): ?User
    {
        $actor = auth()->user();

        if (! $actor) {
            return null;
        }

        if ($actor instanceof User) {
            return $actor;
        }

        return User::withoutGlobalScopes()->find($actor->getAuthIdentifier());
    }

    /**
     * The role the actor holds in the given team, resolved through the catalog
     * seam (pivot role_id → slug → Role::resolveForTeam). Null when the actor has
     * no Membership in that team.
     */
    protected function actorRoleForTeam(User $actor, int $teamId): ?Role
    {
        $pivot = DB::table('user_role')
            ->where('user_id', $actor->getKey())
            ->where('team_id', $teamId)
            ->first();

        if (! $pivot) {
            return null;
        }

        $role = Role::withoutGlobalScopes()->find($pivot->role_id);

        if (! $role) {
            return null;
        }

        return Role::resolveForTeam($role->getAttribute('slug'), $teamId);
    }

    /**
     * Refresh the viewed user's cached team list after a Membership change so the
     * tab and the user's permission checks reflect the new state immediately.
     */
    protected function afterMembershipChange(User $user, int $teamId): void
    {
        Cache::forget('user.'.$user->id.'.teams');
        $user->unsetRelation('teams');
        $user->unsetRelation('roles');
    }

    /**
     * Resolve a submitted role id against the target team's assignable set —
     * its own Team Roles plus unshadowed Global Roles, each slug once. Returns
     * null when the id is not assignable in that team (e.g. a role owned by
     * another team, or a Global Role's hidden id for a slug this team shadows).
     */
    protected function assignableRole(int $teamId, int $roleId): ?Role
    {
        return Role::withoutGlobalScopes()
            ->shadowResolved($teamId)
            ->visibleToTeam($teamId)
            ->whereKey($roleId)
            ->first();
    }

    /**
     * Whether the actor may manage Memberships for the given team: a Global Admin
     * anywhere, else a Super Admin of that specific team (resolved through the
     * catalog seam, so a Shadow's super_admin flag wins). Never keyed off the
     * actor's implicit current team.
     */
    protected function canManageTeam(int $teamId): bool
    {
        $actor = $this->actor();

        if (! $actor) {
            return false;
        }

        if ($actor->isAuraGlobalAdmin()) {
            return true;
        }

        return (bool) optional($this->actorRoleForTeam($actor, $teamId))->super_admin;
    }

    /**
     * Refuse assigning/removing a Super Admin role unless the actor is a Super
     * Admin of that team (or a Global Admin) — mirrors Roles::saved()'s guard.
     */
    protected function guardSuperAdmin(int $teamId, Role $role): void
    {
        if (! $role->getAttribute('super_admin')) {
            return;
        }

        $actor = $this->actor();

        abort_if(
            ! $actor
            || (! $actor->isAuraGlobalAdmin() && ! optional($this->actorRoleForTeam($actor, $teamId))->super_admin),
            403
        );
    }

    /**
     * The viewed user's role in a team, resolved through the catalog seam so a
     * Shadow displays as the team's version.
     */
    protected function resolvedMembershipRole(int $teamId): ?Role
    {
        $pivot = DB::table('user_role')
            ->where('user_id', $this->userId)
            ->where('team_id', $teamId)
            ->first();

        if (! $pivot) {
            return null;
        }

        $role = Role::withoutGlobalScopes()->find($pivot->role_id);

        if (! $role) {
            return null;
        }

        return Role::resolveForTeam($role->getAttribute('slug'), $teamId);
    }

    /**
     * The target team's merged, shadow-resolved role set for the pickers: each
     * slug once, the team's Shadow winning over the Global Role it shadows.
     *
     * @return array<int, string>
     */
    protected function rolesForTeam(int $teamId): array
    {
        return Role::withoutGlobalScopes()
            ->shadowResolved($teamId)
            ->visibleToTeam($teamId)
            ->get()
            ->pluck('name', 'id')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    /** The viewed user, loaded unscoped so cross-team management works. */
    protected function user(): User
    {
        return User::withoutGlobalScopes()->findOrFail($this->userId);
    }
}
