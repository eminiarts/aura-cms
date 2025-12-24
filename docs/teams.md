# Teams in Aura CMS

Teams in Aura CMS provide a powerful multi-tenancy solution that allows you to organize users and resources within isolated workspaces. This feature can be enabled or disabled based on your application's needs.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Team Management](#team-management)
- [Team Invitations](#team-invitations)
- [Resource Isolation](#resource-isolation)
- [Team Scope](#team-scope)
- [Bypassing Team Scope](#bypassing-team-scope)
- [Team Settings](#team-settings)
- [Team Lifecycle](#team-lifecycle)
- [API Reference](#api-reference)

<a name="overview"></a>
## Overview

Teams functionality in Aura CMS enables:

- Multi-tenant architecture with complete resource isolation
- Automatic scoping of all resources to the current team
- Team-specific settings and configurations via the Options system
- User management within teams with role-based access
- Team invitations with email notifications
- Automatic Super Admin role creation for new teams
- Soft delete support for teams

<a name="configuration"></a>
## Configuration

### Enabling Teams

Teams can be enabled or disabled in your `config/aura.php` or via environment variable:

```php
return [
    'teams' => env('AURA_TEAMS', true), // Set to false to disable teams functionality
    // ...
];
```

You can also set this via your `.env` file:

```env
AURA_TEAMS=true
```

> **Note**: If you change the teams setting after initial setup, you will need to run `php artisan migrate:fresh` to update the database schema.

### Team Authentication Settings

Configure team-related authentication settings:

```php
return [
    'auth' => [
        'registration' => env('AURA_REGISTRATION', true), // Allow user registration
        'create_teams' => true,     // Allow users to create new teams
        'user_invitations' => true, // Enable team invitations
        '2fa' => true,              // Enable two-factor authentication
    ],
    // ...
];
```

### Customizing Team Resources

You can customize the Team and TeamInvitation resources used by Aura:

```php
return [
    'resources' => [
        'team' => Aura\Base\Resources\Team::class,
        'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
        // ...
    ],
];
```

<a name="team-management"></a>
## Team Management

### Creating Teams

When teams are enabled, users with the `create-team` permission can create new teams. When a team is created:

1. The creating user is automatically set as the team owner (`user_id`)
2. A "Super Admin" role is automatically created for the team
3. The creating user is attached to the team with the Super Admin role
4. The user's `current_team_id` is switched to the new team
5. All resource permissions are automatically generated for the team

```php
// Team fields include:
[
    'name' => 'required',       // Team name
    'description' => '',        // Optional description
]
```

### Switching Teams

Users can switch between teams they belong to:

```php
// Check if user belongs to a team
if ($user->belongsToTeam($team)) {
    $user->switchTeam($team);
}

// Get user's current team
$currentTeam = $user->currentTeam;

// Check if a team is the current team
if ($user->isCurrentTeam($team)) {
    // ...
}
```

<a name="team-invitations"></a>
## Team Invitations

Team invitations are handled through the `TeamInvitation` resource. Users with the `invite-users` permission on the Team resource can send invitations.

### Invitation Flow

1. Team admin sends an invitation to an email address with a role assignment
2. User receives an email with a signed URL
3. If the user already exists, they can accept the invitation directly
4. If the user doesn't exist, they are redirected to a registration page
5. Upon acceptance, the user is added to the team with the specified role

### TeamInvitation Resource

```php
// TeamInvitation fields
[
    'email' => 'required',  // Email address to invite
    'role' => 'required',   // Role ID to assign
]
```

The invitation email is sent using the `Aura\Base\Mail\TeamInvitation` mailable, which uses the `aura::emails.team-invitation` view.

<a name="resource-isolation"></a>
## Resource Isolation

All resources in Aura CMS are automatically scoped to the current team when teams are enabled. This ensures complete data isolation between teams.

### How It Works

Resources are automatically filtered by the `team_id` column, which is set when creating new resources:

```php
// When creating a resource, team_id is automatically set
$post = Post::create(['title' => 'My Post']);
// $post->team_id is automatically set to auth()->user()->current_team_id
```

### Accessing Team Resources

Resources belonging to a team can be accessed through:

```php
// Via current team context (automatically applied)
$posts = Post::all(); // Only returns posts for the current team

// Via team relationship on the resource
$post->team; // Returns the team this resource belongs to

// Via user's current team
$team = auth()->user()->currentTeam;
```

<a name="team-scope"></a>
## Team Scope

The `TeamScope` (`Aura\Base\Models\Scopes\TeamScope`) is a global scope that is automatically applied to all resources extending `Aura\Base\Resource`.

### How TeamScope Works

1. **For regular resources**: Filters by `team_id = current_team_id`
2. **For the User model**: Filters users who belong to the current team via the `user_role` pivot table
3. **For the Team model**: No team filtering is applied (teams are not scoped to themselves)
4. **When teams are disabled**: No filtering is applied

```php
// TeamScope automatically adds this to queries:
$builder->where($model->getTable().'.team_id', $currentTeamId);
```

### Cache Mechanism

The current team ID is cached per user to avoid repeated database queries:

```php
// Cache key format
"user_{$userId}_current_team_id"
```

> **Important**: When changing a user's `current_team_id`, you should clear this cache to ensure the TeamScope uses the updated value.

<a name="bypassing-team-scope"></a>
## Bypassing Team Scope

Sometimes you need to query resources across all teams, such as in admin tools or background jobs.

### Using withoutGlobalScope

```php
use Aura\Base\Models\Scopes\TeamScope;

// Bypass TeamScope for a single query
$allPosts = Post::withoutGlobalScope(TeamScope::class)->get();

// Bypass all global scopes
$allPosts = Post::withoutGlobalScopes()->get();

// Bypass specific scopes
$posts = Post::withoutGlobalScopes([TeamScope::class])->get();
```

### In Tests

When writing tests, you often need to bypass TeamScope:

```php
use Aura\Base\Models\Scopes\TeamScope;

// In tests, bypass TeamScope to get actual counts
$count = User::withoutGlobalScopes()->count();

// Or to find specific records
$role = Role::withoutGlobalScope(TeamScope::class)
    ->where('slug', 'super_admin')
    ->where('team_id', $team->id)
    ->first();
```

<a name="team-settings"></a>
## Team Settings

### Team-Specific Options

Teams can have their own settings and configurations stored in the Options table:

```php
// Get team-specific option
$option = $team->getOption('setting_name');

// Get multiple options using wildcard
$options = $team->getOption('theme.*'); // Returns collection of all theme.* options

// Update team-specific option
$team->updateOption('setting_name', $value);

// Delete a team option
$team->deleteOption('setting_name');

// Clear cached option (useful after external updates)
$team->clearCachedOption('setting_name');
```

Options are stored with the naming convention: `team.{team_id}.{option_name}`

### Team Preferences

Each team can maintain its own:

1. Theme preferences (colors, dark mode, sidebar style)
2. Navigation settings
3. Custom configurations
4. Feature toggles

<a name="team-lifecycle"></a>
## Team Lifecycle

### Team Creation

When a new team is created, the following happens automatically:

1. The `user_id` is set to the authenticated user (team owner)
2. A "Super Admin" role is created with full permissions
3. The creating user is attached to the team with the Super Admin role
4. The user's `current_team_id` is updated to the new team
5. User team cache is cleared
6. `GenerateAllResourcePermissions` job is dispatched to create all permissions

### Team Deletion

Teams support soft deletes. When a team is deleted:

1. Users with this team as their `current_team_id` are switched to their first available team
2. All team meta data is deleted
3. All team invitations are deleted
4. All team-specific options are deleted
5. User team caches are cleared
6. Users are redirected to the dashboard

```php
// Team uses SoftDeletes trait
$team->delete();      // Soft delete
$team->forceDelete(); // Permanent delete
$team->restore();     // Restore soft-deleted team
```

<a name="api-reference"></a>
## API Reference

### Team Model Properties

```php
// Team uses a custom table (not the posts table)
public static $customTable = true;

// Team uses meta fields for additional data
public static bool $usesMeta = true;

// Teams are not included in global search
public static $globalSearch = false;
```

### Team Model Methods

```php
// Options management
$team->getOption($key);           // Get a team option (supports wildcards with *)
$team->updateOption($key, $value); // Create or update an option
$team->deleteOption($key);         // Delete an option
$team->clearCachedOption($key);    // Clear option cache

// Relationships
$team->users;           // BelongsToMany - all users in the team
$team->roles;           // HasMany - all roles belonging to the team
$team->teamInvitations; // HasMany - pending invitations
$team->meta;            // Team meta data

// Team info
$team->title();  // Returns team name
$team->getIcon(); // Returns team icon SVG

// Custom permissions
$team->customPermissions(); // Returns ['invite-users' => 'Invite users to team']
```

### User Team Methods

```php
// Current team
$user->currentTeam;       // BelongsTo - current team relation
$user->current_team_id;   // Current team ID

// Team membership
$user->teams;             // BelongsToMany - all teams user belongs to
$user->getTeams();        // Cached teams with meta loaded
$user->belongsToTeam($team); // Check if user belongs to a team
$user->isCurrentTeam($team); // Check if team is the current team
$user->ownsTeam($team);   // Check if user owns the team (user_id matches)

// Switching teams
$user->switchTeam($team); // Switch to another team (returns bool)

// Roles (team-aware)
$user->roles;         // BelongsToMany - includes team_id pivot
$user->cachedRoles(); // Cached roles for performance
$user->isSuperAdmin(); // Check if user has a super_admin role
$user->hasRole('admin'); // Check for specific role
$user->hasPermission('view-post'); // Check for specific permission
```

### Resource Team Methods

All resources extending `Aura\Base\Resource` have:

```php
// Team relationship
$resource->team; // BelongsTo - the team this resource belongs to

// Team ID is automatically set on creation
$resource->team_id; // The team ID
```

## Testing with Teams

When writing tests, the `createSuperAdmin()` helper creates a user with a team:

```php
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('team functionality', function () {
    // User has a team
    expect($this->user->currentTeam)->not->toBeNull();
    
    // Resources are scoped to the team
    $post = Post::create(['title' => 'Test']);
    expect($post->team_id)->toBe($this->user->current_team_id);
});
```

To test without teams, use `createSuperAdminWithoutTeam()` or run tests with:

```bash
vendor/bin/pest -c phpunit-without-teams.xml
```
