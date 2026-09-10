# Teams

Teams add a team boundary to Aura data. Teams are enabled by default. A User may have one current Team, and a Membership records the role that User holds in each Team.

<a id="overview"></a>
## Overview

Teams affect four related parts of Aura:

- Resource queries use the current Team when the Resource has a `team_id` column.
- Users belong to Teams through the `user_role` Membership pivot.
- Roles are resolved from a shared Role Catalog plus any Team Roles that shadow it.
- Global Admin status is an instance-level flag separate from Membership and roles.

Teams-off mode is a supported installation mode. It removes the team schema and team UI while keeping the ordinary Resource, role, and permission features available.

![Teams index](/images/docs/teams/teams-index.png)

<a id="configuration"></a>
## Configure teams

Set the feature in the published `config/aura.php` file, or through the environment:

```php
'teams' => env('AURA_TEAMS', true),
```

```dotenv
AURA_TEAMS=true
```

Choose the value before the first migration. The migration creates a different schema for Teams-on and Teams-off mode. Changing the value on an existing installation requires a planned schema and data migration. Do not treat it as a runtime switch.

The current `main` installer applies `--teams=true` or `--teams=false` before it runs the migration. The public beta has a separate Teams-off installation sequence because its unified installer does not update the migration's configuration in the same PHP process. Follow [the installation guide](/docs/installation#without-teams) for that release-specific sequence.

The team-related authentication settings are in the `auth` block:

```php
'auth' => [
    'registration'      => env('AURA_REGISTRATION', true),
    'user_invitations'  => true,
    'invitation_expiry' => 7,
    'create_teams'      => env('AURA_CREATE_TEAMS', true),
],
```

`create_teams` is checked by `TeamPolicy::create()`. It must be true, and the actor must be a Global Admin, before the admin panel can create a Team. It does not grant team-creation rights to a Team Super Admin, and it does not block the first Team created by public registration.

`user_invitations` gates the new-user invitation registration endpoints. When it is false, those endpoints return 404. Existing users can still use the signed acceptance endpoint while Teams are enabled. Invitation links expire after `invitation_expiry` days. The default is seven days.

<a id="schema-differences"></a>

### Schema differences

The package migration creates these team columns when Teams are enabled:

| Area | Teams-on schema | Teams-off schema |
| --- | --- | --- |
| Users | `users.current_team_id` and `users.global_admin` | `users.global_admin` only |
| Teams | `teams` table | No `teams` table |
| Resources | `posts.team_id` | No `posts.team_id` |
| Roles and permissions | `team_id` on `roles` and `permissions` | No `team_id` columns |
| Options | `options.team_id` | No `team_id` column |
| Memberships | `user_role.team_id`, unique per `(team_id, user_id)` | No `team_id`, unique per `user_id` |

`Team` uses the dedicated `teams` table. `TeamInvitation` uses the normal shared `posts` table with the `teaminvitation` resource type. Aura does not create a separate `team_invitations` table.

Aura registers the Team and TeamInvitation Resources only when Teams are enabled. The `global_admin` column exists in both modes because Global Admin is an instance-level status, not a team role.

### Replace the built-in resources

The built-in classes are resolved from configuration and can be extended or replaced:

```php
'resources' => [
    'team'            => Aura\Base\Resources\Team::class,
    'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
],
```

<a id="team-management"></a>
## The Team resource

`Aura\Base\Resources\Team` is a custom-table Resource backed by `teams`. It uses meta fields, is excluded from global search, and uses soft deletes. Its built-in fields are:

- `Name`, which is required.
- `Description`.
- A Users tab for Membership-related user records.
- An Invitations tab for pending TeamInvitation Resources.

`user_id` stores the Team owner. The `users()` relationship is a `BelongsToMany` relationship through `user_role`, with the pivot `role_id`. It is not a `team_id` column on `users`.

```php
$team->users();           // Memberships through user_role
$team->roles();           // Team Roles owned by this Team
$team->teamInvitations(); // TeamInvitation Resources for this Team
$team->meta();            // Team meta rows
```

