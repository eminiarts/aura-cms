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

<a name="authentication-settings"></a>
### Authentication Settings

```php
'auth' => [
    'registration' => env('AURA_REGISTRATION', true),
    'redirect' => '/admin',
    '2fa' => true,
    'user_invitations' => true,
    'create_teams' => true,
],
```

Configure authentication behavior for different scenarios:

#### Public Registration

```php
// Open registration (SaaS, community sites)
'auth' => [
    'registration' => true,
    'user_invitations' => true,
    'create_teams' => true,
],

// Closed system (internal tools, client projects)
'auth' => [
    'registration' => false,
    'user_invitations' => true,  // Admin can invite
    'create_teams' => false,
],
```

#### Security Settings

```php
// High security environment
'auth' => [
    '2fa' => true,              // Require 2FA
    'registration' => false,     // No public registration
    'redirect' => '/admin/dashboard',
],

// Development environment
'auth' => [
    '2fa' => false,             // Optional 2FA
    'registration' => true,      // Easy testing
],
```

#### Custom Redirects

```php
'redirect' => '/admin',              // Default
'redirect' => '/admin/dashboard',    // Straight to dashboard
'redirect' => '/admin/projects',     // Project-focused app
'redirect' => '/',                   // Frontend integration
```

> **Pro Tip**: Use environment variables for registration control:
> ```env
> # .env
> AURA_REGISTRATION=false  # Production
> AURA_REGISTRATION=true   # Development
> ```

<a name="media-configuration"></a>
### Media Configuration

```php
'media' => [
    'disk' => 'public',                  // Storage disk
    'path' => 'media',                   // Upload directory
    'quality' => 80,                     // JPEG quality (1-100)
    'restrict_to_dimensions' => true,    // Enforce max dimensions
    'max_file_size' => 10000,           // KB (10MB default)
    'generate_thumbnails' => true,       // Auto-generate sizes
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

#### Storage Configurations

**Local Storage (Default)**
```php
'media' => [
    'disk' => 'public',
    'path' => 'media',
],
```

**Amazon S3**
```php
'media' => [
    'disk' => 's3',
    'path' => 'aura-cms/media',
    'quality' => 85,  // Balance quality/size
],

// In .env
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

**DigitalOcean Spaces**
```php
'media' => [
    'disk' => 'spaces',
    'path' => 'uploads',
],
```

#### Custom Thumbnail Sizes

```php
// E-commerce focused
'dimensions' => [
    ['name' => 'thumb', 'width' => 150, 'height' => 150],
    ['name' => 'product', 'width' => 800, 'height' => 800],
    ['name' => 'zoom', 'width' => 1600, 'height' => 1600],
    ['name' => 'mobile', 'width' => 400],
    ['name' => 'desktop', 'width' => 1920],
],

// Blog focused
'dimensions' => [
    ['name' => 'card', 'width' => 400, 'height' => 300],
    ['name' => 'hero', 'width' => 1920, 'height' => 600],
    ['name' => 'social', 'width' => 1200, 'height' => 630],
],
```

#### Performance Optimization

```php
// High-traffic site
'media' => [
    'disk' => 's3',
    'quality' => 75,              // Lower quality for speed
    'max_file_size' => 5000,      // 5MB limit
    'generate_thumbnails' => true,
    'restrict_to_dimensions' => [
        'max_width' => 3000,
        'max_height' => 3000,
    ],
],
```

> **Pro Tip**: Use queues for thumbnail generation on large files:
> ```env
> QUEUE_CONNECTION=redis
> ```

---

<a name="settings-configuration"></a>
## Settings Configuration (aura-settings.php)

The `config/aura-settings.php` file handles advanced configuration for paths, auto-discovery, and middleware.

<a name="resource-field-paths"></a>
### Resource & Field Paths

```php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
        'register' => [],  // Additional resources
    ],
    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
        'register' => [],  // Additional fields
    ],
],
```

#### Custom Organization

