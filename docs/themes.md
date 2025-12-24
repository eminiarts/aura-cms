# Themes

Aura CMS provides a comprehensive theming system that gives you complete control over the visual appearance of your application. Built with Tailwind CSS and CSS variables, the theme system supports multiple color palettes, dark mode, and extensive customization options.

## Table of Contents

- [Overview](#overview)
- [Theme Architecture](#theme-architecture)
- [Configuration](#configuration)
- [Color Palettes](#color-palettes)
- [Dark Mode](#dark-mode)
- [Sidebar Themes](#sidebar-themes)
- [Custom Themes](#custom-themes)
- [CSS Variables](#css-variables)
- [Tailwind Integration](#tailwind-integration)
- [Login Page Customization](#login-page-customization)
- [Team-Specific Themes](#team-specific-themes)
- [Advanced Customization](#advanced-customization)
- [Theme Development](#theme-development)

## Overview

The Aura theme system provides:
- **35+ Primary Color Palettes**: Pre-designed color schemes
- **15 Gray Palettes**: Neutral color options
- **Dark Mode Support**: Auto, light, or dark modes
- **Sidebar Customization**: Independent sidebar theming
- **Custom Colors**: Create your own color schemes
- **Per-Team Themes**: Different themes for different teams
- **Login Page Customization**: Custom backgrounds and favicons
- **Live Preview**: Real-time theme updates

> 📹 **Video Placeholder**: [Overview of Aura's theme system showing color palette selection, dark mode switching, and live preview functionality]

## Theme Architecture

### Component Structure

```
Theme System
├── Configuration (config/aura.php)
├── Settings Component (Livewire)
├── Color Generation (Blade)
├── CSS Variables
├── Tailwind Config
└── Storage (Options Table)
```

### How Themes Work

1. **Configuration**: Default theme settings in `config/aura.php`
2. **Settings UI**: Admin interface for theme customization
3. **CSS Generation**: Dynamic CSS variables based on selected palette
4. **Application**: CSS classes and variables applied to UI
5. **Persistence**: Settings stored in database

## Configuration

### Default Configuration

Set default theme options in `config/aura.php`:

```php
return [
    'theme' => [
        // Primary color palette
        'color-palette' => 'aura',
        
        // Gray color palette
        'gray-color-palette' => 'slate',
        
        // Dark mode: auto, light, dark
        'darkmode-type' => 'auto',
        
        // Sidebar size: standard, compact
        'sidebar-size' => 'standard',
        
        // Sidebar theme: primary, light, dark
        'sidebar-type' => 'primary',
        
        // Dark mode sidebar theme
        'sidebar-darkmode-type' => 'dark',
        
        // Login page background image (path or false)
        'login-bg' => false,
        'login-bg-darkmode' => false,
        
        // Application favicon (path or false)
        'app-favicon' => false,
        'app-favicon-darkmode' => false,
    ],
];
```

### Enabling Theme Settings

Enable the settings interface for admins:

```php
// config/aura.php
return [
    'features' => [
        'settings' => true, // Enable settings UI
    ],
];
```

### Accessing Theme Settings

```php
use Aura\Base\Facades\Aura;

// Get current theme settings
$theme = Aura::getOption('theme');
$colorPalette = $theme['color-palette'] ?? 'aura';
$darkMode = $theme['darkmode-type'] ?? 'auto';

// Check if dark mode is active
$isDark = $darkMode === 'dark' || 
    ($darkMode === 'auto' && // Check system preference);
```

## Color Palettes

### Primary Color Palettes

Aura includes 35+ professionally designed primary color palettes:

| Palette | Description | Use Case |
|---------|-------------|----------|
| `aura` | Default Aura blue | Professional, corporate |
| `red` | Vibrant red | Alerts, urgency |
| `orange` | Warm orange | Energy, creativity |
| `amber` | Golden amber | Warmth, attention |
| `yellow` | Bright yellow | Optimism, clarity |
| `lime` | Fresh lime | Growth, freshness |
| `forest-green` | Deep forest green | Nature, stability |
| `green` | Classic green | Success, growth |
| `emerald` | Rich emerald | Luxury, balance |
| `mountain-meadow` | Natural meadow teal | Fresh, organic |
| `teal` | Ocean teal | Calm, sophisticated |
| `ocean-breeze` | Soft ocean blue | Tranquil, refreshing |
| `cyan` | Bright cyan | Modern, tech |
| `sky` | Light sky blue | Open, friendly |
| `blue` | Classic blue | Trust, reliability |
| `indigo` | Deep indigo | Wisdom, depth |
| `violet` | Rich violet | Creativity, luxury |
| `purple` | Royal purple | Premium, imaginative |
| `fuchsia` | Vibrant fuchsia | Bold, playful |
| `pink` | Soft pink | Gentle, caring |
| `rose` | Romantic rose | Elegant, passionate |

**Neutral/Earth Tone Palettes:**

| Palette | Description |
|---------|-------------|
| `sandal` | Warm sandy beige |
| `desert-sand` | Earthy desert tones |
| `salmon` | Soft coral salmon |
| `autumn-rust` | Rich autumn rust |

**Monochrome/Gray-Based Primary Palettes:**

| Palette | Description |
|---------|-------------|
| `slate` | Cool blue-gray |
| `dark-slate` | Deeper blue-gray |
| `blackout` | High contrast near-black |
| `obsidian` | Deep volcanic black |
| `amethyst` | Purple-tinted gray |
| `opal` | Soft blue-tinted gray |
| `gray` | Pure neutral gray |
| `zinc` | Industrial cool gray |
| `neutral` | Perfect neutral |
| `stone` | Warm stone gray |
| `sandstone` | Earthy warm neutral |
| `rose-quartz` | Pink-tinted gray |
| `olive` | Green-tinted gray |
| `smaragd` | Emerald-tinted gray |

### Gray Color Palettes

15 neutral color palettes for UI backgrounds and text:

| Palette | Description | Style |
|---------|-------------|-------|
| `slate` | Classic slate (default) | Cool blue-gray |
| `dark-slate` | Deeper slate | Deep cool gray |
| `blackout` | High contrast | Near black darks |
| `obsidian` | Deep obsidian | Rich volcanic black |
| `amethyst` | Purple-tinted | Warm purple gray |
| `opal` | Soft opal | Light blue-tinted |
| `gray` | Pure gray | True neutral |
| `zinc` | Industrial zinc | Cool industrial |
| `neutral` | Balanced neutral | Perfect neutral |
| `stone` | Warm stone | Warm earthy gray |
| `sandstone` | Natural sandstone | Earthy warm neutral |
| `rose-quartz` | Pink-tinted | Warm pink gray |
| `olive` | Green-tinted | Organic olive gray |
| `smaragd` | Emerald-tinted | Cool emerald gray |

### Color Shades

Each palette includes 12 precisely calculated shades:

```php
$shades = [
    '25'  => 'Lightest tint',
    '50'  => 'Very light',
    '100' => 'Light',
    '200' => 'Light medium',
    '300' => 'Medium light',
    '400' => 'Medium',
    '500' => 'Base color',
    '600' => 'Medium dark',
    '700' => 'Dark medium',
    '800' => 'Dark',
    '900' => 'Very dark',
    '950' => 'Darkest shade',
];
```

> 📹 **Video Placeholder**: [Interactive color palette selector showing all available palettes with live preview]

## Dark Mode

### Dark Mode Options

```php
// Auto mode - follows system preference
'darkmode-type' => 'auto'

// Force light mode
'darkmode-type' => 'light'

// Force dark mode
'darkmode-type' => 'dark'
```

### Implementation

Dark mode is implemented using:
- CSS `.dark` class on HTML element (using Tailwind's `selector` strategy)
- Tailwind's dark mode utilities (`dark:` prefix)
- CSS variables that adapt to theme
- System preference detection for `auto` mode

```html
<!-- Automatic dark mode classes -->
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Content adapts to theme -->
</div>
```

When `darkmode-type` is set to `auto`, the system detects the user's OS preference using `window.matchMedia('(prefers-color-scheme: dark)')`.

### JavaScript Detection

```javascript
// Check dark mode status
const isDarkMode = () => {
    return document.documentElement.classList.contains('dark');
};

// Listen for theme changes
window.addEventListener('theme-changed', (event) => {
    console.log('Theme changed to:', event.detail);
});
```

## Sidebar Themes

### Sidebar Types

1. **Primary** (default)
   ```css
   --sidebar-bg: var(--primary-600);
   --sidebar-text: var(--primary-100);
   ```

2. **Light**
   ```css
   --sidebar-bg: var(--gray-100);
   --sidebar-text: var(--gray-700);
   ```

3. **Dark**
   ```css
   --sidebar-bg: var(--gray-900);
   --sidebar-text: var(--gray-100);
   ```

### Sidebar Configuration

```php
// Standard sidebar
'sidebar-size' => 'standard',
'sidebar-type' => 'primary',

// Compact sidebar with dark theme
'sidebar-size' => 'compact',
'sidebar-type' => 'dark',

// Different sidebar for dark mode
'sidebar-darkmode-type' => 'dark',
```

### Sidebar CSS Variables

Each color palette defines its own sidebar variables. The default values are:

```css
:root {
    /* Background colors */
    --sidebar-bg: var(--primary-600);
    --sidebar-bg-hover: var(--primary-500);
    --sidebar-bg-dropdown: var(--primary-700);
    
    /* Text colors */
    --sidebar-text: var(--primary-400);
    
    /* Icon colors */
    --sidebar-icon: var(--primary-300);
    --sidebar-icon-hover: var(--primary-200);
}
```

Color palettes can override these defaults. For example, the `aura` palette uses:

```css
:root {
    --sidebar-bg: var(--primary-700);
    --sidebar-bg-hover: var(--primary-600);
    --sidebar-bg-dropdown: var(--primary-800);
}
```

## Custom Themes

### Creating Custom Colors

```php
// In Settings UI or config
'color-palette' => 'custom',
'primary-25' => '#fefce8',
'primary-50' => '#fef3c7',
'primary-100' => '#fde68a',
'primary-200' => '#fcd34d',
'primary-300' => '#fbbf24',
'primary-400' => '#f59e0b',
'primary-500' => '#d97706',
'primary-600' => '#b45309',
'primary-700' => '#92400e',
'primary-800' => '#78350f',
'primary-900' => '#451a03',
'primary-950' => '#281203',
```

### Using TransformColor

```php
use Aura\Base\TransformColor;

// Convert hex to RGB for CSS variables
$rgb = TransformColor::hexToRgb('#3B82F6');
// Returns: "59 130 246"

// Use in CSS
$css = "--primary-500: {$rgb};";
```

### Custom Theme Class

```php
namespace App\Themes;

use Aura\Base\TransformColor;

class BrandTheme
{
    public static function colors()
    {
        return [
            'primary' => [
                '25' => TransformColor::hexToRgb('#fefce8'),
                '50' => TransformColor::hexToRgb('#fef3c7'),
                // ... all shades
            ],
            'gray' => [
                '25' => TransformColor::hexToRgb('#fafafa'),
                '50' => TransformColor::hexToRgb('#f4f4f5'),
                // ... all shades
            ],
        ];
    }
    
    public static function sidebarVariables()
    {
        return [
            '--sidebar-bg' => 'var(--primary-800)',
            '--sidebar-bg-hover' => 'var(--primary-700)',
            '--sidebar-text' => 'var(--primary-100)',
            // ... other variables
        ];
    }
}
```

## CSS Variables

### Generated Variables

Aura generates CSS variables for all color shades:

```css
:root {
    /* Primary colors */
    --primary-25: 251 254 255;
    --primary-50: 240 244 254;
    --primary-100: 224 234 253;
    /* ... through 950 */
    
    /* Gray colors */
    --gray-25: 250 250 250;
    --gray-50: 245 245 245;
    --gray-100: 235 235 235;
    /* ... through 950 */
}
```

### Using Variables in CSS

```css
/* Direct usage */
.custom-element {
    background-color: rgb(var(--primary-500));
    color: rgb(var(--gray-100));
}

/* With opacity */
.transparent-bg {
    background-color: rgb(var(--primary-500) / 0.5);
}

/* In Tailwind classes */
.custom-class {
    @apply bg-primary-500 text-gray-100;
}
```

## Tailwind Integration

### Tailwind Configuration

Aura uses a custom function to support opacity modifiers with CSS variables:

```javascript
// tailwind.config.js
function withOpacityValue(variable) {
    return ({ opacityValue }) => {
        if (opacityValue === undefined) {
            return `rgb(var(${variable}))`
        }
        return `rgb(var(${variable}) / ${opacityValue})`
    }
}

module.exports = {
    darkMode: 'selector',
    
    theme: {
        extend: {
            colors: {
                // Sidebar colors from CSS variables
                sidebar: {
                    'bg': withOpacityValue('--sidebar-bg'),
                    'bg-hover': withOpacityValue('--sidebar-bg-hover'),
                    'bg-dropdown': withOpacityValue('--sidebar-bg-dropdown'),
                    'icon': withOpacityValue('--sidebar-icon'),
                    'icon-hover': withOpacityValue('--sidebar-icon-hover'),
                    'text': withOpacityValue('--sidebar-text'),
                },
                // Primary colors (shades 25-900)
                primary: {
                    '25': withOpacityValue('--primary-25'),
                    '50': withOpacityValue('--primary-50'),
                    '100': withOpacityValue('--primary-100'),
                    // ... through 900
                },
                // Gray colors (shades 25-900)
                gray: {
                    '25': withOpacityValue('--gray-25'),
                    '50': withOpacityValue('--gray-50'),
                    '100': withOpacityValue('--gray-100'),
                    // ... through 900
                },
            },
        },
    },
};
```

### Using Theme Colors

```html
<!-- Primary colors -->
<div class="bg-primary-500 hover:bg-primary-600">
    <span class="text-primary-100">Themed text</span>
</div>

<!-- Gray colors -->
<div class="bg-gray-50 dark:bg-gray-900">
    <p class="text-gray-700 dark:text-gray-300">Adaptive text</p>
</div>

<!-- With opacity -->
<div class="bg-primary-500/20 border-primary-500/50">
    Semi-transparent elements
</div>
```

## Login Page Customization

Customize the login page appearance with custom backgrounds and favicons:

### Login Background

```php
// config/aura.php
'theme' => [
    // Custom background image for login page
    'login-bg' => '/path/to/background.jpg',
    
    // Different background for dark mode
    'login-bg-darkmode' => '/path/to/dark-background.jpg',
],
```

Set to `false` to use the default gradient background.

### Application Favicon

```php
// config/aura.php
'theme' => [
    // Custom favicon
    'app-favicon' => '/path/to/favicon.ico',
    
    // Different favicon for dark mode (optional)
    'app-favicon-darkmode' => '/path/to/dark-favicon.ico',
],
```

## Team-Specific Themes

### Configuration

When teams are enabled, each team can have custom themes:

```php
// Enable teams and settings
return [
    'teams' => true,
    'features' => [
        'settings' => true,
    ],
];
```

### Accessing Team Themes

```php
// Get current team's theme
$teamTheme = auth()->user()->currentTeam->getOption('theme');

// Set team theme
auth()->user()->currentTeam->setOption('theme', [
    'color-palette' => 'emerald',
    'darkmode-type' => 'dark',
]);
```

### Theme Hierarchy

1. Team theme (if set and teams enabled)
2. User preference (if implemented)
3. Global theme (default)

## Advanced Customization

### Custom Theme Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aura\Base\Facades\Aura;

class ThemeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Add custom palettes
        $this->app->booted(function () {
            $this->registerCustomPalettes();
        });
        
        // Override theme logic
        Aura::macro('getTheme', function () {
            // Custom theme resolution logic
            return $this->customThemeLogic();
        });
    }
    
    protected function registerCustomPalettes()
    {
        // Register brand colors
        config([
            'aura.palettes.brand' => [
                'name' => 'Brand Colors',
                'colors' => [
                    '25' => '#fefce8',
                    // ... all shades
                ],
            ],
        ]);
    }
}
```

### Theme Events

```php
// Listen for theme changes
Event::listen('theme.changed', function ($theme) {
    // Clear caches, update assets, etc.
    Cache::tags(['theme'])->flush();
});

// Dispatch theme change
event('theme.changed', $newTheme);
```

### Dynamic Theme Loading

```php
// In a middleware
class LoadTheme
{
    public function handle($request, $next)
    {
        $theme = $this->resolveTheme($request);
        
        View::share('theme', $theme);
        
        return $next($request);
    }
    
    protected function resolveTheme($request)
    {
        // Check for theme in query string (preview)
        if ($request->has('theme')) {
            return $this->loadTheme($request->theme);
        }
        
        // Load user/team theme
        return Aura::getOption('theme');
    }
}
```

## Theme Development

### Creating a Theme Package

```php
// src/MyThemeServiceProvider.php
namespace Acme\MyTheme;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MyThemeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('my-theme')
            ->hasConfigFile()
            ->hasViews();
    }
    
    public function packageBooted()
    {
        // Register theme
        $this->registerTheme();
        
        // Add to theme selector
        $this->addToThemeSelector();
    }
    
    protected function registerTheme()
    {
        config([
            'aura.themes.my-theme' => [
                'name' => 'My Custom Theme',
                'colors' => $this->getColors(),
                'sidebar' => $this->getSidebarConfig(),
            ],
        ]);
    }
}
```

### Theme Assets

```php
// Publish theme assets
public function boot()
{
    $this->publishes([
        __DIR__.'/../dist/theme.css' => public_path('vendor/my-theme/theme.css'),
    ], 'my-theme-assets');
    
    // Auto-inject theme CSS
    Aura::macro('injectThemeAssets', function () {
        return '<link href="/vendor/my-theme/theme.css" rel="stylesheet">';
    });
}
```

### Theme Preview

```php
// Preview component
class ThemePreview extends Component
{
    public $theme;
    
    public function mount($theme)
    {
        $this->theme = $theme;
    }
    
    public function render()
    {
        return view('my-theme::preview', [
            'colors' => $this->generatePreviewColors(),
        ]);
    }
    
    protected function generatePreviewColors()
    {
        // Generate CSS for preview
        $css = ":root {\n";
        
        foreach ($this->theme['colors'] as $shade => $color) {
            $rgb = TransformColor::hexToRgb($color);
            $css .= "    --primary-{$shade}: {$rgb};\n";
        }
        
        $css .= "}";
        
        return $css;
    }
}
```

> 📹 **Video Placeholder**: [Creating a custom theme from scratch, including color selection, testing, and packaging]

### Pro Tips

1. **Use CSS Variables**: Always use CSS variables for theme colors
2. **Test Dark Mode**: Ensure all elements work in both light and dark modes
3. **Maintain Contrast**: Follow WCAG guidelines for color contrast
4. **Cache Themes**: Cache generated CSS for performance
5. **Preview Mode**: Implement theme preview before applying
6. **Gradual Migration**: Use both old and new theme systems during transition
7. **Document Colors**: Provide color documentation for designers
8. **Accessibility**: Test themes with accessibility tools

The theme system provides complete control over your application's appearance while maintaining consistency and ease of use across teams and installations.
