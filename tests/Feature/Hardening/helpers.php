<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Feature hardening helpers (issue #54)
|--------------------------------------------------------------------------
|
| Shared by the tests/Feature/Hardening/* files and pulled in with
| `require_once` from each of them. `require_once` + the function_exists guards
| keep the declarations idempotent (Pest also includes each test file), and the
| `hardening` prefix keeps these generic names from colliding with helpers in
| other suites (e.g. RoleResolutionTest's makeGlobalRole).
|
| This file is NOT a *Test.php file, so PHPUnit never collects it as a test; it
| only defines functions and is inert until required.
|
*/

if (! function_exists('hardeningAcceptUrl')) {
    /**
     * The signed "accept invitation" URL, matching the TeamInvitation mailable's
     * acceptUrl (same route, params and expiry).
     */
    function hardeningAcceptUrl($invitation, ?int $days = null): string
    {
        return URL::temporarySignedRoute(
            'aura.team-invitations.accept',
            now()->addDays($days ?? (int) config('aura.auth.invitation_expiry')),
            ['invitation' => $invitation],
        );
    }
}

if (! function_exists('hardeningRegisterUrl')) {
    /**
     * The signed "register through the invitation" URL, matching the mailable's
     * registerUrl.
     */
    function hardeningRegisterUrl($team, $invitation, ?int $days = null): string
    {
        return URL::temporarySignedRoute(
            'aura.invitation.register',
            now()->addDays($days ?? (int) config('aura.auth.invitation_expiry')),
            ['team' => $team, 'teamInvitation' => $invitation],
        );
    }
}

if (! function_exists('hardeningRegisterGuest')) {
    /**
     * Register a brand-new account through the real register route as a guest
     * (dropping any current session first) and assert the success redirect.
     */
    function hardeningRegisterGuest(string $name, string $team, string $email): void
    {
        auth()->logout();

        test()->post(route('aura.register'), [
            'name' => $name,
            'team' => $team,
            'email' => $email,
            'password' => 'Password123!XX',
            'password_confirmation' => 'Password123!XX',
        ])->assertRedirect(config('aura.auth.redirect'));
    }
}

if (! function_exists('hardeningAttachRole')) {
    /**
     * Assign a single role to the user through the Roles field pipeline and reload
     * the resolved-roles memo.
     */
    function hardeningAttachRole(User $user, Role $role): void
    {
        $user->update(['roles' => [$role->id]]);
        $user->refresh();
    }
}

if (! function_exists('hardeningMemberIn')) {
    /**
     * A fresh user whose only Membership ties them to the given role within the
     * given team.
     */
    function hardeningMemberIn(int $teamId, int $roleId): User
    {
        $user = User::factory()->create(['current_team_id' => $teamId]);
        $user->roles()->attach($roleId, ['team_id' => $teamId]);
        $user->refresh();

        return $user;
    }
}

if (! function_exists('hardeningGlobalRole')) {
    /**
     * A Global Role (team_id = null) written quietly so InitialPostFields' saving
     * hook never re-teams it to the acting user's current team. Bumps the catalog
     * version the same way the seeder/self-heal path does.
     */
    function hardeningGlobalRole(string $slug, array $permissions): Role
    {
        $role = Role::withoutGlobalScopes()->newModelInstance([
            'name' => ucfirst($slug), 'slug' => $slug, 'super_admin' => false,
            'permissions' => $permissions, 'team_id' => null,
        ]);
        $role->saveQuietly();
        Role::bumpCatalogVersion();

        return $role;
    }
}
