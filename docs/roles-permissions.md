# Roles and Permissions in Aura CMS

Aura CMS provides a flexible role-based access control (RBAC) system that allows you to manage user permissions effectively. This system integrates seamlessly with the Teams feature and supports both team-specific and global roles.

## Table of Contents

- [Overview](#overview)
- [Roles](#roles)
- [Permissions](#permissions)
- [Super Admin](#super-admin)
- [Team-Based Roles](#team-based-roles)
- [Usage Examples](#usage-examples)

<a name="overview"></a>
## Overview

The RBAC system in Aura CMS consists of:

- Roles: Define user groups and their capabilities
- Permissions: Granular access controls for resources
- Super Admin: Special role with full system access
- Team-specific roles (when Teams feature is enabled)

<a name="roles"></a>
## Roles

### Creating Roles

Roles can be created through the admin interface or programmatically:

```php
use Aura\Base\Resources\Role;

$role = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Can edit and publish content',
    'permissions' => [
        'view-posts' => true,
        'create-posts' => true,
        'edit-posts' => true
    ]
]);
```

### Role Configuration

Each role has the following attributes:

- `name`: Display name of the role
- `slug`: Unique identifier
- `description`: Role description
- `permissions`: Array of assigned permissions
- `super_admin`: Boolean flag for super admin status
- `team_id`: Team association (when Teams is enabled)

### Assigning Roles

Assign roles to users:

```php
// Assign a role to a user
$user->roles()->attach($roleId);

// With team context
$user->roles()->attach($roleId, ['team_id' => $teamId]);

// Multiple roles
$user->roles()->sync($roleIds);
```

<a name="permissions"></a>
## Permissions

### Permission Types

Default permission types for resources:

- `view`: View individual resources
- `viewAny`: View list of resources
- `create`: Create new resources
- `update`: Modify existing resources
- `delete`: Remove resources
- `restore`: Restore soft-deleted resources
- `forceDelete`: Permanently delete resources

### Checking Permissions

```php
// Check if user has permission
if ($user->hasPermission('create-posts')) {
    // User can create posts
}

// Check permission for specific resource
if ($user->hasPermissionTo('update', $post)) {
    // User can update this post
}
```

### Automatic Permission Generation

Aura CMS automatically generates permissions for new resources:

```php
// Generate permissions for all resources
php artisan aura:generate-permissions
```

<a name="super-admin"></a>
## Super Admin

### Super Admin Role

The super admin role has unrestricted access:

```php
$role = Role::create([
    'name' => 'Super Admin',
    'super_admin' => true
]);
```

### Checking Super Admin Status

```php
if ($user->isSuperAdmin()) {
    // User has full system access
}
```

<a name="team-based-roles"></a>
## Team-Based Roles

When Teams feature is enabled, roles can be team-specific:

### Team Role Management

```php
// Assign role within team context
$user->roles()->attach($roleId, [
    'team_id' => $currentTeamId
]);

// Get user's roles for current team
$roles = $user->roles()
    ->where('team_id', $user->current_team_id)
    ->get();
```

### Team Permission Scope

```php
// Check team-specific permission
if ($user->hasPermissionTo('create-posts', $team)) {
    // User can create posts in this team
}
```

<a name="usage-examples"></a>
## Usage Examples

### Basic Role Checks

```php
// Check if user has specific role
if ($user->hasRole('editor')) {
    // User is an editor
}

// Check multiple roles
if ($user->hasAnyRole(['editor', 'author'])) {
    // User has at least one of these roles
}
```

### Advanced Permission Usage

```php
// Resource-specific permissions
class PostController extends Controller
{
    public function update(Post $post)
    {
        $this->authorize('update', $post);

        // Update logic here
    }
}

// Custom permission checks
if ($user->hasPermission('publish-posts') && $post->status === 'draft') {
    // User can publish draft posts
}
```

### Role-Based UI Elements

```php
@if (auth()->user()->hasPermission('create-posts'))
    <button>Create Post</button>
@endif

@if (auth()->user()->isSuperAdmin())
    <a href="{{ route('admin.settings') }}">System Settings</a>
@endif
```


### Role Methods

```php
// Role model methods
$role->users();              // Get users with this role
$role->teams();              // Get teams using this role
$role->permissions;          // Get role permissions

// User role methods
$user->roles();              // Get user's roles
$user->hasRole($role);       // Check specific role
$user->hasAnyRole($roles);   // Check multiple roles
$user->isSuperAdmin();       // Check super admin status

// Permission methods
$user->hasPermission($permission);           // Check permission
$user->hasPermissionTo($ability, $resource); // Check resource permission
```
