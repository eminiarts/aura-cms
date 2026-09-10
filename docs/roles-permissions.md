# Roles and permissions

Aura authorizes Resource actions through roles and permissions. When a policy needs a permission, it reads the effective roles for the current team. A role either grants named permission slugs or carries the `super_admin` flag.

Aura has two separate administrative concepts:

- A **Super Admin** is a role-level grant that applies in the team where the role is held.
- A **Global Admin** is an instance-level operator. The Global Admin gate can cross the team boundary.

The admin pages for roles and permissions are under the **Users** menu:

- `/admin/role`
- `/admin/permission`

![Users index](/images/docs/roles-permissions/users-index.png)

## Data model

Aura stores role definitions, permission labels, and Memberships separately.

| Table | Relevant columns | Meaning |
| --- | --- | --- |
| `roles` | `name`, `slug`, `description`, `super_admin`, `permissions`, `user_id`, `team_id` | A role definition. `permissions` is JSON cast to an array of `slug => bool` grants. With teams on, `team_id = null` identifies a Global Role and a value identifies a Team Role. |
| `permissions` | `name`, `slug`, `description`, `group`, `user_id`, `team_id` | Rows used by the role form's permission matrix. A row here does not grant access by itself. |
| `user_role` | `user_id`, `role_id`, and `team_id` when teams are on | A Membership. The unique key is `(team_id, user_id)` with teams and `(user_id)` in Teams-off mode, so a user has at most one role in each team. |

The role migration declares a composite unique index on `(slug, team_id)` when teams are on and a unique index on `slug` in Teams-off mode. The application treats one `team_id = null` row per Global Role slug as the catalog definition.

The `global_admin` boolean lives on `users`. It is separate from the role tables and is not mass assignable.

In Teams-off mode, the tables do not have team columns and the role catalog is flat. The same role and permission checks still run.

A Team Role can be created from an authenticated team context:

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

With teams on, this creates a Team Role in the current team. With teams off, the same code creates a flat role.

![Roles index](/images/docs/roles-permissions/roles-index.png)

<a id="role-catalog"></a>
## The Role Catalog

With teams on, the catalog contains two role scopes:

- A **Global Role** has `roles.team_id = null`. It is defined once and is available in every team.
- A **Team Role** has a team id. It exists only in that team.

Only a Global Admin may define a Global Role. The Role form's **Global Role** switch is a form field named `is_global`, not a database column. Aura captures the submitted value and applies it in a guarded save hook. A non-Global-Admin submission cannot create a Global Role and is assigned to the actor's current team. In Teams-off mode, the switch is hidden.

A Global Admin can turn the switch off on a Global Role. Aura then assigns that role to the Global Admin's current team as a Team Role.

<a id="shadowing"></a>
### Shadowing

A Team Role shadows a Global Role when both rows use the same slug. The Team Role wins inside its team. Other teams still resolve the Global Role. Deleting the Team Role returns that team to the Global Role.

Aura resolves this by slug at permission-check time:

~~~php
use Aura\Base\Resources\Role;

$effectiveRole = Role::resolveForTeam(
    'editor',
    auth()->user()->current_team_id,
);
~~~

The `user_role` Membership keeps its original `role_id`. Aura reads that row's slug and resolves the effective definition for the current team. Creating or deleting a Shadow therefore changes the next permission check without rewriting Membership rows.

The Roles index and role pickers show one row per slug. When a Team Role shadows a Global Role, the Team Role is shown and the Global Role row is hidden for that team.

## Base catalog roles

`Aura\Base\Database\Seeders\RoleCatalogSeeder` creates two base catalog rows:

| Slug | Name | `super_admin` | Default use |
| --- | --- | --- | --- |
| `admin` | Admin | `true` | Super Admin role attached to a team creator |
| `user` | User | `false` | Flat default role for Teams-off registration |

The `user` role has no grants until an application adds them. The seeder is idempotent and runs from the install command after migrations. In Teams-on mode these rows have `team_id = null`. In Teams-off mode they are flat catalog rows and have no team scope.

When an install did not run the seeder, the package can self-heal the required rows through:

~~~php
use Aura\Base\Resources\Role;

Role::firstOrCreateCatalogRole('admin');
Role::firstOrCreateCatalogRole('user');
Role::firstOrCreateGlobalAdmin();
~~~

Team creation calls `firstOrCreateGlobalAdmin()` and attaches the creator to that shared `admin` row through `user_role`. It does not create a separate `admin` row for each team. Registration uses the same shared `admin` role when teams are on and the `user` role when teams are off. The `aura:user` command uses the shared `admin` role in Teams-off mode.

## Memberships and role assignment

A Membership is the row in `user_role` that connects one user, one team, and one role. The `User` resource exposes a `roles` BelongsToMany relationship. In teams-on code, filter that relationship by the pivot team:

~~~php
$roles = $user->roles()
    ->wherePivot('team_id', $teamId)
    ->get();
~~~

The User resource's Role field is `Aura\Base\Fields\Roles` with `'multiple' => false`. It stores one role for the target user's current team. Saving an empty value detaches that Membership. In Teams-off mode it detaches the user's only role.

