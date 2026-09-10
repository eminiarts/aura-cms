# Themes

Aura resolves theme values from the package defaults, the host application's
`config/aura.php`, and the saved Settings option. The saved option controls the
palette, sidebar, and uploaded logos. Semantic colors and fonts are configured
in `config/aura.php`.

## Configuration defaults

The published `config/aura.php` contains the theme defaults. The current main
defaults are:

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

The page is mounted at `/{config('aura.path')}/settings`, which is
`/admin/settings` when the default path is used. `Aura\Base\Livewire\Settings`
returns a 404 when the feature is disabled and a 403 unless the current user
is a super admin.

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

The sidebar dark mode field is shown only when `darkmode-type` is `auto`.
When either palette is `custom`, the form shows color fields for shades
`25`, `50`, `100`, `200`, `300`, `400`, `500`, `600`, `700`, `800`, `900`, and
`950`.

On the first visit, the component creates one `Option` record with six values
from `config/aura.php`: `darkmode-type`, `sidebar-type`, `color-palette`,
`gray-color-palette`, `sidebar-size`, and `sidebar-darkmode-type`. After saving,
the record contains the complete form field set, including uploaded image IDs
and custom color values. `save()` clears the application cache after updating
the record.

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

`Aura::getOption('settings')` returns the current team's record when teams are
enabled and returns an empty array when no record exists. It does not read a
global record as a fallback for a team that has no settings. Reads are cached
for one hour. The layout falls back to `config('aura.theme')` when it resolves
the CSS values.

The Settings screen has no live preview. Reload the page after saving to make
the new layout CSS and dark mode class apply.

## Color palettes

The primary palette select contains these 39 preset slugs:

```text
aura, red, orange, amber, yellow, lime, forest-green, green, emerald,
mountain-meadow, teal, ocean-breeze, cyan, sky, blue, indigo, violet,
purple, fuchsia, pink, rose, sandal, desert-sand, salmon, autumn-rust,
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc, neutral,
stone, sandstone, rose-quartz, olive, smaragd
```

The gray palette select contains these 14 preset slugs:

```text
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc, neutral,
stone, sandstone, rose-quartz, olive, smaragd
```

Every preset defines RGB channel values for the 12 shades listed above. Aura
emits those values as `--primary-*` and `--gray-*` CSS variables. The custom
palette fields accept hex colors and the renderer converts them to the same
space-separated RGB form:

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

The `theme.colors` configuration controls semantic values shared by Aura's
package CSS and a host stylesheet. Aura accepts either a space-separated RGB
channel string or a single CSS variable reference such as
`var(--primary-600)`. Do not include `rgb(...)` around the value.

The supported semantic names are `primary`, `background`, `panel`, `border`,
`text`, `muted`, `success`, `warning`, and `danger`. Configure light and dark
values separately:

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

`resources/views/components/layout/colors.blade.php` emits these variables:

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

Aura rejects remote URLs, data URLs, backslashes, control characters, and
parent-directory segments in `font.stylesheet`. Invalid semantic color values
fall back per token. Invalid font-family entries are dropped, and the package
font stack is used when no valid family remains.

## Dark mode

`darkmode-type` accepts three values:

- `dark` adds the `dark` class to the document root.
- `light` removes the `dark` class.
- `auto` checks `window.matchMedia('(prefers-color-scheme: dark)')`.

Aura's Tailwind 3 configuration uses `darkMode: 'selector'`, so `dark:*`
utilities respond to the `dark` class on `<html>`. The inline script in the
layout evaluates the setting when the page loads. It does not dispatch a
`theme-changed` event or update the class when the setting changes in the
Settings component. Reload after changing the setting.

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

Palettes can override those values. The `aura` palette uses primary shades
`700`, `600`, `800`, `400`, `300`, and `200` for the six variables. A palette
without an override uses the fallback values.

`sidebar-type` selects the light-mode CSS:

- `primary` uses the sidebar variables.
- `light` uses `bg-gray-50` with dark text.
- `dark` uses `#18181b` with light text.

When `darkmode-type` is `auto`, `sidebar-darkmode-type` selects the matching
dark-mode class. The navigation markup does not add that class for forced
`light` or forced `dark` mode.

The Settings form labels `sidebar-size` as `standard` and `compact`. The
current navigation template tests the value for truthiness, so both non-empty
values choose the `md:w-56` width. The wider `md:w-72` branch is used only
when the value is empty. See the review report for this source defect.

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

The shipped `false` favicon defaults pass through the null-coalescing lookup
and produce an empty `href`. Set the keys to `null`, remove them, or provide
paths to use the bundled or a custom icon.

The login layout falls back to `aura.theme` values when no `$appSettings`
array is passed to it. Verify `/login` after configuring background overrides.

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

The implementation for this page is in `config/aura.php`,
`src/ThemeTokens.php`, `src/Livewire/Settings.php`,
`src/Livewire/Navigation.php`,
`resources/views/components/layout/colors.blade.php`,
`resources/views/components/layout/styles.blade.php`,
`resources/views/components/layout/login.blade.php`,
`resources/views/components/layout/favicon.blade.php`,
`resources/views/navigation/logo.blade.php`, `tailwind.config.js`, and
`vite.config.js`.

Related guides:

- [Settings](/docs/settings)
- [Configuration](/docs/configuration)
- [Customizing views](/docs/customizing-views)
- [Teams](/docs/teams)
