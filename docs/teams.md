# Teams in Aura CMS

Teams in Aura CMS provide a powerful multi-tenancy solution that allows you to organize users and resources within isolated workspaces. This feature can be enabled or disabled based on your application's needs.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Team Management](#team-management)
- [Resource Isolation](#resource-isolation)
- [Team Settings](#team-settings)

<a name="overview"></a>
## Overview

Teams functionality in Aura CMS enables:

- Multi-tenant architecture
- Resource isolation between teams
- Team-specific settings and configurations
- User management within teams
- Team-based permissions and roles

<a name="configuration"></a>
## Configuration

### Enabling Teams

Teams can be enabled or disabled in your `config/aura.php`:

```php
return [
    'teams' => true, // Set to false to disable teams functionality
    // ...
];
```

### Team Authentication Settings

Configure team-related authentication settings:

```php
return [
    'auth' => [
        'create_teams' => true,    // Allow users to create teams
        'user_invitations' => true // Enable team invitations
    ],
    // ...
];
```

<a name="team-management"></a>
## Team Management

### Creating Teams

When teams are enabled, users with appropriate permissions can:

1. Create new teams
2. Invite team members
3. Manage team settings
4. Switch between teams

### Team Invitations

Team invitations are handled through the `TeamInvitation` resource:

1. Team owners can send invitations to users
2. Users receive email notifications
3. Users can accept or decline invitations
4. Upon acceptance, users are added to the team

<a name="resource-isolation"></a>
## Resource Isolation

### Team Scope

All resources in Aura CMS are automatically scoped to the current team when teams are enabled. This is handled by the `TeamScope`:

```php
// Example of how resources are scoped to teams
$posts = Post::all(); // Only returns posts for the current team
```

### Accessing Team Resources

Resources belonging to a team can be accessed through:

1. Direct team relationship:
```php
$team->posts; // Get all posts for a specific team
```

2. Current team context:
```php
auth()->user()->currentTeam->posts;
```

<a name="team-settings"></a>
## Team Settings

### Team-Specific Options

Teams can have their own settings and configurations:

```php
// Get team-specific option
$option = $team->getOption('setting_name');

// Update team-specific option
$team->updateOption('setting_name', $value);
```

### Team Preferences

Each team can maintain its own:

1. Theme preferences
2. Navigation settings
3. Custom configurations

## API Reference

### Team Model Methods

```php
// Get team option
$team->getOption($key);

// Update team option
$team->updateOption($key, $value);

// Get team members
$team->users;

// Get team owner
$team->owner;

// Get team invitations
$team->invitations;
```

### User Team Methods

```php
// Get user's current team
$user->currentTeam;

// Switch to another team
$user->switchTeam($team);

// Get all user's teams
$user->teams;

// Check if user owns team
$user->ownsTeam($team);
```
