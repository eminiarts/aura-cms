# Teams

Teams keep each team's data separate and are enabled by default. A user works in one current team at a time. Their membership determines the role they hold in each team.

<a id="overview"></a>
## Overview

Teams affect four related parts of Aura:

- Resource queries return data for the current team when the resource has a `team_id` column.
- Memberships connect users to teams through the `user_role` pivot table.
- Teams share a role catalog, but each team can override shared roles with its own.
- Global admin status applies across the installation and is separate from memberships and roles.

You can also install Aura without teams. This mode omits the team schema and interface while keeping resources, roles, and permissions available.

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

Choose whether to enable teams before the first migration. The setting determines which tables and columns Aura creates. Changing it on an existing installation requires a planned schema and data migration. It is not a runtime switch.

The installer on the current `main` branch applies `--teams=true` or `--teams=false` before running the migration. The public beta requires a separate installation sequence to disable teams. Its unified installer does not make the updated setting available to the migration in the same PHP process. Follow [the installation guide](/docs/installation#without-teams) for that release-specific sequence.

The team-related authentication settings are in the `auth` block:

```php
'auth' => [
    'registration'      => env('AURA_REGISTRATION', true),
    'user_invitations'  => true,
    'invitation_expiry' => 7,
    'create_teams'      => env('AURA_CREATE_TEAMS', true),
],
```

Creating a team in the admin panel requires both global admin status and `create_teams` set to true. The team policy enforces these requirements. Enabling the setting does not give team super admins permission to create teams. Disabling it does not prevent public registration from creating a user's first team.

Set `user_invitations` to false to disable registration through invitations. Those endpoints then return 404. Existing users can still accept a signed invitation while teams are enabled. Invitation links expire after seven days by default. Change `invitation_expiry` to use a different number of days.

<a id="schema-differences"></a>

### Schema differences

The package migration creates these team columns when teams are enabled:

| Area | Teams-on schema | Teams-off schema |
| --- | --- | --- |
| Users | `users.current_team_id` and `users.global_admin` | `users.global_admin` only |
| Teams | `teams` table | No `teams` table |
| Resources | `posts.team_id` | No `posts.team_id` |
| Roles and permissions | `team_id` on `roles` and `permissions` | No `team_id` columns |
| Options | `options.team_id` | No `team_id` column |
| Memberships | `user_role.team_id`, unique per `(team_id, user_id)` | No `team_id`, unique per `user_id` |

Teams have a dedicated database table. Invitations use the shared `posts` table with the `teaminvitation` resource type. Aura does not create a separate `team_invitations` table.

Aura registers the team and invitation resources only when teams are enabled. The `global_admin` column exists in both modes because this status applies across the installation.

### Replace the built-in resources

The built-in classes are resolved from configuration and can be extended or replaced:

```php
'resources' => [
    'team'            => Aura\Base\Resources\Team::class,
    'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
],
```

<a id="team-management"></a>
## The team resource

The built-in team resource, `Aura\Base\Resources\Team`, stores records in the `teams` table and supports meta fields. It uses soft deletes and is excluded from global search. It provides these fields and tabs:

- A required Name field.
- A Description field.
- A Users tab listing members.
- An Invitations tab listing pending invitations.

The team's `user_id` column identifies its owner. Members are available through the `users()` many-to-many relationship. The relationship uses the `user_role` pivot table, which also stores each member's role in `role_id`. Membership does not use a team column on the users table.

```php
$team->users();           // Memberships through user_role
$team->roles();           // Team Roles owned by this Team
$team->teamInvitations(); // TeamInvitation Resources for this Team
$team->meta();            // Team meta rows
```

<a id="what-happens-when-a-team-is-created"></a>
## What happens when a team is created

Creating a team also sets up its permissions and the creator's membership. When a user is authenticated, `Team::create()` runs a model hook that:

1. Uses the authenticated user as the owner when no `user_id` was supplied.
2. Makes the new team the authenticated user's current team.
3. Finds or creates the shared `admin` role through `Role::firstOrCreateGlobalAdmin()`. This role has `team_id = null` and `super_admin = true`. Aura does not create a separate admin role for each team.
4. Creates the creator's membership in `user_role`, using the shared role and the new team.
5. Clears the creator's team-list cache and the global admin team-switcher cache.
6. Dispatches `GenerateAllResourcePermissions` for the new team.

```php
use Aura\Base\Resources\Team;

$team = Team::create([
    'name'    => 'Marketing',
    'user_id' => auth()->id(),
]);
```

Without an authenticated user, Aura still creates the team, finds or creates the shared global role, and dispatches the permission job. It skips membership creation and the current-team update.

Public registration creates the user and their first team before login, then assigns the shared `admin` role through the Roles field. This happens when teams are enabled, regardless of the `auth.create_teams` setting.

<a id="switching-teams"></a>
## Switch teams

Pass a team object to `switchTeam()` to change the user's current team. The method updates `current_team_id` and returns whether the switch succeeded:

```php
if ($user->switchTeam($team)) {
    // Queries now use the visited Team as their context.
}
```

An ordinary user can switch only to a team they belong to. A global admin can visit any existing team without becoming a member. Switching changes the current team but does not create a membership.

The `aura.current-team.update` route accepts `PUT /current-team`. It looks up the team and calls the switching method, returning 403 when an ordinary user is not a member. When teams are disabled, switching is unavailable and `switchTeam()` returns false.

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

Aura caches the current team ID for scoped queries under `user_{id}_current_team_id`. Updating the current team through the user model clears this cache, including during normal switching and team deletion. If you update the database column directly, call `User::clearCurrentTeamCache($userId)` afterward.

<a id="memberships"></a>
## Memberships and the role catalog

A membership connects a user, a role, and a team in one row of the `user_role` pivot table. A database constraint allows at most one membership per user in each team. Users can therefore hold different roles in different teams. With teams disabled, the pivot allows one role row per user.

The role referenced by a membership has a slug. Aura uses that slug to find the effective role for the target team through `Role::resolveForTeam($slug, $teamId)`:

- A global role is shared by every team and has `team_id = null`.
- A team role belongs to one team.
- A team role with the same slug as a global role overrides it within that team. This override is called a shadow.
- Creating or deleting a shadow changes the effective role without changing membership rows.

See [Roles and Permissions](/docs/roles-permissions) for the role catalog, permission slugs, and role editing rules.

### Global admin and team super admin

Global admin status comes from the `users.global_admin` flag and the `AuraGlobalAdmin` gate. It is independent of team membership and the super admin flag on a role.

A team super admin has an effective role with `super_admin = true` in the current team. That role grants blanket access through resource policies within that team. It grants no access to other teams.

Global admins can also be team members. Creating a team gives its creator a membership there, but global admin status does not create memberships in every team. Entering a team without a membership is a visit:

```php
$globalAdmin->switchTeam($team); // true, with no new user_role row
```

During a visit, `isSuperAdmin()` can return false and `cachedRoles()` can be empty. Resource policies still grant access through the global admin gate. Global admins see all users in the Users index, including those without memberships, and `getTeams()` returns every team. They can also view the visited team when its resource view is enabled. None of these actions creates a membership.

<a id="managing-memberships"></a>
## Manage memberships

The Teams tab on a user's View page lets administrators manage memberships. It uses the `Aura\Base\Livewire\UserTeams` component through the matching `Aura\Base\Fields\UserTeams` field. The tab and component are available only when teams are enabled.

The editor lists each membership with its team and effective role, including any team override. An authorized user can:

- Add the user to a team they do not already belong to.
- Change the role of an existing membership.
- Remove a membership.

The editor checks permission for each change against the target team:

| Actor | Membership editor access |
| --- | --- |
| Global admin | Any team |
| Team super admin | Teams where the acting user has an effective super admin role |
| Everyone else | Read-only; mutations return 403 |

You can assign only roles available for assignment in the target team, after team overrides have been applied. The editor rejects roles owned by another team. Assigning or removing a super admin role also requires global admin status or super admin status in that target team.

Removing a user from their current team switches them to another team they belong to. If no membership remains, their current team becomes `null`.

<a id="team-scope"></a>
## TeamScope

Aura applies `Aura\Base\Models\Scopes\TeamScope` to its resources to limit queries to the current team. The filter depends on the model:

- Ordinary resources filter their `team_id` column by the current team ID. An authenticated user with no current team receives no results, enforced by a `1 = 0` condition.
- User queries return members of the current team through the membership table. Global admins can see every user. An ordinary authenticated user with no current team sees only their own record.
- Teams are not filtered by this scope because they provide the team context.
- Role queries include shared roles and roles owned by the current team. The Roles index and role pickers apply team overrides by slug so each effective role appears once.
- Options are scoped to the current team when teams are enabled.
- With teams disabled, the scope does not filter queries.

When an authenticated user creates a resource in the shared posts table, Aura fills `team_id` with the current team. Subsequent queries return that team's records:

```php
$post = Post::create(['title' => 'Team note']);
$posts = Post::query()->get(); // Rows for the current Team
```

This requires a `team_id` column in the resource's table, including for custom resources that use teams. Teams, users, and roles follow the special query behavior described above.

<a id="bypassing-team-scope"></a>
## Query across teams

Use Eloquent's standard scope removal only in trusted administrative or background code:

```php
use Aura\Base\Models\Scopes\TeamScope;

$allPosts = Post::withoutGlobalScope(TeamScope::class)->get();
$allRows = Post::withoutGlobalScopes()->get();
```

Removing scopes can also remove type, authorization, or other query restrictions. Add the filters your operation requires before returning data across teams.

<a id="authorization"></a>
## Team policies

The team resource uses `Aura\Base\Policies\TeamPolicy`. Its permissions and resource flags work as follows:

| Ability | Current policy condition |
| --- | --- |
| `create` | `Team::$createEnabled` and `auth.create_teams` are true, and the acting user is a global admin |
| `viewAny` | `Team::$indexViewEnabled` is true and the acting user is a global admin or owns the team |
| `view` | `Team::$viewEnabled` is true and the acting user is a global admin or belongs to the team |
| `update` | `Team::$editEnabled` is true and the acting user is a global admin or owns the team |
| `delete` | The acting user is a global admin or owns the team |
| `inviteUsers` | The acting user is a global admin, owns the team, or has a role in the target team that grants `invite-users-team` |
| `addTeamMember` | The acting user is a global admin, owns the team, or passes `isSuperAdmin()` in the target team |
| `removeTeamMember` | The acting user is a global admin or owns the team |
| `updateTeamMember` | The acting user is a global admin or owns the team |

The checks for inviting users and adding members resolve the acting user's permissions in the target team without switching their current team. The admin panel shows the team delete action only to global admins, even though the policy also allows the owner to delete it.

Resource actions use `ResourcePolicy`. Team super admins and global admins pass its blanket permission checks, subject to the resource's own enabled flags. Only global admins can change shared global roles. Team super admin status does not grant that permission.

<a id="team-invitations"></a>
## Invite users

The invitation form invites users to your current team. It asks for an email address and a role available in that team, including any team overrides. Validation rejects existing members, duplicate pending invitations, and roles owned by another team. You can invite someone as a super admin only if you have permission to grant that role. The form uses the `Aura\Base\Livewire\InviteUser` component.

The `Aura\Base\Resources\TeamInvitation` resource stores the email and selected role ID in the shared posts table under the invitation resource type. Its team relationship limits invitations to their team. Generic resource creation is disabled.

Invitation emails contain temporary signed links. New users receive a registration link, while existing users receive an acceptance link. To customize the email, use the `aura::emails.team-invitation` view rendered by `Aura\Base\Mail\TeamInvitation`.

| Route name | Method and path | Purpose |
| --- | --- | --- |
| `aura.invitation.register` | `GET /register/{team}/{teamInvitation}` | Show registration for a new address |
| `aura.invitation.register.post` | `POST /register/{team}/{teamInvitation}` | Create the invited user and membership |
| `aura.team-invitations.accept` | `GET /team-invitations/{invitation}` | Let an existing authenticated user accept |
| `aura.team-invitations.destroy` | `DELETE /teams/{team}/team-invitations/{invitation}` | Cancel a pending invitation |
| `aura.team-invitations.resend` | `POST /teams/{team}/team-invitations/{invitation}/resend` | Send it again |

The registration and acceptance links are signed and temporary. The cancel and resend routes require the `invite-users` team ability.

### New-user registration

Invitation registration checks that `auth.user_invitations` is enabled and validates the user's name and password. It also verifies that the invited role is shared or owned by the inviting team. A role owned by another team returns 404.

In one transaction, registration creates the user with the invited email, makes the inviting team their current team, assigns the role through the Roles field, and deletes the invitation.

Existing users must use the acceptance link. Registration rejects an email that matches an existing account, including a match with different capitalization.

### Existing-user acceptance

Accepting an invitation requires a signed-in user and a valid signature. The account email must match the invited email, ignoring capitalization. If the user is not already a member, Aura validates the invited role and creates their membership in the inviting team. It then switches the user to that team and consumes the invitation. Consumed or revoked invitations cannot be reused.

Acceptance allows shared roles and roles owned by the inviting team. It rejects roles owned by another team. If the user already belongs to the inviting team, accepting does not create a duplicate membership.

<a id="team-settings"></a>
## Team settings

Team settings use the option resource. Aura prefixes each option name with `team.{id}.` and stores the team ID in `options.team_id`:

```php
$team->getOption('settings');
$team->getOption('theme.*');
$team->updateOption('settings', ['dark_mode' => true]);
$team->deleteOption('settings');
$team->clearCachedOption('settings');
```

Use a trailing `*` to retrieve a keyed collection of matching options. The `getOption()` method reads directly from the database. Calling `clearCachedOption()` only removes a cache entry if other code cached the same key separately. When teams are enabled, option queries are also filtered by the team scope.

<a id="deleting-teams"></a>
## Delete and restore teams

Teams use soft deletes. Deleting a team runs a cleanup hook that:

1. Switches users currently in that team to their first remaining membership, or clears their current team if none remains.
2. Deletes all memberships for the team.
3. Deletes the team's own roles, including overrides. Shared global roles remain.
4. Increments the role catalog version to invalidate cached effective roles.
5. Calls deletion for the team's meta rows, invitations, and options whose names start with `team.{id}.`.
6. Clears current-team and team-list caches for affected users, along with the global admin switcher cache.

```php
$team->delete();      // Soft delete and cleanup
$team->restore();     // Restore the Team row
$team->forceDelete(); // Permanently delete the Team row
```

Restoring a team does not restore the memberships, team roles, invitations, meta rows, or options removed during deletion.

Cleanup targets the deleted team explicitly. Its invitations and options are removed even after affected users switch to another team.

<a id="api-reference"></a>
## API reference

| Class or method | Use |
| --- | --- |
| `Team::users()` | Read team members through `user_role` |
| `Team::roles()` | Read roles owned by a team |
| `Team::teamInvitations()` | Read invitations stored for a team |
| `Team::getOption($key)` | Read a team option or wildcard collection |
| `Team::updateOption($key, $value)` | Create or update a team option |
| `Team::deleteOption($key)` | Delete a team option |
| `User::switchTeam($team)` | Change the current team or visit one as a global admin |
| `User::belongsToTeam($team)` | Check whether a user belongs to a team |
| `User::getTeams()` | Get a user's cached teams, or all teams for a global admin |
| `User::isSuperAdmin()` | Check the effective role in the current team |
| `User::isAuraGlobalAdmin()` | Check the global admin gate for the installation |
| `Role::resolveForTeam($slug, $teamId)` | Resolve a team override or shared global role |
| `Role::shadowResolvedForCurrentTeam()` | Get the merged role set used by role pickers |

<a id="teams-off"></a>
## Teams-off mode

With `aura.teams` set to false before installation:

- Aura does not create the `teams` table or team-specific columns.
- The team and invitation resources are not registered. The Teams tab, switcher, and invitation components are unavailable.
- The team scope does not filter queries.
- Roles form one flat catalog. There are no shared versus team roles, team overrides, membership team IDs, or `is_global` distinction.
- Public registration does not ask for a team and assigns the seeded `user` role.
- `php artisan aura:user` assigns the seeded `admin` role.
- The `global_admin` flag remains independent of roles. It does not allow team visits because no teams exist.

Use the [Teams-off installation steps](/docs/installation#without-teams) when the public beta requires the split setup. Do not change the setting after the schema exists without a deliberate migration and data plan.

<a id="testing"></a>
## Test team behavior

The package test helpers create an authenticated user with a current team:

```php
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});
```

Useful focused coverage includes:

- `tests/Feature/Team/TeamTest.php` for fields, creation, memberships, and cleanup.
- `tests/Feature/GlobalAdmin/GlobalAdminVisitationTest.php` and `tests/Feature/GlobalAdmin/GlobalAdminVisibilityTest.php` for team visits and user visibility across teams.
- `tests/Feature/Users/MembershipEditorTest.php` for membership authorization in the target team.
- `tests/Feature/Hardening/InvitationLifecycleTest.php` and `tests/Feature/Team/InvitationGlobalRoleTest.php` for signed invitations, expiry, role validation, and reuse.
- `tests/Feature/Team/CurrentTeamCacheTest.php` and `tests/Feature/Security/FailClosedQueryFiltersAndTeamScopeTest.php` for TeamScope and cache behavior.
- `tests/Feature/Team/CreateTeamsConfigTest.php` for `auth.create_teams`.
- `tests/FeatureWithDatabaseMigrations/WithoutTeamsSchemaTest.php` and `tests/FeatureWithDatabaseMigrations/RoleAssignmentWithoutTeamsTest.php` for the schema and role assignment with teams disabled.

See [Testing](/docs/testing) for the package test setup.

## Related guides

- [Roles and Permissions](/docs/roles-permissions)
- [Installation](/docs/installation)
- [Configuration](/docs/configuration)
- [Testing](/docs/testing)
