<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Browser test helpers
|--------------------------------------------------------------------------
|
| These live in the Browser testsuite (not tests/Pest.php) and are pulled in
| with `require_once` from each browser test file. `require_once` makes the
| declarations idempotent even though Pest also autoloads this directory.
|
*/

if (! function_exists('browserSuperAdmin')) {
    /**
     * A logged-in team Super Admin with a KNOWN password, so the login form can
     * be driven with real credentials. Mirrors createSuperAdmin (attach-don't-mint
     * global `admin` role) and then pins a deterministic password.
     */
    function browserSuperAdmin(string $password = 'password', array $attributes = []): User
    {
        $user = createSuperAdmin();

        $user->forceFill(array_merge($attributes, [
            'password' => Hash::make($password),
        ]))->save();

        return $user->refresh();
    }
}

if (! function_exists('browserTeamRole')) {
    /**
     * A non-admin Team Role owned by the given team. It shows up in the invitation
     * modal's role picker (shadowResolvedForCurrentTeam) and is accepted by the
     * invitation flow (visibleToTeam).
     */
    function browserTeamRole(int $teamId, string $name = 'Editor', string $slug = 'editor'): Role
    {
        return Role::factory()->create([
            'team_id' => $teamId,
            'name' => $name,
            'slug' => $slug,
            'super_admin' => false,
        ]);
    }
}

if (! function_exists('browserMembershipExists')) {
    /**
     * True when a Membership pivot row ties the user to the role within the team.
     */
    function browserMembershipExists(int $userId, int $roleId, ?int $teamId): bool
    {
        return DB::table('user_role')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->where('team_id', $teamId)
            ->exists();
    }
}

if (! function_exists('browserGlobalAdmin')) {
    /**
     * A Global Admin who is also a team Super Admin with a KNOWN password. Built
     * on browserSuperAdmin (attach-don't-mint global `admin` role + own team) and
     * promoted with a trusted, pipeline-bypassing write (saveQuietly) — the same
     * posture the CLI bootstrap uses — so the flag is never granted through a
     * user-facing write path.
     */
    function browserGlobalAdmin(string $password = 'password', array $attributes = []): User
    {
        $user = browserSuperAdmin($password, $attributes);

        $user->forceFill(['global_admin' => true])->saveQuietly();

        return $user->refresh();
    }
}

if (! function_exists('browserPickSingleRole')) {
    /**
     * Drive the Roles field (AdvancedSelect, single-select) on a resource form:
     * open the listbox, search by the role's title, and click the sole matching
     * option. Scoped to the field's wrapper so it never collides with another
     * AdvancedSelect on the page.
     */
    function browserPickSingleRole($page, string $title, string $wrapperSlug = 'roles'): void
    {
        $scope = '#resource-field-'.$wrapperSlug.'-wrapper';

        // The user-form Roles picker is not searchable, so open the listbox and
        // click the option carrying the role's title (matched as a substring so
        // the "(#id)" suffix in Resource::title() does not need to be spelled out).
        $page->click($scope.' [x-ref="listboxButton"]')->wait(1);
        $page->click($scope.' [role="option"]:has-text("'.$title.'")')->wait(1);
    }
}

if (! function_exists('browserAttachMembership')) {
    /**
     * Attach the user to a team with a role via a raw Membership pivot row — the
     * server-side arrangement the Teams-tab journeys drive the UI against.
     */
    function browserAttachMembership(User $user, int $teamId, int $roleId): void
    {
        $user->roles()->attach($roleId, ['team_id' => $teamId]);
    }
}

if (! function_exists('browserInvitationRegisterUrl')) {
    /**
     * The signed "register through the invitation" URL — the same route, params
     * and expiry the TeamInvitation mailable builds. Generated with the browser
     * server's origin (LaravelHttpServer::bootstrap calls useOrigin), so its
     * signature validates against the live test server.
     */
    function browserInvitationRegisterUrl(Team $team, TeamInvitation $invitation): string
    {
        return URL::temporarySignedRoute(
            'aura.invitation.register',
            now()->addDays((int) config('aura.auth.invitation_expiry', 7)),
            ['team' => $team, 'teamInvitation' => $invitation],
        );
    }
}

if (! function_exists('browserInvitationAcceptUrl')) {
    /**
     * The signed "accept invitation" URL for an existing account, matching the
     * mailable's acceptUrl.
     */
    function browserInvitationAcceptUrl(TeamInvitation $invitation): string
    {
        return URL::temporarySignedRoute(
            'aura.team-invitations.accept',
            now()->addDays((int) config('aura.auth.invitation_expiry', 7)),
            ['invitation' => $invitation],
        );
    }
}

if (! function_exists('browserExtractMailLink')) {
    /**
     * Pull a real link out of a rendered mailable — proving the flow follows the
     * URL the email actually carries, not a helper's re-derivation of it. Returns
     * the first href whose (entity-decoded) value contains $needle.
     */
    function browserExtractMailLink(object $mailable, string $needle): string
    {
        preg_match_all('/href="([^"]+)"/i', $mailable->render(), $matches);

        foreach ($matches[1] as $href) {
            $decoded = html_entity_decode($href, ENT_QUOTES);

            if (str_contains($decoded, $needle)) {
                return $decoded;
            }
        }

        throw new RuntimeException('No link containing "'.$needle.'" was found in the rendered mail.');
    }
}

if (! function_exists('browserQuietTeam')) {
    /**
     * A team owned by a throwaway user, created quietly so no creator Membership
     * or per-team admin row is minted — a clean tenant to attach a target user
     * into.
     */
    function browserQuietTeam(string $name): Team
    {
        return Team::factory()->createQuietly([
            'name' => $name,
            'user_id' => User::factory()->create()->id,
        ]);
    }
}

if (! function_exists('browserGlobalRole')) {
    /**
     * A Global Role (team_id = null) written quietly so InitialPostFields' saving
     * hook never re-teams it to the acting user's current team. Bumps the catalog
     * version the same way the seeder/self-heal path does.
     */
    function browserGlobalRole(string $slug, string $name, array $attributes = []): Role
    {
        $role = Role::withoutGlobalScopes()->newModelInstance(array_merge([
            'name' => $name,
            'slug' => $slug,
            'super_admin' => false,
            'permissions' => [],
            'team_id' => null,
        ], $attributes));

        $role->saveQuietly();
        Role::bumpCatalogVersion();

        return $role->refresh();
    }
}
