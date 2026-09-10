# Themes

Use the Settings page to choose color palettes, adjust the sidebar, and upload
logos. Configure semantic colors and fonts in your application's
`config/aura.php`. Aura combines these settings with the package defaults when
it renders the theme.

## Configuration defaults

The published `config/aura.php` contains these theme defaults:

```php
'theme' => [
    'color-palette' => 'aura',
    'gray-color-palette' => 'slate',
    'darkmode-type' => 'auto',       // auto, light, or dark
    'sidebar-size' => 'standard',    // standard or compact
    'sidebar-type' => 'dark',        // primary, light, or dark
    'sidebar-darkmode-type' => 'dark',

    'login-bg' => false,
    'login-bg-darkmode' => false,
    'app-favicon' => false,
    'app-favicon-darkmode' => false,
],
```

The Settings page is enabled by default. Disable it with:

```php
'features' => [
    'settings' => false,
],
```

Open `/admin/settings` to change the theme. If you changed the admin path in
`aura.path`, the Settings page uses that path instead. Only super admins can
access the page. Other users receive a 403 response, and disabling the feature
makes the page return a 404.

## The Settings screen

The form contains these fields:

| Field | Slug | Options |
| --- | --- | --- |
| Logo | `logo` | Uploaded image |
| Logo dark mode | `logo-darkmode` | Uploaded image |
| Size | `sidebar-size` | `standard`, `compact` |
| Sidebar | `sidebar-type` | `primary`, `light`, `dark` |
| Dark mode | `darkmode-type` | `auto`, `light`, `dark` |
| Sidebar dark mode | `sidebar-darkmode-type` | `primary`, `light`, `dark` |
| Primary color palette | `color-palette` | 39 presets or `custom` |
| Gray color palette | `gray-color-palette` | 14 presets or `custom` |

The Sidebar dark mode field appears only when dark mode is set to auto.
Choosing a custom palette reveals color fields for its 12 shades: 25, 50, 100,
200, 300, 400, 500, 600, 700, 800, 900, and 950.

On the first visit, Aura creates a settings record using your configured dark
mode, sidebar appearance and size, and primary and gray palettes. It also
includes the sidebar's dark mode appearance. Saving the form stores all its
fields, including uploaded image IDs and custom colors, then clears the
application cache.

The record name depends on the teams setting:

- With teams enabled, it is `team.{teamId}.settings`.
- With teams disabled, it is `settings`.

Read the saved values with the `settings` option name:

```php
use Aura\Base\Facades\Aura;

$settings = Aura::getOption('settings');
$palette = $settings['color-palette'] ?? config('aura.theme.color-palette');
$darkMode = $settings['darkmode-type'] ?? config('aura.theme.darkmode-type');
```

When teams are enabled, this reads the current team's settings. It returns an
empty array if no record exists, without falling back to global settings.
Aura caches these reads for one hour. The layout uses the theme configuration
as a fallback when it generates CSS.

The Settings screen has no live preview. Reload after saving to apply the new
styles and dark mode setting.

## Color palettes

The primary palette offers 39 presets. Use these slugs when configuring a
palette in code:

```text
aura, red, orange, amber, yellow, lime, forest-green, green, emerald,
mountain-meadow, teal, ocean-breeze, cyan, sky, blue, indigo, violet,
purple, fuchsia, pink, rose, sandal, desert-sand, salmon, autumn-rust,
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc, neutral,
stone, sandstone, rose-quartz, olive, smaragd
```

The gray palette offers these 14 presets:

```text
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc, neutral,
stone, sandstone, rose-quartz, olive, smaragd
```

Each preset provides all 12 shades through the `--primary-*` and `--gray-*`
CSS variables. For custom palettes, supply hex colors. Aura converts them to
space-separated RGB values:

```php
'theme' => [
    'color-palette' => 'custom',
    'primary-500' => '#3c73f2',
    'primary-600' => '#1f55e9',

    'gray-color-palette' => 'custom',
    'gray-500' => '#64748b',
],
```

Provide all 12 shades for each custom palette. The conversion helper returns
channel strings without `rgb(...)`:

```php
use Aura\Base\TransformColor;

TransformColor::hexToRgb('#3c73f2'); // "60 115 242"
```

## Semantic colors and fonts

Semantic colors let you style elements by their purpose, such as a panel
background or warning message. Define them under `theme.colors` to share the
same colors between Aura's CSS and your application's stylesheet.