<a id="what-happens-when-a-team-is-created"></a>
## What happens when a Team is created

The Team model's `created` hook performs the initial setup. With an authenticated user, `Team::create()` does the following:

1. Fills `user_id` from the authenticated user when the value was omitted.
2. Sets the authenticated user's `current_team_id` to the new Team.
3. Calls `Role::firstOrCreateGlobalAdmin()` to obtain the shared `admin` Global Role. This role has `team_id = null` and `super_admin = true`. Team creation does not mint a per-Team admin role.
4. Adds one `user_role` row for the creator, carrying the shared role id and the new Team id. That row is the creator's Membership.
5. Clears the creator's Team-list cache and the Global Admin Team-switcher cache.
6. Dispatches `GenerateAllResourcePermissions` for the new Team.

```php
use Aura\Base\Resources\Team;

$team = Team::create([
    'name'    => 'Marketing',
    'user_id' => auth()->id(),
]);
```

The hook still creates the Team, shared Global Role, and permission job when no user is authenticated. It skips the current-team update and Membership attach because there is no actor to attach.

Public registration takes a separate path. When Teams are enabled, `RegisteredUserController` creates the user and first Team before login, then assigns the shared `admin` Global Role through the Roles field. This path does not check `auth.create_teams`.

<a id="switching-teams"></a>
## Switch Teams

`switchTeam()` accepts a Team object, updates `current_team_id`, and returns a boolean:

```php
if ($user->switchTeam($team)) {
    // Queries now use the visited Team as their context.
}
```

An ordinary user can switch only to a Team where they have a Membership. A Global Admin can switch to any existing Team, even without a Membership. The switch only changes `current_team_id`; it does not create a `user_role` row.

The HTTP entry point is `PUT /current-team`, named `aura.current-team.update`. It looks up the Team, calls `switchTeam()`, and returns 403 when an ordinary user is not a member. Switching is disabled in Teams-off mode and `switchTeam()` returns false there.

![Team switcher](/images/docs/teams/team-switcher.png)

```php
$user->currentTeam;          // The Team in current_team_id, or null
$user->current_team_id;      // int|null
$user->teams;                // Membership Teams
$user->getTeams();           // Cached Team list, or every Team for a Global Admin
$user->belongsToTeam($team); // Membership check
$user->isCurrentTeam($team); // Current-team check
$user->ownsTeam($team);      // Owner check using teams.user_id
```

The current Team id used by `TeamScope` is cached under `user_{id}_current_team_id`. Aura clears that key when `current_team_id` changes through the User model, including normal switching and Team deletion. If application code changes the database column directly, call `User::clearCurrentTeamCache($userId)` afterward.

<a id="memberships"></a>
## Memberships and the Role Catalog

A Membership is one `user_role` row containing `user_id`, `role_id`, and `team_id`. The Teams-on unique constraint allows at most one Membership per user and Team, so a user can have one role in Team A, another role in Team B, or no Membership in a Team. The Teams-off pivot has one flat role row per user.

The `role_id` in a Membership identifies a role by slug. `Role::resolveForTeam($slug, $teamId)` resolves that slug in the target Team:

- A Global Role is defined once with `team_id = null` and is available in every Team.
- A Team Role belongs to one Team.
- A Team Role with the same slug as a Global Role is a Shadow. It wins inside that Team only.
- Creating or deleting a Shadow changes the resolved role without rewriting Membership rows.

See [Roles and Permissions](/docs/roles-permissions) for the Role Catalog, permission slugs, and role editing rules.

### Global Admin and Team Super Admin

Global Admin status comes from the `users.global_admin` flag and the `AuraGlobalAdmin` gate. It is separate from Membership and from the `super_admin` flag on a Role.

A Team Super Admin has a role with `super_admin = true` resolved in the current Team. That role grants blanket access to Resource policies inside that Team. It says nothing about other Teams.

A Global Admin may also be a Team member. For example, a user who creates a Team receives a Membership there. Global Admin status does not create Memberships in every Team. When a Global Admin enters a Team where they have no Membership, the user is visiting:

```php
$globalAdmin->switchTeam($team); // true, with no new user_role row
```

