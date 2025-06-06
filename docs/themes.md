# Themes

Aura CMS provides a powerful and flexible theming system that allows you to customize the appearance of your admin interface. This includes color schemes, dark mode support, and sidebar customization.

*Video 1: Customizing Your Aura Theme*

![Customizing Your Aura Theme](placeholder-video.mp4)

## Table of Contents

- [Introduction to Themes](#introduction-to-themes)
- [Theme Configuration](#theme-configuration)
  - [Default Configuration](#default-configuration)
  - [Settings Interface](#settings-interface)
- [Color Palettes](#color-palettes)
- [Dark Mode](#dark-mode)
- [Sidebar Customization](#sidebar-customization)
- [Custom Colors](#custom-colors)

## Theme Configuration

There are two ways to configure themes in Aura CMS:

### Default Configuration

The default theme settings can be defined in your `config/aura.php` file. For more information about configuration options, see the [Configuration documentation](configuration.md).

```php
// Default theme configuration in config/aura.php
return [
    'theme' => [
        'darkmode-type' => 'auto',    // auto, light, dark
        'sidebar-type' => 'primary',   // primary, light, dark
        'color-palette' => 'aura',     // see available palettes below
        'gray-color-palette' => 'slate',
        'sidebar-size' => 'standard',  // standard, compact
    ]
];
```

### Settings Interface

When `features.settings` is enabled in your Aura configuration, super admins can access the theme settings through the admin interface:

1. Navigate to Settings in the admin panel
2. Access the Theme section
3. Customize colors, dark mode, and sidebar options
4. Save changes

All settings modified through the interface are stored in the options table. If teams are enabled, these settings can be customized per team, allowing different teams to have their own theme configurations.

```php
// Example of enabling settings in config/aura.php
return [
    'features' => [
        'settings' => true,
    ],
];
```

The settings interface provides a user-friendly way to:
- Choose from predefined color palettes
- Create custom color schemes
- Configure dark mode behavior
- Customize sidebar appearance

## Color Palettes

Aura comes with a rich selection of pre-defined color palettes:

### Primary Colors

| Palette | Description |
|---------|-------------|
| `aura` | Default Aura blue theme |
| `red` | Vibrant red theme |
| `orange` | Warm orange theme |
| `amber` | Golden amber theme |
| `yellow` | Bright yellow theme |
| `lime` | Fresh lime theme |
| `forest-green` | Deep forest green |
| `green` | Classic green theme |
| `emerald` | Rich emerald theme |
| `mountain-meadow` | Natural mountain meadow |
| `teal` | Ocean teal theme |
| `cyan` | Bright cyan theme |
| `sky` | Light sky blue theme |
| `blue` | Classic blue theme |
| `indigo` | Deep indigo theme |
| `violet` | Rich violet theme |
| `purple` | Royal purple theme |
| `fuchsia` | Vibrant fuchsia theme |
| `pink` | Soft pink theme |
| `rose` | Romantic rose theme |

### Gray Palettes

| Palette | Description |
|---------|-------------|
| `slate` | Classic slate grays |
| `dark-slate` | Darker slate variation |
| `blackout` | High contrast black theme |
| `obsidian` | Deep obsidian theme |
| `amethyst` | Purple-tinted grays |
| `opal` | Soft opal grays |
| `zinc` | Industrial zinc theme |
| `neutral` | Pure neutral grays |
| `stone` | Warm stone grays |
| `sandstone` | Natural sandstone theme |

Each color palette includes 12 shades (25-950) for both primary and gray colors:

```php
$colorShades = [
    '25',  // Lightest
    '50',
    '100',
    '200',
    '300',
    '400',
    '500',
    '600',
    '700',
    '800',
    '900',
    '950', // Darkest
];
```

## Dark Mode

Aura supports three dark mode configurations:

1. Auto (default): Follows system preferences
   ```php
   'darkmode-type' => 'auto'
   ```

2. Light: Forces light mode
   ```php
   'darkmode-type' => 'light'
   ```

3. Dark: Forces dark mode
   ```php
   'darkmode-type' => 'dark'
   ```

When using auto mode, you can configure separate sidebar settings for dark mode:

```php
'sidebar-darkmode-type' => 'primary' // primary, light, dark
```

## Sidebar Customization

The sidebar can be customized in several ways:

### Size
```php
'sidebar-size' => 'standard' // standard, compact
```

### Type
```php
'sidebar-type' => 'primary' // primary, light, dark
```

### Variables

The sidebar appearance is controlled through CSS variables:

```css
:root {
    --sidebar-bg: var(--primary-600);
    --sidebar-bg-hover: var(--primary-500);
    --sidebar-bg-dropdown: var(--primary-700);
    --sidebar-text: var(--primary-400);
    --sidebar-icon: var(--primary-300);
    --sidebar-icon-hover: var(--primary-200);
}
```

## Custom Colors

You can define custom color palettes by selecting 'Custom' in the theme settings. This allows you to specify exact colors for each shade:

### Primary Colors
```php
'color-palette' => 'custom',
'primary-25' => '#fbfeff',
'primary-50' => '#f0f4fe',
'primary-100' => '#e0eafd',
// ... continue for all shades
```

### Gray Colors
```php
'gray-color-palette' => 'custom',
'gray-25' => '#fafafa',
'gray-50' => '#f5f5f5',
'gray-100' => '#ebebeb',
// ... continue for all shades
```

*Figure 1: Color Palette Structure*

![Color Palette Structure](placeholder-image.png)

### Custom Theme Example

```php
use Aura\Base\TransformColor;

$customColors = [
    'primary-25' => TransformColor::hexToRgb('#fbfeff'),
    'primary-50' => TransformColor::hexToRgb('#f0f4fe'),
    'primary-100' => TransformColor::hexToRgb('#e0eafd'),
    // ... additional shades
];

$customVariables = [
    '--sidebar-bg' => 'var(--primary-700)',
    '--sidebar-bg-hover' => 'var(--primary-600)',
    '--sidebar-bg-dropdown' => 'var(--primary-800)',
    '--sidebar-text' => 'var(--primary-200)',
    '--sidebar-icon' => 'var(--primary-100)',
    '--sidebar-icon-hover' => 'var(--primary-50)',
];
```

*Figure 2: Theme Customization Interface*

![Theme Customization Interface](placeholder-image.png)
