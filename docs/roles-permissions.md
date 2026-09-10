# Roles and permissions

Aura uses roles and permissions to control what users can do with resources. Policies check the roles that apply in the current team. Each role grants specific permissions or gives the user Super Admin access in that team.

Aura has two separate administrative concepts:

- A Super Admin has administrative access through a role in a particular team.
- A Global Admin can administer the whole instance, including teams they do not belong to.

The admin pages for roles and permissions are under the **Users** menu:

- `/admin/role`
- `/admin/permission`

![Users index](/images/docs/roles-permissions/users-index.png)

## Data model

Aura stores role definitions, permission labels, and team memberships separately.

| Table | Relevant columns | Meaning |
| --- | --- | --- |
| `roles` | `name`, `slug`, `description`, `super_admin`, `permissions`, `user_id`, `team_id` | Role definitions. Permissions are stored as JSON and cast to an array of `slug => bool` grants. With teams enabled, a null team ID marks a global role. Otherwise, the role belongs to that team. |
| `permissions` | `name`, `slug`, `description`, `group`, `user_id`, `team_id` | Rows used by the role form's permission matrix. A row here does not grant access by itself. |
| `user_role` | `user_id`, `role_id`, and `team_id` when teams are on | Memberships connecting users to roles. The unique key is `(team_id, user_id)` with teams enabled, allowing one role per user in each team. Without teams, the key is `user_id`, allowing one role per user. |

With teams enabled, the role migration creates a composite unique index on `(slug, team_id)`. Aura treats one row with a null team ID per global role slug as its catalog definition. Without teams, the unique index covers only the slug.

Global Admin status is stored separately on the user as `global_admin`. This boolean is not mass assignable.

With teams disabled, these tables have no team columns and all roles share one catalog. The same role and permission checks still run.

To create a role for the authenticated user's current team:

~~~php
use Aura\Base\Resources\Role;

$attributes = [
    'name' => 'Content Editor',
    'slug' => 'content-editor',
    'description' => 'Can manage posts without deleting them.',
    'super_admin' => false,
    'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => true,
        'update-post' => true,
        'delete-post' => false,
    ],
];

if (config('aura.teams')) {
    $attributes['team_id'] = auth()->user()->current_team_id;
}

$role = Role::create($attributes);
~~~

With teams disabled, the same code creates a role without a team scope.

![Roles index](/images/docs/roles-permissions/roles-index.png)

<a id="role-catalog"></a>
## The role catalog

With teams enabled, roles can have either of two scopes:

- A global role is defined once and is available in every team. Its `team_id` is null.
- A team role belongs to one team and is available only there.

Only a Global Admin may define a global role. The role form provides a **Global Role** switch when teams are enabled. Aura checks the acting user before saving it. Submissions from other users create a role in their current team, even if they submit the switch as enabled.

The switch uses the form field `is_global`. It is not a database column. A guarded save hook applies its value.

A Global Admin can turn the switch off to convert a global role into a role for their current team.

<a id="shadowing"></a>
### Shadowing

A team role overrides a global role when both use the same slug. This is called shadowing. Other teams continue to use the global definition. Deleting the team role makes its team use the global definition again.

Aura resolves this by slug at permission-check time:

~~~php
use Aura\Base\Resources\Role;

$effectiveRole = Role::resolveForTeam(
    'editor',
    auth()->user()->current_team_id,
);
~~~

Memberships keep their original role ID. At each permission check, Aura uses the assigned role's slug to find the definition that applies in the current team. Creating or deleting an overriding team role therefore affects the next check without changing any memberships.

The roles index and role pickers show one row per slug. When a team role overrides a global role, users in that team see only the team role.

## Base catalog roles

The role catalog seeder, `Aura\Base\Database\Seeders\RoleCatalogSeeder`, creates two default roles:

| Slug | Name | `super_admin` | Default use |
| --- | --- | --- | --- |
| `admin` | Admin | `true` | Super Admin role attached to a team creator |
| `user` | User | `false` | Default role for registration without teams |

The default user role has no permissions until your application adds them. The install command runs the seeder after migrations, and running it again does not duplicate the roles. With teams enabled, both are global roles with a null team ID. Without teams, neither has a team scope.

If an installation did not run the seeder, these methods create the required roles when they are missing:

~~~php
use Aura\Base\Resources\Role;

Role::firstOrCreateCatalogRole('admin');
Role::firstOrCreateCatalogRole('user');
Role::firstOrCreateGlobalAdmin();
~~~

Team creation calls `firstOrCreateGlobalAdmin()` and assigns the shared admin role to the creator through a membership. Teams do not get separate copies of that role.

Registration assigns the shared admin role when teams are enabled and the user role when they are disabled. The `aura:user` command assigns the shared admin role when teams are disabled.