~~~php
// The target user's current_team_id identifies the Membership to replace.
$target->update([
    'roles' => [$role->id],
]);
~~~

The field performs these checks before it changes the pivot:

- With teams on, the submitted id must belong to the target's current team or be an unshadowed Global Role. A role owned by another team or a hidden shadowed Global Role causes a `403` and no pivot write.
- Adding or removing a role with `super_admin = true` requires the acting User to pass `isSuperAdmin()`. A Global Admin who is not also a Super Admin in the current team does not pass this particular field guard.
- The field's single-select shape matches the `user_role` uniqueness rule. Sending several ids is unsupported.

The User Resource policy controls access to the edit form itself. A non-blanket actor needs the `update-user` permission. A team Super Admin or Global Admin passes the Resource policy. The dedicated Membership editor uses a separate per-team check: a Global Admin can manage any team, and a Super Admin can manage a team where the actor's resolved role is a Super Admin. Other users can see only the rows allowed by the page and cannot mutate them.

The role picker uses the merged catalog. It hides a shadowed Global Role and sends the active Team Role id. The save handler enforces the same resolved set. Programmatic writes must use that resolved id. The Membership editor and invitation validator also resolve Shadowing before they accept submitted role ids. Use the Membership editor to attach, replace, or remove a user's role in another team.

Invitations apply the same catalog and escalation rules. The inviter's submitted role must be visible in the current team. A non-Super-Admin inviter cannot invite a user with a Super Admin role.

![Role edit](/images/docs/roles-permissions/role-edit.png)

## Generated Resource permissions

Aura generates eight fixed permission slugs for each generated Resource:

| Permission slug | Policy action |
| --- | --- |
| `viewAny-{resource-slug}` | List Resources |
| `view-{resource-slug}` | View one Resource |
| `create-{resource-slug}` | Create a Resource |
| `update-{resource-slug}` | Update a Resource |
| `delete-{resource-slug}` | Delete a Resource |
| `restore-{resource-slug}` | Restore a deleted Resource |
| `forceDelete-{resource-slug}` | Permanently delete a Resource |
| `scope-{resource-slug}` | Restrict queries and record access to owned Resources |

The Roles index action **Create Missing Permissions** dispatches `GenerateAllResourcePermissions` for the current team. The job excludes the `Team` Resource and writes the other generated Resource permissions to the `permissions` table.

The Artisan command runs the same job synchronously:

~~~bash
php artisan aura:create-resource-permissions
php artisan aura:create-resource-permissions --team=42
~~~

Without `--team`, the command uses the authenticated user's current team when one exists. The command has no authentication requirement. In Teams-off mode it writes rows without a team column.

The `Team` Resource is special. Its `customPermissions()` method declares `invite-users`, and `TeamPolicy::inviteUsers` checks the resulting permission slug `invite-users-team`. The generator does not create this row because it skips `Team`. To expose that grant in the role form, create the permission row and add the slug to a role:

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

Generated rows use the Resource's plural name as their `group` value. The permission row only populates the matrix. The role's JSON grant is what a permission check reads.

![Permissions index](/images/docs/roles-permissions/permissions-index.png)

<a id="permission-checks"></a>
## Checking permissions in code

The User Resource exposes these checks:

| Method | Returns true when |
| --- | --- |
| `hasPermission($slug)` | An effective role grants the exact slug, such as `publish-post` |
| `hasPermissionTo($ability, $resource)` | An effective role grants `{$ability}-{$resource::$slug}` |
| `hasRole($slug)` | An effective role has that slug |
| `hasAnyRole([$slugs])` | An effective role has any supplied slug |
| `isSuperAdmin()` | An effective role has `super_admin = true` |
| `isAuraGlobalAdmin()` | The `AuraGlobalAdmin` gate allows the User instance |

All role and permission checks use `User::cachedRoles()`. It resolves Membership role ids by slug, applies Shadowing for the current team, and caches the result for that User instance. A Role write or delete bumps the catalog version used by that cache.

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

A Super Admin returns true from `hasPermission` and `hasPermissionTo` even when the JSON grant is empty. A Global Admin does not acquire a role through the gate. A Global Admin visiting a team can therefore pass a policy while `hasPermission` still reflects that user's Memberships.

Use Laravel abilities for Resource actions:

~~~blade
@can('update', $post)
    <button type="submit">Save</button>
@endcan

@superadmin
    <a href="{{ route('aura.settings') }}">Settings</a>
@endsuperadmin
~~~

Aura does not register Gate abilities named after permission slugs. `Gate::denies('create-post')` is not the Resource check. Use `$user->can('create', Post::class)` or `$user->hasPermission('create-post')`.

<a id="resource-policy"></a>
## ResourcePolicy and TeamPolicy

Aura registers `ResourcePolicy` for Resource classes, `UserPolicy` for the User Resource, and `TeamPolicy` for the Team Resource when teams are on. `UserPolicy` extends `ResourcePolicy`.

