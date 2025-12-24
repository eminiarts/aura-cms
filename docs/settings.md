# Settings in Aura CMS

Aura CMS provides a settings system for managing theme and appearance configurations. Settings are stored in the database using the `Option` resource and can be configured per-team when teams are enabled.

## Table of Contents

- [Overview](#overview)
- [Accessing Settings](#accessing-settings)
- [Settings UI](#settings-ui)
- [Available Settings](#available-settings)
- [Configuration File](#configuration-file)
- [Option Resource](#option-resource)
- [Customizing the Settings Component](#customizing-the-settings-component)
- [Programmatic Access](#programmatic-access)
- [Best Practices](#best-practices)

## Overview

Aura CMS settings work in two layers:

1. **Configuration File** (`config/aura.php`): Static default settings
2. **Database Settings** (`options` table): Dynamic runtime settings stored via the `Option` resource

The Settings UI allows super admins to customize theme and appearance options that override the defaults from the configuration file.

## Accessing Settings

The Settings page is accessible at `/admin/settings` (or your configured admin path).

**Requirements:**
- User must be authenticated
- User must be a Super Admin (`isSuperAdmin()` returns `true`)
- The `settings` feature must be enabled in config

```php
// config/aura.php
'features' => [
    'settings' => true,  // Enable/disable settings page
],
```

## Settings UI

The Settings Livewire component (`Aura\Base\Livewire\Settings`) provides a form-based interface for configuring theme options. The component uses Aura's field system to render the settings form.

### Settings Structure

The settings form is organized into panels:

1. **Appearance Panel** - Logo configuration
2. **Sidebar Panel** - Sidebar size, type, and dark mode options
3. **Theme Panel** - Primary and gray color palette selection

## Available Settings

### Appearance

| Setting | Slug | Type | Description |
|---------|------|------|-------------|
| Logo | `logo` | Image | Main logo displayed in the admin |
| Logo Darkmode | `logo-darkmode` | Image | Logo variant for dark mode |

### Sidebar

| Setting | Slug | Type | Options |
|---------|------|------|---------|
| Size | `sidebar-size` | Radio | `standard`, `compact` |
| Sidebar | `sidebar-type` | Radio | `primary`, `light`, `dark` |
| Darkmode | `darkmode-type` | Radio | `auto`, `light`, `dark` |
| Sidebar Darkmode | `sidebar-darkmode-type` | Radio | `primary`, `light`, `dark` (shown only when darkmode is `auto`) |

### Theme Colors

| Setting | Slug | Type | Description |
|---------|------|------|-------------|
| Primary Color Palette | `color-palette` | Select | Choose from 35+ preset palettes or `custom` |
| Gray Color Palette | `gray-color-palette` | Select | Choose from 14 gray palettes or `custom` |

**Available Primary Color Palettes:**
`aura`, `red`, `orange`, `amber`, `yellow`, `lime`, `forest-green`, `green`, `emerald`, `mountain-meadow`, `teal`, `ocean-breeze`, `cyan`, `sky`, `blue`, `indigo`, `violet`, `purple`, `fuchsia`, `pink`, `rose`, `sandal`, `desert-sand`, `salmon`, `autumn-rust`, `slate`, `dark-slate`, `blackout`, `obsidian`, `amethyst`, `opal`, `gray`, `zinc`, `neutral`, `stone`, `sandstone`, `rose-quartz`, `olive`, `smaragd`, `custom`

**Available Gray Color Palettes:**
`slate`, `dark-slate`, `blackout`, `obsidian`, `amethyst`, `opal`, `gray`, `zinc`, `neutral`, `stone`, `sandstone`, `rose-quartz`, `olive`, `smaragd`, `custom`

### Custom Colors

When `custom` is selected for either palette, additional color fields appear:

**Primary Custom Colors:** `primary-25`, `primary-50`, `primary-100`, `primary-200`, `primary-300`, `primary-400`, `primary-500`, `primary-600`, `primary-700`, `primary-800`, `primary-900`, `primary-950`

**Gray Custom Colors:** `gray-25`, `gray-50`, `gray-100`, `gray-200`, `gray-300`, `gray-400`, `gray-500`, `gray-600`, `gray-700`, `gray-800`, `gray-900`, `gray-950`

## Configuration File

The main configuration file is located at `config/aura.php`. Here are the settings-related sections:

```php
return [
    // Admin path
    'path' => env('AURA_PATH', 'admin'),

    // Teams support
    'teams' => env('AURA_TEAMS', true),

    // Theme defaults
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

    // Feature flags
    'features' => [
        'global_search' => true,
        'bookmarks' => true,
        'last_visited_pages' => true,
        'notifications' => true,
        'plugins' => true,
        'settings' => true,
        'profile' => true,
        'create_resource' => true,
        'resource_view' => true,
        'resource_edit' => true,
        'resource_editor' => config('app.env') == 'local' ? true : false,
        'custom_tables_for_resources' => false,
    ],

    // Authentication options
    'auth' => [
        'registration' => env('AURA_REGISTRATION', true),
        'redirect' => '/admin',
        '2fa' => true,
        'user_invitations' => true,
        'create_teams' => true,
    ],

    // Media configuration
    'media' => [
        'disk' => 'public',
        'path' => 'media',
        'quality' => 80,
        'restrict_to_dimensions' => true,
        'max_file_size' => 10000,
        'generate_thumbnails' => true,
        'dimensions' => [
            ['name' => 'xs', 'width' => 200],
            ['name' => 'sm', 'width' => 600],
            ['name' => 'md', 'width' => 1200],
            ['name' => 'lg', 'width' => 2000],
            ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
        ],
    ],
];
```

### Environment Variables

```env
# Admin path (default: admin)
AURA_PATH=admin

# Domain restriction (optional)
AURA_DOMAIN=

# Enable/disable teams
AURA_TEAMS=true

# Enable/disable user registration
AURA_REGISTRATION=true
```

## Option Resource

Settings are stored using the `Option` resource (`Aura\Base\Resources\Option`), which uses a dedicated `options` table.

### Option Model Structure

```php
namespace Aura\Base\Resources;

class Option extends Resource
{
    public static $customTable = true;
    public static ?string $slug = 'option';
    public static string $type = 'Option';

    protected $table = 'options';
    protected $fillable = ['name', 'value', 'team_id'];
    protected $casts = ['value' => 'array'];
}
```

### How Settings Are Stored

- **With Teams Enabled**: Settings are stored with name `team.{team_id}.settings`
- **Without Teams**: Settings are stored with name `settings`

The `value` column contains a JSON object with all setting key-value pairs:

```json
{
    "darkmode-type": "auto",
    "sidebar-type": "primary",
    "color-palette": "aura",
    "gray-color-palette": "slate",
    "sidebar-size": "standard",
    "sidebar-darkmode-type": "dark",
    "logo": null,
    "logo-darkmode": null
}
```

### Retrieving Settings Programmatically

```php
use Aura\Base\Resources\Option;

// Get settings option
$settings = Option::where('name', 'settings')->first();

// Get a specific setting value
$colorPalette = $settings->value['color-palette'] ?? 'aura';

// With teams
$teamId = auth()->user()->current_team_id;
$settings = Option::where('name', "team.{$teamId}.settings")->first();
```

## Customizing the Settings Component

You can replace the default Settings component with your own by updating the config:

```php
// config/aura.php
'components' => [
    'settings' => App\Livewire\CustomSettings::class,
],
```

### Creating a Custom Settings Component

```php
namespace App\Livewire;

use Aura\Base\Livewire\Settings as BaseSettings;

class CustomSettings extends BaseSettings
{
    public static function getFields()
    {
        // Get the default fields
        $fields = parent::getFields();

        // Add custom fields
        $fields[] = [
            'type' => 'Aura\\Base\\Fields\\Panel',
            'name' => 'Custom Settings',
            'slug' => 'panel-custom',
        ];

        $fields[] = [
            'name' => 'Custom Option',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'custom-option',
        ];

        return $fields;
    }
}
```

## Programmatic Access

### Reading Current Theme Settings

```php
// Get theme setting with fallback to config
$colorPalette = config('aura.theme.color-palette'); // Returns 'aura' by default

// The Settings component loads these on mount
$defaultValues = [
    'darkmode-type' => config('aura.theme.darkmode-type'),
    'sidebar-type' => config('aura.theme.sidebar-type'),
    'color-palette' => config('aura.theme.color-palette'),
    'gray-color-palette' => config('aura.theme.gray-color-palette'),
    'sidebar-size' => config('aura.theme.sidebar-size'),
    'sidebar-darkmode-type' => config('aura.theme.sidebar-darkmode-type'),
];
```

### Updating Settings Programmatically

```php
use Aura\Base\Resources\Option;
use Illuminate\Support\Facades\Cache;

// Update settings
$option = Option::where('name', 'settings')->first();

if ($option) {
    $values = $option->value;
    $values['color-palette'] = 'blue';
    $option->update(['value' => $values]);

    // Clear cache to apply changes
    Cache::clear();
}
```

## Best Practices

### 1. Always Clear Cache After Changes

The Settings component clears the cache after saving. Do the same when updating programmatically:

```php
use Illuminate\Support\Facades\Cache;

$option->update(['value' => $newValues]);
Cache::clear();
```

### 2. Check Feature Flag Before Accessing

```php
if (config('aura.features.settings')) {
    // Settings feature is enabled
}
```

### 3. Respect Team Context

When working with team-specific settings:

```php
if (config('aura.teams')) {
    $teamId = auth()->user()->current_team_id;
    $optionName = "team.{$teamId}.settings";
} else {
    $optionName = 'settings';
}

$settings = Option::where('name', $optionName)->first();
```

### 4. Use Config Defaults

Always provide fallbacks to config when reading settings:

```php
$sidebarType = $settings['sidebar-type'] ?? config('aura.theme.sidebar-type');
```

## Troubleshooting

### Settings Page Returns 404

- Verify `config('aura.features.settings')` is `true`
- Check that the route is properly registered

### Settings Page Returns 403

- Confirm the user has super admin privileges
- Check `$user->isSuperAdmin()` returns `true`

### Changes Not Applying

- Clear the application cache: `php artisan cache:clear`
- Verify the Option record was updated in the database
- Check for JavaScript caching in the browser

### Settings Not Persisting

- Ensure the `options` table exists and has the correct structure
- Verify database write permissions
- Check for validation errors in the form

---

For more configuration options, see the [Configuration Guide](configuration.md).
For theme customization, see the [Themes Guide](themes.md).