## Memberships and role assignment

A membership connects a user to a role in a team. Aura stores it in the `user_role` table. The user resource exposes these assignments through its `roles` relationship, which uses Laravel's many-to-many relationship type. With teams enabled, filter by the team on the pivot:

~~~php
$roles = $user->roles()
    ->wherePivot('team_id', $teamId)
    ->get();
~~~

The user form lets you select one role for the target user's current team. Clearing the field removes that membership. With teams disabled, it removes the user's only role.

The field uses `Aura\Base\Fields\Roles` with `'multiple' => false`:

~~~php
// The target user's current_team_id identifies the Membership to replace.
$target->update([
    'roles' => [$role->id],
]);
~~~

Before changing a membership, the field checks the submitted role:

- With teams enabled, the role must belong to the target user's current team or be a global role without a team override. A role from another team or an overridden global role returns a `403` response without changing the membership.
- Adding or removing a Super Admin role requires the acting user to pass `isSuperAdmin()`. Global Admin status alone does not satisfy this field check. The actor must also be a Super Admin in the current team.
- The field accepts a single role, matching the membership table's uniqueness rule. Sending several IDs is unsupported.

Access to the user edit form is controlled separately by its resource policy. A team Super Admin or Global Admin passes this policy. Other users need the `update-user` permission.

The dedicated membership editor checks access for each team. A Global Admin can manage any team. A Super Admin can manage a team where their effective role gives them Super Admin access. Other users can only view the memberships the page allows them to see.

The role picker hides overridden global roles and submits the active team role's ID. The save handler accepts only roles from this same effective catalog, so programmatic writes must also use the effective role ID. The membership editor and invitation validator apply the same shadowing rules.

Use the membership editor to attach, replace, or remove a user's role in another team.

Invitations follow the same rules. The selected role must be visible in the current team, and only a Super Admin can invite a user with a Super Admin role.

![Role edit](/images/docs/roles-permissions/role-edit.png)

## Generated resource permissions

Aura generates eight fixed permission slugs for each generated resource:

| Permission slug | Policy action |
| --- | --- |
| `viewAny-{resource-slug}` | List records |
| `view-{resource-slug}` | View one record |
| `create-{resource-slug}` | Create a record |
| `update-{resource-slug}` | Update a record |
| `delete-{resource-slug}` | Delete a record |
| `restore-{resource-slug}` | Restore a deleted record |
| `forceDelete-{resource-slug}` | Permanently delete a record |
| `scope-{resource-slug}` | Restrict queries and record access to owned records |

Select **Create Missing Permissions** on the roles index to generate permissions for the current team. This dispatches the `GenerateAllResourcePermissions` job, which writes permission rows for generated resources. It excludes the team resource.

The Artisan command runs the same job synchronously:

~~~bash
php artisan aura:create-resource-permissions
php artisan aura:create-resource-permissions --team=42
~~~

Without `--team`, the command uses the authenticated user's current team when one exists. Authentication is not required to run the command. With teams disabled, it writes rows without a team column.

Team invitations use the custom permission `invite-users-team`, which the team policy checks before allowing an invitation. The team resource declares it through `customPermissions()` as `invite-users`. Because the generator skips this resource, it does not create the permission row.

To make this permission available in the role form, create its row and grant it to a role:

~~~php
use Aura\Base\Resources\Permission;

Permission::create([
    'name' => 'Invite users to team',
    'slug' => 'invite-users-team',
    'group' => 'Team',
]);

$role->update([
    'permissions' => array_replace($role->permissions ?? [], [
        'invite-users-team' => true,
    ]),
]);
~~~

Generated permissions are grouped under the resource's plural name. These rows populate the checkbox matrix in the role form. Authorization reads the grants stored on the role, so creating a permission row alone does not grant access.

![Permissions index](/images/docs/roles-permissions/permissions-index.png)

<a id="permission-checks"></a>
## Checking permissions in code

The user resource provides these methods:

| Method | Returns true when |
| --- | --- |
| `hasPermission($slug)` | An effective role grants the exact slug, such as `publish-post` |
| `hasPermissionTo($ability, $resource)` | An effective role grants `{$ability}-{$resource::$slug}` |
| `hasRole($slug)` | An effective role has that slug |
| `hasAnyRole([$slugs])` | An effective role has any supplied slug |
| `isSuperAdmin()` | An effective role has `super_admin = true` |
| `isAuraGlobalAdmin()` | The `AuraGlobalAdmin` gate allows the user |

Role and permission checks use the effective roles for the current team, including any team overrides. Aura resolves them through `User::cachedRoles()` and caches the result on that user instance. Saving or deleting a role changes the catalog version used by the cache.