```php
// Domain-driven structure
'paths' => [
    'resources' => [
        'namespace' => 'Domain\\Resources',
        'path' => base_path('domain/Resources'),
        'register' => [
            // Manually register if needed
            Domain\Blog\Resources\Article::class,
            Domain\Shop\Resources\Product::class,
        ],
    ],
],

// Module-based structure
'paths' => [
    'resources' => [
        'namespace' => 'Modules\\Aura\\Resources',
        'path' => base_path('modules/aura/Resources'),
    ],
],
```

#### Multiple Paths (Advanced)

```php
// Register resources from multiple locations
'resources' => [
    'register' => [
        // Core resources
        ...glob(app_path('Aura/Resources/*.php')),
        // Module resources
        ...glob(base_path('modules/*/Resources/*.php')),
        // Package resources
        Vendor\Package\Resources\CustomResource::class,
    ],
],
```

<a name="widget-configuration"></a>
### Widget Configuration

```php
'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
    'register' => [],
],
```

Widgets are auto-discovered from the configured path:

```php
// app/Aura/Widgets/RevenueWidget.php
namespace App\Aura\Widgets;

use Aura\Base\Widgets\Widget;

class RevenueWidget extends Widget
{
    public string $component = 'revenue-chart';
    public string $title = 'Monthly Revenue';
    public string $description = 'Revenue trends';
    public array $size = ['width' => 6, 'height' => 4];
    
    public function data(): array
    {
        return [
            'revenue' => Order::byMonth()->sum('total'),
        ];
    }
}
```

**Manual Widget Registration:**
```php
'widgets' => [
    'register' => [
        App\Analytics\Widgets\UserGrowth::class,
        App\Sales\Widgets\TopProducts::class,
        Package\Widgets\ExternalWidget::class,
    ],
],
```

<a name="middleware-stacks"></a>
### Middleware Stacks

```php
'middleware' => [
    'aura-admin' => [         // Authenticated admin routes
        'web',
        'auth',
    ],
    'aura-guest' => [         // Public routes (login, register)
        'web',
    ],
    'aura-base' => [          // Base stack for all routes
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
],
```

#### Custom Middleware Examples

**Adding Security Headers:**
```php
'middleware' => [
    'aura-admin' => [
        'web',
        'auth',
        App\Http\Middleware\SecurityHeaders::class,
        App\Http\Middleware\LogActivity::class,
    ],
],
```

**API Integration:**
```php
'middleware' => [
    'aura-api' => [           // Custom API stack
        'api',
        'auth:sanctum',
        'throttle:api',
        App\Http\Middleware\ValidateApiKey::class,
    ],
],
```

**Subscription Checking:**
```php
'middleware' => [
    'aura-admin' => [
        'web',
        'auth',
        App\Http\Middleware\EnsureSubscriptionActive::class,
        App\Http\Middleware\CheckUserPermissions::class,
    ],
],
```

---

<a name="environment-variables"></a>
## Environment Variables

Aura CMS supports environment-specific configuration through `.env` files:

### Core Variables

```env
# Admin Panel Access
AURA_PATH=admin                    # URL path for admin panel
AURA_DOMAIN=admin.mydomain.com     # Restrict to specific domain

# Features
AURA_TEAMS=true                    # Enable/disable teams
AURA_REGISTRATION=false            # Public registration

# Application
APP_NAME="My Aura CMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mydomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aura_cms
DB_USERNAME=root
DB_PASSWORD=secret

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
```

### Environment-Specific Configs

```php
// config/aura.php
'features' => [
    'resource_editor' => env('APP_DEBUG', false),
    'debug_bar' => env('APP_DEBUG', false),
],

'media' => [
    'disk' => env('MEDIA_DISK', 'public'),
    'max_file_size' => env('MEDIA_MAX_SIZE', 10000),
],
```

> **Pro Tip**: Use different `.env` files for each environment:
> - `.env.local` - Local development
> - `.env.staging` - Staging server
> - `.env.production` - Production server

---

<a name="performance-optimization"></a>
## Performance Optimization