For the ordinary Resource actions, the policy checks the Resource toggle, then blanket access, then the permission:

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

For `view`, `update`, and `delete`, the owner check applies only when the role grants both the action and `scope`. `restore` and `forceDelete` use their permission alone.

A mutating policy call for a Global Role runs the Global Role write guard before blanket access. In a team context, a team Super Admin cannot update, delete, restore, or force-delete a Global Role. A Global Admin can. A team can still create a Team Role with the same slug as a Shadow.

To add a custom Resource action, extend the policy and register it for the Resource:

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

The `scope-post` grant narrows a user to Resources whose `user_id` matches the current User:

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

`ScopedScope` adds a `user_id` filter to Resource queries when the current role grants `scope-post`. It skips the Role and User Resources. A Super Admin bypasses this query scope. The Resource policy repeats ownership checks for `view`, `update`, and `delete`. The Resource table needs a `user_id` column.

Global Admin is a policy bypass, not a `ScopedScope` bypass. A Global Admin's Resource query can still be affected by a role-level scope grant if that user has one.

### TeamScope and team policy

With teams on, `TeamScope` filters team-aware Resources, permissions, and Membership-related role queries to the current team. It does not filter the Team Resource itself. It shows the current team's Team Roles together with Global Roles. The Roles index and role pickers apply the Shadow-resolved filter on top of that query.

Super Admin does not bypass `TeamScope`. A Super Admin still queries Resource rows from the current team.

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

The Team policy evaluates membership and permission grants against the target team. A Global Admin can view a team without membership when that Team's view is enabled.

<a id="global-admin"></a>
## Global Admin

Global Admin status is the `global_admin` boolean and the `AuraGlobalAdmin` gate. Aura defines the gate in `AuraServiceProvider`:

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

The User form exposes `Aura\Base\Fields\GlobalAdmin` only to a Global Admin. `global_admin` is outside User `$fillable`. The field's save hook silently ignores changes from guests, team Super Admins, registration, invitation registration, mass assignment, and form tampering. A Global Admin can grant or revoke the flag.

Global Admin capabilities include:

- ResourcePolicy blanket access, subject to Resource toggles where those methods check them.
- Listing and managing users across teams. The Users index includes users with no Membership.
- Team creation, team updates, team deletion, invitations, and Membership operations allowed by TeamPolicy or the dedicated Membership editor.
- Visitation through `User::switchTeam()` without creating a `user_role` row. Resource data remains current-team scoped.
- Editing Global Roles and promoting Team Roles through the guarded `is_global` switch.
- Impersonating non-Global-Admins. A Global Admin cannot be impersonated.

A Global Admin who is not also a Super Admin in the current team cannot use the User Roles field to grant or remove a `super_admin` role. The dedicated Membership editor has its own guard and permits that operation for a Global Admin.

<a id="global-admin-vs-super-admin"></a>
## Global Admin versus Super Admin

These are independent checks.

| | Super Admin | Global Admin |
| --- | --- | --- |
| Defined by | `super_admin` on a role | `users.global_admin` and the `AuraGlobalAdmin` gate |
| Effective scope | The team where the role is resolved | The whole instance |
| ResourcePolicy blanket access | Yes, after Resource toggles | Yes, after Resource toggles |
| TeamScope bypass | No | No for Resource data |
| ScopedScope bypass | Yes | No |
| Global Role mutation | No in a team context | Yes |
| Enter a team without Membership | No | Yes |
| Settings navigation | Yes | No, unless the user also is a Super Admin |
| Team policy access | Add members and the permission-based invite path | The Global Admin rows in the TeamPolicy table |
| Impersonation | No | Yes |

A Global Admin can have a Super Admin role as well. The role affects permission checks only when it resolves through a Membership in the current team.

## Role and permission fields

The Role Resource declares these fields:

| Field | Aura type | Purpose |
| --- | --- | --- |
| Name | `Text` | Display name |
| Slug | `Slug` | Derived from Name and disabled in the form |
| Description | `Textarea` | Optional description |
| Admin | `Boolean`, `super_admin` | Grants blanket Resource access in the current team |
| Global Role | `Boolean`, `is_global` | Guarded catalog switch, shown only to Global Admins with teams on |
| Permissions | `Aura\Base\Fields\Permissions` | Checkbox matrix backed by permission rows |

The Permissions field is hidden when the Admin toggle is on because a Super Admin does not need JSON grants.

The Permission Resource form exposes Name, Slug, Description, and Group. The Group value organizes the checkbox matrix. It does not affect authorization.

## Custom permissions

Custom permissions use the same JSON map as generated permissions. Create a Permission row so the slug appears in the role form, then grant the slug and check it in application code:

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

There is no dynamic registration method that adds more generated Resource actions. The generator always produces the eight fixed action slugs.

## Related guides

- [Teams](/docs/teams) covers team creation, current-team context, and Membership editing.
- [Authentication](/docs/authentication) covers registration, invitations, and impersonation.
- [Resources](/docs/resources) covers Resource definitions and Resource visibility toggles.