~~~php
if ($user->hasPermission('publish-post')) {
    $post->publish();
}

if ($user->hasPermissionTo('create', Post::class)) {
    $post = Post::create($attributes);
}

if ($user->hasPermissionTo('update', $post)) {
    // Update the Resource after the policy check.
}
~~~

For a Super Admin, `hasPermission()` and `hasPermissionTo()` return true even without explicit grants. Global Admin status does not assign a role. A Global Admin visiting a team may therefore pass a policy check while a direct permission check still reflects only their memberships.

Use Laravel abilities to authorize resource actions:

~~~blade
@can('update', $post)
    <button type="submit">Save</button>
@endcan

@superadmin
    <a href="{{ route('aura.settings') }}">Settings</a>
@endsuperadmin
~~~

Aura does not register permission slugs as Gate abilities, so `Gate::denies('create-post')` does not check the resource policy. Use `$user->can('create', Post::class)` for the policy check or `$user->hasPermission('create-post')` to check the permission directly.

<a id="resource-policy"></a>
## ResourcePolicy and TeamPolicy

Aura registers `ResourcePolicy` for resources. The user resource uses `UserPolicy`, which extends that base policy. With teams enabled, the team resource uses `TeamPolicy`.

For ordinary resource actions, the policy first checks whether the action is enabled on the resource. It then checks for administrative access before checking the specific permission:

~~~php
public function create($user, $resource)
{
    if ($resource::$createEnabled === false) {
        return false;
    }

    if ($this->hasBlanketAccess($user)) {
        return true;
    }

    return $user->hasPermissionTo('create', $resource);
}
~~~

The toggles and ownership checks are:

| Policy method | Toggle checked first | `scope` ownership check |
| --- | --- | --- |
| `viewAny` | `$indexViewEnabled` | No |
| `view` | `$viewEnabled` | Yes |
| `create` | `$createEnabled` | No |
| `update` | `$editEnabled` | Yes |
| `delete` | None | Yes |
| `restore` | None | No |
| `forceDelete` | None | No |

Viewing, updating, and deleting a record require an ownership check only when the role grants both the action and the scope permission. Restoring and permanently deleting records require only their respective permissions.

Changes to global roles have an additional guard that runs before the administrative bypass. In a team context, a team Super Admin cannot update, delete, restore, or permanently delete a global role. A Global Admin can. The team can still create its own role with the same slug to override the global definition.

To add a custom resource action, extend the policy and register it for the resource:

~~~php
namespace App\Policies;

use Aura\Base\Policies\ResourcePolicy;

class PostPolicy extends ResourcePolicy
{
    public function publish($user, $post): bool
    {
        return $this->hasBlanketAccess($user)
            || $user->hasPermission('publish-post');
    }
}
~~~

~~~php
use Illuminate\Support\Facades\Gate;

Gate::policy(
    \App\Aura\Resources\Post::class,
    \App\Policies\PostPolicy::class,
);
~~~

### Scoped access

The `scope-post` permission restricts users to records they own, identified by a matching `user_id`:

~~~php
$permissions = $role->permissions ?? [];
$permissions['viewAny-post'] = true;
$permissions['view-post'] = true;
$permissions['create-post'] = true;
$permissions['update-post'] = true;
$permissions['delete-post'] = true;
$permissions['scope-post'] = true;

$role->update(['permissions' => $permissions]);
~~~

For posts, granting `scope-post` makes `ScopedScope` filter queries by the current user's ID. The resource table must have a `user_id` column. The scope skips role and user resources, and Super Admins bypass it.

The resource policy also checks ownership when viewing, updating, or deleting a record.

Global Admin status bypasses policy permission checks but does not bypass this query scope. A scope permission on the Global Admin's role can still restrict their queries.

### TeamScope and team policy

With teams enabled, `TeamScope` limits team-aware resources, permissions, and membership-related role queries to the current team. It does not filter the team resource itself. Role queries include both the current team's roles and global roles. The roles index and role pickers then hide any global roles that the team has overridden.

Super Admins do not bypass this scope. Their resource queries still return rows from the current team.

`TeamPolicy` uses these rules:

| Action | Allowed when |
| --- | --- |
| `create` | `Team::$createEnabled` is on, `aura.auth.create_teams` is true, and the actor is a Global Admin |
| `view` | `Team::$viewEnabled` is on and the actor is a Global Admin or belongs to the team |
| `viewAny` | `Team::$indexViewEnabled` is on and the actor is a Global Admin or owns the team |
| `update` | `Team::$editEnabled` is on and the actor is a Global Admin or owns the team |
| `delete`, `removeTeamMember`, `updateTeamMember` | The actor is a Global Admin or owns the team |
| `addTeamMember` | The actor is a Global Admin, a Super Admin in the target team, or owns that team |
| `inviteUsers` | The actor is a Global Admin, owns the target team, or has `invite-users-team` in that team |