### Configuration Caching

```bash
# Cache all configuration (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches (after changes)
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimized Settings

```php
// Production optimizations
'features' => [
    'resource_editor' => false,      // Disable visual editor
    'last_visited_pages' => false,   // Reduce DB queries
],

'media' => [
    'quality' => 75,                 // Balance quality/size
    'generate_thumbnails' => true,   // Use queue worker
],

// Use Redis for better performance
'cache' => ['driver' => 'redis'],
'session' => ['driver' => 'redis'],
'queue' => ['default' => 'redis'],
```

---

<a name="common-configuration-scenarios"></a>
## Common Configuration Scenarios

### 1. Blog Platform

```php
// config/aura.php
'teams' => false,                    // Single author
'features' => [
    'global_search' => true,
    'resource_editor' => false,
    'create_teams' => false,
],
'auth' => [
    'registration' => false,         // No public authors
    'user_invitations' => false,
],
```

### 2. SaaS Application

```php
'teams' => true,                     // Multi-tenant
'features' => [
    'notifications' => true,
    'custom_tables_for_resources' => true,
],
'auth' => [
    'registration' => true,          // Self-service
    '2fa' => true,                   // Security
    'create_teams' => true,
],
```

### 3. Enterprise CMS

```php
'domain' => 'cms.company.com',       // Dedicated domain
'teams' => true,                     // Departments
'features' => [
    'resource_editor' => false,      // IT controlled
    'plugins' => false,              // No custom code
],
'auth' => [
    'registration' => false,         // IT provisioned
    'user_invitations' => true,      // Controlled access
    '2fa' => true,                   // Required
],
```

### 4. Development Agency

```php
'teams' => true,                     // Per client
'features' => [
    'resource_editor' => true,       // Rapid development
    'custom_tables_for_resources' => true,
],
'theme' => [
    'color-palette' => 'brand',      // Agency branding
],
```

---

<a name="configuration-best-practices"></a>
## Configuration Best Practices

### 1. Use Environment Variables

```php
// ✅ Good
'path' => env('AURA_PATH', 'admin'),

// ❌ Bad
'path' => 'admin',  // Hard-coded
```

### 2. Document Custom Settings

```php
// config/aura.php

/*
|--------------------------------------------------------------------------
| Custom Widget Configuration
|--------------------------------------------------------------------------
|
| These settings control our custom dashboard widgets.
| Updated: 2024-01-15 by John Doe
|
*/
'custom_widgets' => [
    'sales_dashboard' => true,
    'analytics_panel' => env('ENABLE_ANALYTICS', false),
],
```

### 3. Group Related Settings

```php
// Group by feature
'ecommerce' => [
    'enable_cart' => true,
    'enable_checkout' => true,
    'payment_providers' => ['stripe', 'paypal'],
],
```

### 4. Version Control Considerations

```gitignore
# .gitignore
.env
.env.backup
config/aura-local.php  # Local overrides
```

---

<a name="configuration-troubleshooting"></a>
## Troubleshooting

### Configuration Not Taking Effect

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild caches
php artisan config:cache
```

### Finding Configuration Issues

```bash
# Dump current configuration
php artisan config:show aura

# Test specific values
php artisan tinker
>>> config('aura.teams')
>>> config('aura.features.global_search')
```

### Common Issues

1. **Changes not reflected**: Clear config cache
2. **ENV not working**: Check `.env` file exists and is readable
3. **Routes not found**: Check `path` and `domain` settings
4. **Features missing**: Verify feature flags are enabled
5. **Theme not changing**: Clear view cache

> **Need Help?** 
> - Check our [FAQ](troubleshooting.md)
> - Visit the [Community Forum](https://forum.aura-cms.com)
> - Report issues on [GitHub](https://github.com/eminiarts/aura-cms)

---

**Next Steps:**
- 📚 [Create your first Resource](resources.md)
- 🎨 [Customize the theme](themes.md)
- 🔌 [Develop a plugin](plugins.md)