Use space-separated RGB values or a single CSS variable reference such as
`var(--primary-600)`. Do not wrap values in `rgb(...)`. The following example
shows every supported color name, with separate values for light and dark mode:

```php
'theme' => [
    'font' => [
        'family' => [
            'ui-sans-serif',
            'system-ui',
            'sans-serif',
        ],
        'stylesheet' => false,
    ],

    'colors' => [
        'light' => [
            'primary' => 'var(--primary-600)',
            'background' => '255 255 255',
            'panel' => '250 250 250',
            'border' => '228 228 231',
            'text' => '24 24 27',
            'muted' => '82 82 91',
            'success' => '22 163 74',
            'warning' => '217 119 6',
            'danger' => '220 38 38',
        ],
        'dark' => [
            'primary' => 'var(--primary-600)',
            'background' => '9 9 11',
            'panel' => '24 24 27',
            'border' => '63 63 70',
            'text' => '244 244 245',
            'muted' => '161 161 170',
            'success' => '22 163 74',
            'warning' => '217 119 6',
            'danger' => '220 38 38',
        ],
    ],
],
```

The layout exposes the font and semantic colors as CSS variables:

```css
--aura-font-sans
--aura-color-primary
--aura-color-background
--aura-color-panel
--aura-color-border
--aura-color-text
--aura-color-muted
--aura-color-success
--aura-color-warning
--aura-color-danger
```

Light values are declared on `:root`. Dark values replace the semantic
variables under `.dark`. The package Tailwind configuration maps the same
values to `aura.*` colors:

```html
<div class="bg-aura-panel text-aura-text border border-aura-border">
    Themed content
</div>
```

The `font-sans` utility uses `var(--aura-font-sans)` before Tailwind's default
sans-serif fallbacks. The default system stack makes no font request. To load
a custom font, place its stylesheet in the host application's public
directory and set a local path:

```php
'font' => [
    'family' => ['Acme Sans', 'sans-serif'],
    'stylesheet' => 'fonts/acme-sans.css',
],
```

The font stylesheet path cannot contain remote or data URLs, backslashes,
control characters, or parent-directory segments. Aura ignores invalid font
families and uses its default font stack if none remain. Each invalid semantic
color falls back to its default independently.

## Dark mode

Set `darkmode-type` to one of three values:

- `dark` always uses dark mode.
- `light` always uses light mode.
- `auto` follows the operating system's color preference when the page loads.

Aura applies dark mode by adding the `dark` class to the document root and
removes it for light mode. Its Tailwind 3 configuration uses
`darkMode: 'selector'`, so dark mode utilities respond to that class.

The layout checks the setting on page load. Saving a new setting does not
update the class or dispatch a `theme-changed` event. Reload the page to apply
the change.

Favicon switching is separate from the page dark mode setting. Its script
listens to the operating system color-scheme media query and swaps the light
and dark favicon paths when that preference changes.

## Sidebar

The sidebar uses these generated variables when its type is `primary`:

```css
--sidebar-bg
--sidebar-bg-hover
--sidebar-bg-dropdown
--sidebar-text
--sidebar-icon
--sidebar-icon-hover
```

The fallback values are:

```css
--sidebar-bg: var(--primary-600);
--sidebar-bg-hover: var(--primary-500);
--sidebar-bg-dropdown: var(--primary-700);
--sidebar-text: var(--primary-400);
--sidebar-icon: var(--primary-300);
--sidebar-icon-hover: var(--primary-200);
```

Palettes can override these defaults. The aura palette uses primary shades
700, 600, 800, 400, 300, and 200 for the six variables, in the order shown above.
Other palettes use the fallback values unless they provide their own overrides.

`sidebar-type` selects the light-mode CSS:

- `primary` uses the sidebar variables.
- `light` uses `bg-gray-50` with dark text.
- `dark` uses `#18181b` with light text.

The sidebar's dark mode appearance, configured with `sidebar-darkmode-type`,
applies only when the main dark mode setting is auto. Forcing light or dark
mode does not add the corresponding sidebar class.

The Size setting currently has a limitation. Both standard and compact use
the same sidebar width, `md:w-56`, because the navigation template checks only
whether the value is non-empty. It uses the wider `md:w-72` width only for an
empty value.

## Logos, login backgrounds, and favicons