The team policy checks memberships and permissions in the target team. A Global Admin can view a team without belonging to it, provided the team resource allows viewing.

<a id="global-admin"></a>
## Global Admin

Aura determines Global Admin status through the `AuraGlobalAdmin` gate. By default, this gate reads the user's `global_admin` boolean. Aura defines it in `AuraServiceProvider`:

~~~php
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

Gate::define(User::GLOBAL_ADMIN_GATE, function ($user) {
    return (bool) ($user->global_admin ?? false);
});
~~~

An application provider can redefine the gate after Aura's provider boots. The gate receives a user argument, so guests fail it.

The CLI bootstrap command supports both flags:

~~~bash
php artisan aura:user --global-admin
php artisan aura:user --no-global-admin
~~~

The command starts with Global Admin enabled. An interactive run without either flag asks whether to keep it enabled. A non-interactive run keeps it enabled unless `--no-global-admin` is passed.

Only Global Admins can see the Global Admin field on the user form and grant or revoke this status. The field uses `Aura\Base\Fields\GlobalAdmin`, and the flag is excluded from the user's mass-assignable attributes.

The field's save hook silently ignores changes from guests and team Super Admins. It also ignores changes submitted through registration, invitation registration, mass assignment, or form tampering.

Global Admin capabilities include:

- Passing resource policy permission checks, subject to the resource toggles checked by each method.
- Listing and managing users across teams, including users without memberships.
- Creating, updating, and deleting teams, sending invitations, and managing memberships as allowed by the team policy or membership editor.
- Visiting a team through `User::switchTeam()` without creating a membership. Resource data remains scoped to the current team.
- Editing global roles and converting team roles into global roles through the guarded form switch.
- Impersonating users who are not Global Admins. A Global Admin cannot be impersonated.

To grant or remove a Super Admin role through the user form's role field, a Global Admin must also be a Super Admin in the current team. The dedicated membership editor has a separate guard and allows Global Admins to make this change.

<a id="global-admin-vs-super-admin"></a>
## Global Admin versus Super Admin

These are independent checks.

| | Super Admin | Global Admin |
| --- | --- | --- |
| Defined by | `super_admin` on a role | `users.global_admin` and the `AuraGlobalAdmin` gate |
| Effective scope | The team where the role is resolved | The whole instance |
| Resource policy permission bypass | Yes, after applicable resource toggles | Yes, after applicable resource toggles |
| TeamScope bypass | No | No for resource data |
| ScopedScope bypass | Yes | No |
| Changes to global roles | No in a team context | Yes |
| Enter a team without membership | No | Yes |
| Settings navigation | Yes | No, unless the user also is a Super Admin |
| Team policy access | Add members and the permission-based invite path | The Global Admin rows in the TeamPolicy table |
| Impersonation | No | Yes |

A Global Admin can also hold a Super Admin role. That role affects permission checks only when it applies through a membership in the current team.

## Role and permission fields

The role form contains these fields:

| Field | Aura type | Purpose |
| --- | --- | --- |
| Name | `Text` | Display name |
| Slug | `Slug` | Derived from Name and disabled in the form |
| Description | `Textarea` | Optional description |
| Admin | `Boolean`, `super_admin` | Grants administrative resource access in the current team |
| Global Role | `Boolean`, `is_global` | Changes the role scope, shown only to Global Admins with teams enabled |
| Permissions | `Aura\Base\Fields\Permissions` | Checkbox matrix backed by permission rows |

Enabling the **Admin** toggle hides the permissions matrix because Super Admins do not need individual permission grants.

The permission form contains name, slug, description, and group fields. The group organizes the checkbox matrix and does not affect authorization.

## Custom permissions

Store custom permissions on the role alongside generated permissions. Create a permission row to make it available in the role form, then grant it to a role and check it in your application:

~~~php
use Aura\Base\Resources\Permission;

Permission::create([
    'name' => 'Publish posts',
    'slug' => 'publish-post',
    'group' => 'Posts',
]);

$role->update([
    'permissions' => array_replace($role->permissions ?? [], [
        'publish-post' => true,
    ]),
]);

if (auth()->user()->hasPermission('publish-post')) {
    $post->publish();
}
~~~

The generator always produces the same eight action slugs. It has no dynamic registration method for additional resource actions.

## Related guides

- [Teams](/docs/teams) covers team creation, current-team context, and membership editing.
- [Authentication](/docs/authentication) covers registration, invitations, and impersonation.
- [Resources](/docs/resources) covers resource definitions and visibility toggles.
