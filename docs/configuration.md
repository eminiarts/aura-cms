# Configuration

> 📹 **Video Placeholder**: Deep dive into Aura CMS configuration, showing real-time changes to themes, features, and settings with live preview

Aura CMS provides a powerful configuration system that lets you customize every aspect of your CMS. This comprehensive guide covers all configuration options, best practices, and real-world examples to help you tailor Aura CMS to your exact needs.

## Table of Contents

- [Configuration Overview](#configuration-overview)
- [Quick Configuration](#quick-configuration)
- [Main Configuration (aura.php)](#main-configuration)
  - [Core Settings](#core-settings)
  - [Teams & Multi-tenancy](#teams-multi-tenancy)
  - [Component Customization](#component-customization)
  - [Resource Management](#resource-management)
  - [Theme Configuration](#theme-configuration)
  - [View Customization](#view-customization)
  - [Feature Toggles](#feature-toggles)
  - [Authentication Settings](#authentication-settings)
  - [Media Configuration](#media-configuration)
- [Settings Configuration (aura-settings.php)](#settings-configuration)
  - [Resource & Field Paths](#resource-field-paths)
  - [Widget Configuration](#widget-configuration)
  - [Middleware Stacks](#middleware-stacks)
- [Environment Variables](#environment-variables)
- [Performance Optimization](#performance-optimization)
- [Common Configuration Scenarios](#common-configuration-scenarios)
- [Configuration Best Practices](#configuration-best-practices)
- [Troubleshooting](#configuration-troubleshooting)

---

<a name="configuration-overview"></a>
## Configuration Overview

Aura CMS uses a layered configuration approach:

```
┌─────────────────────────────────────────────────────────────┐
│                     Environment (.env)                       │
│                  Runtime Configuration                       │
├─────────────────────────────────────────────────────────────┤
│                    config/aura.php                          │
│                 Main Configuration File                      │
├─────────────────────────────────────────────────────────────┤
│                config/aura-settings.php                      │
│              Advanced Settings & Paths                       │
├─────────────────────────────────────────────────────────────┤
│                    Laravel Fortify                          │
│              Authentication Configuration                    │
└─────────────────────────────────────────────────────────────┘
```

### Configuration Files

| File | Purpose | When to Edit |
|------|---------|--------------|
| `config/aura.php` | Main configuration | Always - core settings |
| `config/aura-settings.php` | Advanced paths & middleware | Custom installations |
| `config/fortify.php` | Authentication settings | Auth customization |
| `.env` | Environment-specific values | Per environment |

> **Pro Tip**: Use environment variables for values that change between environments (local, staging, production)

<a name="quick-configuration"></a>
## Quick Configuration

Use the interactive configuration command to modify settings without editing files:

```bash
# Modify configuration interactively
php artisan aura:install-config

# This allows you to:
# - Enable/disable teams
# - Toggle features
# - Configure theme
# - Set authentication options
```

> 📹 **Video Placeholder**: Using the interactive configuration command to customize Aura CMS settings

<a name="main-configuration"></a>
## Main Configuration (aura.php)

The `config/aura.php` file controls all core functionality. Let's explore each section:

<a name="core-settings"></a>
### Core Settings

#### Path Configuration

```php
'path' => env('AURA_PATH', 'admin'),
```

Controls where your admin panel is accessible:

```php
// Examples
'path' => 'admin',        // yourdomain.com/admin
'path' => 'dashboard',    // yourdomain.com/dashboard
'path' => 'cms',         // yourdomain.com/cms
'path' => 'backend',     // yourdomain.com/backend
```

> **Common Pitfall**: Ensure your chosen path doesn't conflict with existing routes

#### Domain Configuration

```php
'domain' => env('AURA_DOMAIN'),
```

Restrict Aura CMS to specific domains:

```php
// Single domain
'domain' => 'admin.yourdomain.com',

// Environment-based
'domain' => env('AURA_DOMAIN', 'admin.localhost'),

// Null for all domains (default)
'domain' => null,
```

**Use Cases:**
- Separate admin subdomain: `admin.yourdomain.com`
- Multi-site installations
- Development/staging isolation

<a name="teams-multi-tenancy"></a>
### Teams & Multi-tenancy

```php
'teams' => env('AURA_TEAMS', true),
```

Teams enable powerful multi-tenant functionality:

```php
// Enable teams (default)
'teams' => true,  // Multi-tenant application

// Disable teams
'teams' => false, // Single-tenant application
```

**When to Use Teams:**
- SaaS applications with customer isolation
- Agency managing multiple clients
- Enterprise with department separation
- Multi-site content management

**When to Disable Teams:**
- Personal blogs or portfolios
- Single company websites
- Simple applications

> **Important**: Changing teams setting after installation requires database migration:
> ```bash
> php artisan migrate:fresh --seed
> ```

**Team Features When Enabled:**
- Automatic data isolation per team
- Team member management
- Team-based permissions
- Team switching UI
- Invitation system

```php
// Example: Team-scoped Resource
namespace App\Aura\Resources;

use Aura\Base\Resource;
use Aura\Base\Models\Scopes\TeamScope;

class Project extends Resource
{
    public static function booted()
    {
        static::addGlobalScope(new TeamScope);
    }
}

<a name="component-customization"></a>
### Component Customization

```php
'components' => [
    'dashboard' => Aura\Base\Livewire\Dashboard::class,
    'profile' => Aura\Base\Livewire\Profile::class,
    'settings' => Aura\Base\Livewire\Settings::class,
],
```

Replace core Livewire components with your own implementations:

```php
// Custom Dashboard Example
namespace App\Http\Livewire;

use Aura\Base\Livewire\Dashboard as BaseDashboard;

class CustomDashboard extends BaseDashboard
{
    public function render()
    {
        // Add custom metrics
        $metrics = [
            'total_revenue' => Order::sum('total'),
            'new_customers' => User::whereDate('created_at', today())->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];
        
        return view('livewire.custom-dashboard', [
            'metrics' => $metrics
        ])->layout('aura::components.layout.app');
    }
}

// Register in config/aura.php
'components' => [
    'dashboard' => App\Http\Livewire\CustomDashboard::class,
],
```

**Common Component Customizations:**
- Dashboard with custom widgets
- Profile with additional fields
- Settings with company-specific options
- Custom navigation components

<a name="resource-management"></a>
### Resource Management

```php
'resources' => [
    'user' => Aura\Base\Resources\User::class,
    'team' => Aura\Base\Resources\Team::class,
    'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
    'role' => Aura\Base\Resources\Role::class,
    'permission' => Aura\Base\Resources\Permission::class,
    'option' => Aura\Base\Resources\Option::class,
    'attachment' => Aura\Base\Resources\Attachment::class,
],
```

Customize or extend built-in resources:

```php
// Override built-in User resource
namespace App\Aura\Resources;

use Aura\Base\Resources\User as BaseUser;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Select;

class User extends BaseUser
{
    public function fields()
    {
        return array_merge(parent::fields(), [
            Text::make('Department'),
            Select::make('Office Location')->options([
                'nyc' => 'New York',
                'lon' => 'London',
                'tok' => 'Tokyo',
            ]),
        ]);
    }
}

// Register in config
'resources' => [
    'user' => App\Aura\Resources\User::class,
    // ... other resources
],
```

**Pro Tip**: Resources are auto-discovered from `app/Aura/Resources/`, but you can override them here for custom implementations.

<a name="theme-configuration"></a>
### Theme Configuration

```php
'theme' => [
    'color-palette' => 'aura',
    'gray-color-palette' => 'slate',
    'darkmode-type' => 'auto',
    'sidebar-size' => 'standard',
    'sidebar-type' => 'primary',
    'sidebar-darkmode-type' => 'dark',
    'login-bg' => false,
    'login-bg-darkmode' => false,
    'app-favicon' => false,
    'app-favicon-darkmode' => false,
],
```

#### Available Color Palettes

| Primary Colors | Gray Palettes | Description |
|----------------|---------------|-------------|
| `aura` (default) | `slate` | Aura's signature purple |
| `red`, `orange`, `amber` | `gray` | Warm tones |
| `yellow`, `lime`, `green` | `zinc` | Nature inspired |
| `emerald`, `teal`, `cyan` | `neutral` | Cool tones |
| `sky`, `blue`, `indigo` | `stone` | Professional |
| `violet`, `purple`, `fuchsia` | `purple-slate` | Creative |
| `pink`, `rose` | `blue` | Soft tones |

#### Theme Examples

```php
// Corporate Blue Theme
'theme' => [
    'color-palette' => 'blue',
    'gray-color-palette' => 'slate',
    'darkmode-type' => 'auto',
    'sidebar-type' => 'dark',
],

// Creative Agency Theme
'theme' => [
    'color-palette' => 'purple',
    'gray-color-palette' => 'zinc',
    'darkmode-type' => 'dark',
    'sidebar-type' => 'primary',
    'sidebar-size' => 'compact',
],

// Minimal Light Theme
'theme' => [
    'color-palette' => 'gray',
    'gray-color-palette' => 'neutral',
    'darkmode-type' => 'light',
    'sidebar-type' => 'light',
],
```

#### Custom Branding

```php
// Custom login backgrounds
'login-bg' => 'images/login-bg-light.jpg',
'login-bg-darkmode' => 'images/login-bg-dark.jpg',

// Custom favicons
'app-favicon' => 'favicons/light.ico',
'app-favicon-darkmode' => 'favicons/dark.ico',
```

> **Pro Tip**: Test different color combinations in real-time using the Settings page in the admin panel

<a name="view-customization"></a>
### View Customization

```php
'views' => [
    'layout' => 'aura::components.layout.app',
    'login-layout' => 'aura::components.layout.login',
    'dashboard' => 'aura::dashboard',
    'index' => 'aura::index',
    'view' => 'aura::view',
    'create' => 'aura::create',
    'edit' => 'aura::edit',
    'navigation' => 'aura::components.navigation',
    'logo' => 'aura::components.application-logo',
],
```

Override any view to customize the UI:

```php
// Custom views example
'views' => [
    // Use custom layout
    'layout' => 'layouts.admin',
    
    // Custom dashboard
    'dashboard' => 'admin.dashboard',
    
    // Custom logo component
    'logo' => 'components.company-logo',
    
    // Custom navigation
    'navigation' => 'components.custom-nav',
],
```

**Creating Custom Views:**

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
@extends('aura::components.layout.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Custom dashboard content --}}
    <x-aura::card>
        <h3>Welcome {{ auth()->user()->name }}</h3>
        {{-- Your custom metrics --}}
    </x-aura::card>
</div>
@endsection
```

<a name="feature-toggles"></a>
### Feature Toggles

```php
'features' => [
    'global_search' => true,              // ⇧⌘K quick search
    'bookmarks' => true,                  // Save favorite pages
    'last_visited_pages' => true,         // Recent pages tracking
    'notifications' => true,              // In-app notifications
    'plugins' => true,                    // Plugin system
    'settings' => true,                   // Settings page
    'profile' => true,                    // User profile page
    'create_resource' => true,            // Resource creation UI
    'resource_view' => true,              // Resource viewing
    'resource_edit' => true,              // Resource editing
    'resource_editor' => config('app.env') == 'local',  // Visual editor
    'custom_tables_for_resources' => false,  // Storage strategy
],
```

#### Feature Scenarios

**Minimal Setup (Content Viewers)**
```php
'features' => [
    'global_search' => true,
    'bookmarks' => true,
    'create_resource' => false,
    'resource_edit' => false,
    'resource_editor' => false,
    'settings' => false,
],
```

**Developer Mode (All Features)**
```php
'features' => [
    'global_search' => true,
    'resource_editor' => true,  // Visual resource builder
    'plugins' => true,
    'custom_tables_for_resources' => true,
    // ... all other features enabled
],
```

**SaaS Application**
```php
'features' => [
    'notifications' => true,
    'bookmarks' => true,
    'settings' => false,  // Use custom settings
    'plugins' => false,   // Controlled features only
],
```

> **Important**: The `custom_tables_for_resources` feature changes how data is stored:
> - `false`: Uses shared `posts` and `meta` tables (default)
> - `true`: Each resource gets its own database table

<a name="auth"></a>
### Auth

```php
'auth' => [
    'registration' => env('AURA_REGISTRATION', true),
    'redirect' => '/admin',
    '2fa' => true,
    'user_invitations' => true,
    'create_teams' => true,
],
```

- **Description**: Manages authentication-related settings.
- **Options**:
  - **registration**: Allows or disallows user registration.
  - **redirect**: Sets the redirect URL after login.
  - **2fa**: Enables two-factor authentication.
  - **user_invitations**: Allows inviting users to the CMS.
  - **create_teams**: Enables team creation functionality.

**Example: Disabling User Registration and Enabling Two-Factor Authentication**

```php
'auth' => [
    'registration' => false,
    '2fa' => true,
],
```

*Figure 9: Configuring Authentication Settings*

![Figure 9: Configuring Authentication Settings](placeholder-image.png)

<a name="media"></a>
### Media

```php
'media' => [
    'disk' => 'public',
    'path' => 'media',
    'quality' => 80,
    'restrict_to_dimensions' => true,

    'max_file_size' => 10000,

    'generate_thumbnails' => true,
    'dimensions' => [
        [
            'name' => 'xs',
            'width' => 200,
        ],
        [
            'name' => 'sm',
            'width' => 600,
        ],
        [
            'name' => 'md',
            'width' => 1200,
        ],
        [
            'name' => 'lg',
            'width' => 2000,
        ],
        [
            'name' => 'thumbnail',
            'width' => 600,
            'height' => 600,
        ],
    ],
],
```

- **Description**: Configures media handling, including storage disk, file paths, image quality, and thumbnail generation.
- **Options**:
  - **disk**: Specifies the storage disk (as defined in `config/filesystems.php`).
  - **path**: Defines the directory where media files are stored.
  - **quality**: Sets the image quality (percentage).
  - **restrict_to_dimensions**: Limits uploaded images to specified dimensions.
  - **max_file_size**: Maximum allowed file size in kilobytes.
  - **generate_thumbnails**: Enables or disables automatic thumbnail generation.
  - **dimensions**: Defines the different thumbnail sizes.

**Example: Changing the Media Disk and Disabling Thumbnail Generation**

```php
'media' => [
    'disk' => 's3',
    'generate_thumbnails' => false,
],
```

*Figure 10: Configuring Media Settings*

![Figure 10: Configuring Media Settings](placeholder-image.png)

---

<a name="aura-settingsphp-configuration"></a>
## aura-settings.php Configuration

The `aura-settings.php` file provides additional configuration options related to resource paths, widgets, and middleware. This allows for further customization and organization of your Aura CMS setup.

<a name="paths"></a>
### Paths

```php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
        'register' => [],
    ],

    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
        'register' => [],
    ],
],
```

- **Description**: Defines the namespaces and directories for resources and fields.
- **Options**:
  - **namespace**: The PHP namespace for the resources or fields.
  - **path**: The filesystem path where resources or fields are located.
  - **register**: An array to manually register additional resources or fields.

**Example: Changing the Resources Path**

```php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Custom\\Resources',
        'path' => app_path('Custom/Resources'),
        'register' => [],
    ],
],
```

*Figure 11: Configuring Resource Paths*

![Figure 11: Configuring Resource Paths](placeholder-image.png)

<a name="widgets"></a>
### Widgets

```php
'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
    'register' => [],
],
```

- **Description**: Specifies the namespace and path for dashboard widgets.
- **Customization**: Override the default widgets by specifying custom namespaces and paths.

**Example: Adding a Custom Widget Directory**

```php
'widgets' => [
    'namespace' => 'App\\Custom\\Widgets',
    'path' => app_path('Custom/Widgets'),
    'register' => [],
],
```

*Figure 12: Configuring Widgets*

![Figure 12: Configuring Widgets](placeholder-image.png)

<a name="middleware"></a>
### Middleware

```php
'middleware' => [
    'aura-admin' => [
        'web',
        'auth',
    ],

    'aura-guest' => [
        'web',
    ],

    'aura-base' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
],
```

- **Description**: Defines middleware stacks for different Aura CMS routes.
- **Options**:
  - **aura-admin**: Middleware applied to admin routes.
  - **aura-guest**: Middleware applied to guest routes.
  - **aura-base**: Base middleware applied to all Aura CMS requests.

**Example: Adding a Custom Middleware to Aura Admin Routes**

```php
'middleware' => [
    'aura-admin' => [
        'web',
        'auth',
        'custom.middleware',
    ],
],
```

*Figure 13: Configuring Middleware*

![Figure 13: Configuring Middleware](placeholder-image.png)

---

<a name="modifying-configuration"></a>
## Modifying Configuration

Aura CMS provides multiple ways to modify its configuration to suit your development workflow and project requirements.

<a name="using-the-install-config-command"></a>
### Using the Install Config Command

You can modify Aura CMS's configuration using the built-in Artisan command:

```bash
php artisan aura:install-config
```

This command will guide you through a series of prompts to update various configuration settings, including:

- Enabling or disabling teams.
- Modifying default features.
- Allowing or disallowing user registration.
- Customizing the default theme.

*Figure 14: Running the Install Config Command*

![Figure 14: Running the Install Config Command](placeholder-image.png)

<a name="manually-editing-configuration-files"></a>
### Manually Editing Configuration Files

For more granular control, you can directly edit the `config/aura.php` and `aura-settings.php` files.

**Example: Enabling Two-Factor Authentication**

```php
'auth' => [
    '2fa' => true,
],
```

*Figure 15: Manually Editing Configuration Files*

![Figure 15: Manually Editing Configuration Files](placeholder-image.png)

---

<a name="environment-variables"></a>
## Environment Variables

Aura CMS utilizes environment variables to manage certain configuration options. These variables are defined in your `.env` file and can override the default settings in `config/aura.php`.

**Common Environment Variables:**

- **AURA_PATH**: Sets the admin panel path.
- **AURA_DOMAIN**: Restricts Aura CMS to a specific domain.
- **AURA_TEAMS**: Enables or disables team functionality.
- **AURA_REGISTRATION**: Allows or disallows user registration.

**Example: Setting Environment Variables**

```dotenv
AURA_PATH=dashboard
AURA_DOMAIN=admin.yourdomain.com
AURA_TEAMS=false
AURA_REGISTRATION=true
```

*Figure 16: Setting Environment Variables*

![Figure 16: Setting Environment Variables](placeholder-image.png)

---

<a name="references"></a>
## References

- [Laravel Configuration Documentation](https://laravel.com/docs/configuration)
- [Livewire Documentation](https://laravel-livewire.com/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/)

*Video 1: Understanding Aura CMS Configuration*

![Video 1: Understanding Aura CMS Configuration](placeholder-video.mp4)

---

For further assistance or to report issues with the configuration, please refer to our [Support](support.md) section.