During that visit, `isSuperAdmin()` can be false and `cachedRoles()` can be empty. Resource policies still grant the Global Admin access through the Global Admin gate. The Users index lists every user, including users with no Membership, and `getTeams()` lists every Team. `TeamPolicy::view()` also permits a Global Admin visitor when the Team view is enabled. It does not create a Membership.

<a id="managing-memberships"></a>
## Manage Memberships

The Teams tab on a User's View page renders `Aura\Base\Livewire\UserTeams` through the `Aura\Base\Fields\UserTeams` field. The tab and component exist only when Teams are enabled.

The editor lists each Membership with its Team and shadow-resolved role. An authorized actor can:

- Attach the User to a Team that does not already have a Membership for that User.
- Change the role carried by an existing Membership.
- Detach a Membership.

The component authorizes every mutation against the target Team:

| Actor | Membership editor access |
| --- | --- |
| Global Admin | Any Team |
| Team Super Admin | Teams where the actor resolves to `super_admin` |
| Everyone else | Read-only; mutations return 403 |

The submitted role must be in the target Team's shadow-resolved, assignable set. A role owned by another Team is rejected. Assigning or removing a `super_admin` role also requires the actor to be a Super Admin of that target Team or a Global Admin.

Detaching a User's current Team changes `current_team_id` to another remaining Membership, or to `null` when none remains.

<a id="team-scope"></a>
## TeamScope

`Aura\Base\Models\Scopes\TeamScope` is added to Aura Resources. It does not apply one identical condition to every model:

- Ordinary Resources use `where(<table>.team_id, currentTeamId)`.
- `User` queries return members of the current Team through `user_role`. A Global Admin bypasses this filter and can see every User. An ordinary authenticated user with no current Team sees only their own User row.
- `Team` is not filtered by TeamScope because Teams are the context records themselves.
- `Role` queries include the current Team's Team Roles and shared Global Roles. The Roles index and role pickers then apply slug-based Shadow resolution so each effective role appears once.
- `Option` is team-scoped when Teams are enabled.
- An authenticated user with no current Team gets a fail-closed `1 = 0` condition for ordinary team-scoped Resource queries.
- Teams-off mode makes TeamScope a no-op.

When an authenticated user creates a normal posts-table Resource, Aura fills `team_id` from the current Team. A normal query then stays inside that Team:

```php
$post = Post::create(['title' => 'Team note']);
$posts = Post::query()->get(); // Rows for the current Team
```

This assumes the Resource uses a schema with `team_id`. Team, User, and Role have their own query behavior described above. Custom Resources that participate in Teams must include the column expected by TeamScope.

<a id="bypassing-team-scope"></a>
## Query across Teams

Use Eloquent's standard scope removal only in trusted administrative or background code:

```php
use Aura\Base\Models\Scopes\TeamScope;

$allPosts = Post::withoutGlobalScope(TeamScope::class)->get();
$allRows = Post::withoutGlobalScopes()->get();
```

Dropping scopes also drops type, authorization, or other query restrictions. Add the exact filters the operation requires before returning cross-Team data.

<a id="authorization"></a>
## Team policies

`Aura\Base\Policies\TeamPolicy` governs the Team Resource. The static Resource flags are checked where shown:

| Ability | Current policy condition |
| --- | --- |
| `create` | `Team::$createEnabled` is true, `auth.create_teams` is true, and the actor is a Global Admin |
| `viewAny` | `Team::$indexViewEnabled` is true and the actor is a Global Admin or owns the Team |
| `view` | `Team::$viewEnabled` is true and the actor is a Global Admin or has a Membership in the Team |
| `update` | `Team::$editEnabled` is true and the actor is a Global Admin or owns the Team |
| `delete` | The actor is a Global Admin or owns the Team |
| `inviteUsers` | The actor is a Global Admin, owns the Team, or the actor's role in the target Team grants `invite-users-team` |
| `addTeamMember` | The actor is a Global Admin, owns the Team, or `isSuperAdmin()` is true in the target Team |
| `removeTeamMember` | The actor is a Global Admin or owns the Team |
| `updateTeamMember` | The actor is a Global Admin or owns the Team |