The Settings form stores uploaded attachment IDs under `logo` and
`logo-darkmode`. When both are set, the navigation renders both images and
uses the sidebar type classes to show the light or dark variant. With no
uploaded sidebar logo, it renders the component named by
`config('aura.views.logo')`.

The login layout also renders `config('aura.views.logo')`. The default
component is `aura::application-logo`. A host can point the `logo` view
configuration at its own Blade component.

Set login background paths in `config/aura.php`. Use root-relative public URLs:

```php
'theme' => [
    'login-bg' => '/images/login-light.jpg',
    'login-bg-darkmode' => '/images/login-dark.jpg',
],
```

With both paths set, the light image is used first and the dark image replaces
it when the `dark` class is present at `DOMContentLoaded`. The login view does
not react to later system or Settings changes. With only one path set, that
image is used in every mode. With neither path set, the login view uses its
default gradient background.

The favicon values are inserted into the `<link rel="icon">` tag. Use explicit
root-relative paths when customizing them:

```php
'theme' => [
    'app-favicon' => '/vendor/aura/public/favicon-32x32.png',
    'app-favicon-darkmode' => '/vendor/aura/public/favicon-darkmode-32x32.png',
],
```

The default favicon settings are `false`, which currently produces an empty
icon URL. Set them to `null` or remove them to use the bundled icons. To use
custom icons, provide paths as shown above.

If you render the login layout without passing an `$appSettings` array, it
uses the theme configuration. Check `/login` after changing the backgrounds.

## View overrides and extension points

The current `views` configuration contains these keys:

```php
'views' => [
    'layout' => 'aura::layout.app',
    'login-layout' => 'aura::layout.login',
    'logo' => 'aura::application-logo',
],
```

`login-layout` wraps the guest authentication pages. `logo` controls the
application logo component used by the login layout and the sidebar fallback.
The main Livewire pages use `aura::components.layout.app` directly.

For smaller additions, Aura's layout includes these extension points:

- `@stack('styles')` is rendered in the document head.
- `@stack('scripts')` is rendered before the closing body tag.
- `components.layouts.aura-head`, when present in the host app, is included
  in the document head.
- `resources/views/navigation/before.blade.php` and
  `resources/views/navigation/after.blade.php` are included around the
  navigation menu.

Publish package views before replacing a whole Aura Blade file:

```bash
php artisan vendor:publish --tag=aura-views
```

Published files go under `resources/views/vendor/aura` and override the
package views.

## Compiled assets and host frontend builds

Aura's package CSS and JavaScript are compiled in the Aura package checkout.
The package `vite.config.js` writes the normal build to `resources/dist` and
the library build to `resources/libs`:

```bash
npm run build
npm run build:lib
```

Those files are separate from a host application's `resources/css/app.css`,
`resources/js/app.js`, and `public/build` output. The host Vite build does not
recompile Aura's package source.

The `@auraStyles` and `@auraScripts` directives resolve the package entries
from the `vendor/aura` Vite build directory. In a consuming Laravel
application, publish the compiled package tree after installing or updating
Aura:

```bash
php artisan aura:publish
```

The command copies the package `dist`, `libs`, and public files into
`public/vendor/aura` and verifies the Vite manifest. The alternative publish
tag is:

```bash
php artisan vendor:publish --tag=aura-assets --force
```

If a host view needs a Tailwind utility that Aura's package config does not
generate, add that utility to the host's own Tailwind configuration. The
shipped config maps `aura.*`, `sidebar.*`, and primary and gray shades
`25` through `900`. It emits the `950` CSS variables, but it does not map
`primary-950` or `gray-950` to Tailwind classes by default.

## Focused source references

For implementation details, start with the files relevant to your change:

| Area | Files |
| --- | --- |
| Defaults and validation | `config/aura.php`, `src/ThemeTokens.php` |
| Settings and navigation | `src/Livewire/Settings.php`, `src/Livewire/Navigation.php` |
| Theme styles | `resources/views/components/layout/colors.blade.php`, `resources/views/components/layout/styles.blade.php` |
| Login and icons | `resources/views/components/layout/login.blade.php`, `resources/views/components/layout/favicon.blade.php`, `resources/views/navigation/logo.blade.php` |
| Asset builds | `tailwind.config.js`, `vite.config.js` |

Related guides:

- [Settings](/docs/settings)
- [Configuration](/docs/configuration)
- [Customizing views](/docs/customizing-views)
- [Teams](/docs/teams)