The `inviteUsers` and `addTeamMember` checks resolve the actor's grants in the target Team without changing the actor's current Team. The admin-panel Team delete action is conditionally shown only to Global Admins, even though the policy also allows the owner.

Resource actions use `ResourcePolicy`. A Team Super Admin or Global Admin passes the blanket Resource checks, subject to the Resource's own enabled flags. A Team Super Admin cannot write a Global Role in the shared Role Catalog. Only a Global Admin can make that change.

<a id="team-invitations"></a>
## Invite users

The `Aura\Base\Livewire\InviteUser` component invites a user to the authenticated user's current Team. It collects an email and a role from the current Team's shadow-resolved role set. Validation rejects an existing member, a duplicate pending invitation, a role owned by another Team, and a Super Admin role when the inviter is not allowed to grant it.

`Aura\Base\Resources\TeamInvitation` stores `email` and the selected role id in a `posts` row of type `teaminvitation`. Generic Resource creation is disabled. The Team relationship scopes the invitation to its Team.

The `Aura\Base\Mail\TeamInvitation` mailable uses the `aura::emails.team-invitation` view and creates temporary signed URLs. The email chooses the registration URL for a new address and the acceptance URL for an existing address.

| Route name | Method and path | Purpose |
| --- | --- | --- |
| `aura.invitation.register` | `GET /register/{team}/{teamInvitation}` | Show registration for a new address |
| `aura.invitation.register.post` | `POST /register/{team}/{teamInvitation}` | Create the invited User and Membership |
| `aura.team-invitations.accept` | `GET /team-invitations/{invitation}` | Existing authenticated User accepts |
| `aura.team-invitations.destroy` | `DELETE /teams/{team}/team-invitations/{invitation}` | Cancel a pending invitation |
| `aura.team-invitations.resend` | `POST /teams/{team}/team-invitations/{invitation}/resend` | Send it again |

The registration and acceptance links are signed and temporary. The cancel and resend routes require the `invite-users` Team ability.

### New-user registration

`InvitationRegisterUserController` checks `auth.user_invitations`, validates the name and password, and rechecks that the carried role is either a Global Role or a Team Role owned by the inviting Team. A role owned by another Team returns 404. It creates the User with the invitation email and invited `current_team_id`, assigns the role through the Roles field, and deletes the invitation in the same transaction.

An existing account with the invited email must use the acceptance URL. The registration path refuses a case variant of an existing email instead of creating a duplicate User.

### Existing-user acceptance

`TeamInvitationController::accept()` requires authentication and a valid signature. It compares the authenticated User's email with the invitation email case-insensitively. If the User is not already a member, it validates and attaches the carried role through `user_role` with the invitation's Team id. It then switches the User to that Team and consumes the invitation. Reusing a consumed or revoked invitation fails.

The accept controller accepts a shared Global Role or the inviting Team's own Team Role. It refuses a role owned by another Team. If the User already has a Membership in the inviting Team, acceptance does not create a duplicate row.

<a id="team-settings"></a>
## Team settings

Team settings use the `Option` Resource. Aura prefixes the option name with `team.{id}.` and stores the Team id in `options.team_id`:

```php
$team->getOption('settings');
$team->getOption('theme.*');
$team->updateOption('settings', ['dark_mode' => true]);
$team->deleteOption('settings');
$team->clearCachedOption('settings');
```

A trailing `*` returns a keyed collection of matching options. `getOption()` reads the database directly. `clearCachedOption()` only forgets a cache entry for code that cached the same key separately. The `Option` Resource itself is filtered by TeamScope when Teams are enabled.

<a id="deleting-teams"></a>
## Delete and restore Teams

Teams use `SoftDeletes`. When a Team is deleted, its `deleted` hook:

1. Moves users whose `current_team_id` points to the deleted Team to their first remaining Membership, or to `null`.
2. Deletes all `user_role` Membership rows for the Team.
3. Deletes the Team's Team Roles, including Shadows. Shared Global Roles remain.
4. Bumps the Role Catalog version so resolved-role caches are recomputed.
5. Calls deletion for Team meta, TeamInvitation rows, and options whose names start with `team.{id}.`.
6. Clears current-Team and Team-list caches for affected users and the Global Admin switcher cache.

```php
$team->delete();      // Soft delete and cleanup
$team->restore();     // Restore the Team row
$team->forceDelete(); // Permanently delete the Team row
```

Restoring the Team row does not restore the Memberships, Team Roles, invitations, meta rows, or options removed by the delete hook.

Cleanup targets the deleted Team explicitly, so invitations and Team options are removed even after affected users switch to a replacement Team.

<a id="api-reference"></a>
## API reference

| Class or method | Use |
| --- | --- |
| `Team::users()` | Read Membership Teams through `user_role` |
| `Team::roles()` | Read Team Roles owned by a Team |
| `Team::teamInvitations()` | Read invitations stored for a Team |
| `Team::getOption($key)` | Read a Team option or wildcard collection |
| `Team::updateOption($key, $value)` | Create or update a Team option |
| `Team::deleteOption($key)` | Delete a Team option |
| `User::switchTeam($team)` | Change current Team or visit one as Global Admin |
| `User::belongsToTeam($team)` | Check a Membership |
| `User::getTeams()` | Get cached Membership Teams or all Teams for a Global Admin |
| `User::isSuperAdmin()` | Check the resolved role in the current Team |
| `User::isAuraGlobalAdmin()` | Check the instance-level Global Admin gate |
| `Role::resolveForTeam($slug, $teamId)` | Resolve a Team Role Shadow or Global Role |
| `Role::shadowResolvedForCurrentTeam()` | Get the merged role set used by role pickers |

<a id="teams-off"></a>
## Teams-off mode

With `aura.teams` set to false before installation:

- Aura does not create the `teams` table or team-specific columns.
- The Team and TeamInvitation Resources are not registered, and the Teams tab, switcher, and invitation components are unavailable.
- `TeamScope` is a no-op and ordinary Resources are not filtered by a Team.
- Roles are one flat catalog. There is no Global Role, Team Role, Shadow, Membership team id, or `is_global` distinction.
- Public registration does not ask for a Team and assigns the seeded `user` role.
- `php artisan aura:user` assigns the seeded `admin` role.
- `global_admin` remains an independent user flag, but it does not provide Team visitation because no Teams exist.

Use the [Teams-off installation steps](/docs/installation#without-teams) when the public beta requires the split setup. Do not change the setting after the schema exists without a deliberate migration and data plan.

<a id="testing"></a>
## Test team behavior

The package test helpers create the usual authenticated Team context:

```php
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});
```

Useful focused coverage includes:

- `tests/Feature/Team/TeamTest.php` for fields, creation, Memberships, and cleanup.
- `tests/Feature/GlobalAdmin/GlobalAdminVisitationTest.php` and `tests/Feature/GlobalAdmin/GlobalAdminVisibilityTest.php` for visitation and cross-Team User visibility.
- `tests/Feature/Users/MembershipEditorTest.php` for target-Team Membership authorization.
- `tests/Feature/Hardening/InvitationLifecycleTest.php` and `tests/Feature/Team/InvitationGlobalRoleTest.php` for signed invitations, expiry, role validation, and reuse.
- `tests/Feature/Team/CurrentTeamCacheTest.php` and `tests/Feature/Security/FailClosedQueryFiltersAndTeamScopeTest.php` for TeamScope and cache behavior.
- `tests/Feature/Team/CreateTeamsConfigTest.php` for `auth.create_teams`.
- `tests/FeatureWithDatabaseMigrations/WithoutTeamsSchemaTest.php` and `tests/FeatureWithDatabaseMigrations/RoleAssignmentWithoutTeamsTest.php` for Teams-off schema and role assignment.

See [Testing](/docs/testing) for the package test setup.

## Related guides

- [Roles and Permissions](/docs/roles-permissions)
- [Installation](/docs/installation)
- [Configuration](/docs/configuration)
- [Testing](/docs/testing)
