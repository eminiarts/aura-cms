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
    'sidebar-size' => 'standard',    // standard, compact, or dense
    'sidebar-type' => 'dark',        // primary, light, or dark
    'sidebar-darkmode-type' => 'dark',
    'interface-density' => 'comfortable', // comfortable or compact
    'corner-radius' => 'rounded',    // rounded, subtle, or square
    'appearance-expert-mode' => false,

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
| Size | `sidebar-size` | `standard`, `compact`, `dense` |
| Sidebar | `sidebar-type` | `primary`, `light`, `dark` |
| Dark mode | `darkmode-type` | `auto`, `light`, `dark` |
| Sidebar dark mode | `sidebar-darkmode-type` | `primary`, `light`, `dark` |
| Interface density | `interface-density` | `comfortable`, `compact` |
| Corner radius | `corner-radius` | `rounded`, `subtle`, `square` |
| Primary color palette | `color-palette` | 39 presets or `custom` |
| Gray color palette | `gray-color-palette` | 14 presets or `custom` |
| Expert mode | `appearance-expert-mode` | `true`, `false` |

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

Guest pages (login, register, forgot and reset password) have no current team.
With teams enabled and exactly one team, they use that team's saved appearance.
With several teams they show no team's settings and use the theme
configuration.

The appearance fields (dark mode, sidebar, density, radius, palettes and the
expert values) preview live on the Settings page. "Discard changes" restores
the saved values; nothing is stored until you save.

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
applies whenever the admin renders in dark mode, including when dark mode is
forced.

The Size setting controls the sidebar width and item size. `standard` renders
an 18rem sidebar (`md:w-72`) with 36px items. `compact` and `dense` render a
14rem sidebar (`md:w-56`) with 32px and 28px items. The item size comes from
the `--aura-sidebar-*` variables listed under [Advanced appearance](#advanced-appearance).

## Advanced appearance

Three Settings fields control spacing, radius and sidebar size. Each preset
writes a set of CSS variables on `:root`, together with the fixed control
heights; `Aura\Base\ThemeTokens::design()` resolves them and
`layout/colors.blade.php` prints them. Components read the
variables, so one setting restyles the whole admin.

### Interface density (`interface-density`)

| Variable | `comfortable` (default) | `compact` | Expert setting | Read by |
| --- | --- | --- | --- | --- |
| `--aura-panel-padding` | `1.25rem` (20px) | `1rem` (16px) | `expert-panel-padding` | `.aura-card` (panels, dashboard panels and stat cards, widgets, the user Teams card), the Settings sub-panels (`.aura-group-section`), the login card (`.aura-login-card`: 1.5× on phones, 2× from `sm`), `.aura-card-bleed` (sparklines) |
| `--aura-panel-small-padding` | `0.5rem` (8px) | `0.375rem` (6px) | `expert-panel-small-padding` | `.aura-card-small` |
| `--aura-layout-gap` | `1.5rem` (24px) | `1rem` (16px) | `expert-layout-gap` | Space between panels and columns: `.aura-layout-row` / `.aura-layout-cell` (panels, tabs and fields in every form, view and Settings page, the resource editor, widgets), `.aura-layout-panel` (space below a panel), `.aura-widget-grid`, `.aura-layout-grid` (dashboard, record layout regions) |
| `--aura-section-gap` | `1rem` (16px) | `0.75rem` (12px) | `expert-section-gap` | Space below a group inside a panel: Settings sub-panels (`.aura-group-section`) and top-level Settings groups |

### Corner radius (`corner-radius`)

| Variable | `rounded` (default) | `subtle` | `square` | Expert setting | Read by |
| --- | --- | --- | --- | --- | --- |
| `--aura-panel-radius` | `0.75rem` (12px) | `0.375rem` (6px) | `0rem` | `expert-panel-radius` | `.aura-card`, `.aura-radius-panel` (table card, kanban column, bulk-action bar, dropdown, modal, toast, global search), the user menu popover (tippy theme `aura-small`), `.aura-group-section`, `.aura-login-card` |
| `--aura-control-radius` | `0.5rem` (8px) | `0.25rem` (4px) | `0rem` | `expert-control-radius` | `.aura-button`, `.aura-field` (inputs, selects, textareas, addons, tags, date picker, code editor), `.aura-card-small`, pagination, `.aura-radius-control` (list rows and icon tiles inside panels, kanban cards, close and dismiss buttons, search results), segmented controls (`.aura-segmented`, items 2px smaller), sidebar items, collapsed sidebar icons, dropdown child items and container, team switcher, search and toggle |

### Sidebar size (`sidebar-size`)

| Variable | `standard` (default) | `compact` | `dense` | Expert setting |
| --- | --- | --- | --- | --- |
| `--aura-sidebar-item-height` | `2.25rem` (36px) | `2rem` (32px) | `1.75rem` (28px) | `expert-sidebar-item-height` |
| `--aura-sidebar-item-padding-x` | `0.75rem` (12px) | `0.5rem` (8px) | `0.375rem` (6px) | `expert-sidebar-item-padding-x` |
| `--aura-sidebar-icon-size` | `1.25rem` (20px) | `1.125rem` (18px) | `1rem` (16px) | `expert-sidebar-icon-size` |
| `--aura-sidebar-group-gap` | `0.25rem` (4px) | `0.25rem` (4px) | `0.125rem` (2px) | `expert-sidebar-group-gap` |
| `--aura-sidebar-heading-gap` | `1.25rem` (20px) | `0.875rem` (14px) | `0.625rem` (10px) | `expert-sidebar-heading-gap` |

The sidebar variables apply inside `.aura-sidebar`. The sidebar width is not a
variable: 18rem for `standard`, 14rem for `compact` and `dense`.

### Control height

Buttons, inputs and selects share one height per size. No preset changes it:
density changes padding and gaps, not the size of a control.

| Variable | Value | Size | Expert setting | Read by |
| --- | --- | --- | --- | --- |
| `--aura-control-height-xs` | `1.75rem` (28px) | `xs`, `icon-xs` | — | `.aura-control-xs`, `.aura-control-min-xs` |
| `--aura-control-height-sm` | `2rem` (32px) | `sm` | — | `.aura-control-sm` |
| `--aura-control-height` | `2.25rem` (36px) | `base` | `expert-control-height` | `.aura-control-base`, `.aura-control-min-base`, the color field swatch |
| `--aura-control-height-lg` | `2.75rem` (44px) | `lg` | — | `.aura-control-lg` |
| `--aura-control-height-xl` | `3.25rem` (52px) | `xl` (buttons only) | — | `.aura-control-xl` |

`<x-aura::button>`, `<x-aura::input>` (and `input.text`, `input.email`, …) and
`<x-aura::input.select>` take the same `size` prop: `xs`, `sm`, `base` (the
default) or `lg`; buttons also have `xl` and `icon-xs`. The size adds an
`.aura-control-{size}` class, which sets the height and no vertical padding.
Buttons centre their content with flexbox and inputs and selects centre their
text, so the text size (12px at `xs`, 14px at `sm` and `base`, 16px at `lg`)
does not change the height. Icon-only buttons are square at the control height.
Fields that grow with their content (tags, multi-selects) use the height as a
minimum (`.aura-control-min-*`).

Use the classes in your own views to line up with Aura's controls:

```html
<select class="aura-field aura-control-sm …">…</select>
<button class="aura-button aura-control-base px-4 …">Export</button>
```

### Expert mode

With `appearance-expert-mode` on, each expert setting above replaces its
variable with a whole pixel value from 0 to 96 (other values are ignored).
`expert-control-height` changes the base control height only; the xs, sm, lg
and xl heights stay fixed. Expert mode also exposes six light and six dark sidebar colors
(`expert-light-sidebar-background`, `-hover`, `-dropdown`, `-text`, `-icon`,
`-icon-hover`, and the same with `expert-dark-`) as hex values. They write
`--sidebar-bg`, `--sidebar-bg-hover`, `--sidebar-bg-dropdown`,
`--sidebar-text`, `--sidebar-icon` and `--sidebar-icon-hover` for light mode
and under `.dark`.

The expert values start from the selected presets, and changing a preset
updates the values it governs. "Restore expert defaults" resets them to the
presets. Turning expert mode off renders the presets unchanged and keeps the
expert values for later.

```php
'theme' => [
    'corner-radius' => 'subtle',
    'appearance-expert-mode' => true,
    'expert-panel-radius' => 10,   // --aura-panel-radius: 10px
],
```

### Layout gaps and alignment

Panels, tabs, tables and fields sit in rows whose items set their own
percentage width (the field `style.width`). Each item (`.aura-layout-cell`) is
padded by half of `--aura-layout-gap` and the row (`.aura-layout-row`) pulls
itself out by the same amount, so two 50% panels still fit and sit one layout
gap apart. The outer edges of the first and last item line up with the page
title at every density. Rows nest: the fields inside a panel use the same
gutter, so they start exactly at the panel padding, and a nested has-many table
lines up with the fields above it.

Use the classes in your own views to follow the settings:

```html
<div class="flex flex-wrap aura-layout-row">
    <div class="w-1/2 aura-layout-cell"><div class="aura-card">…</div></div>
    <div class="w-1/2 aura-layout-cell"><div class="aura-card">…</div></div>
</div>
<div class="grid grid-cols-3 aura-layout-grid">…</div>
<div class="p-4 ring-1 aura-radius-panel">A floating surface</div>
<span class="p-2 aura-radius-control">An icon tile inside a panel</span>
```

Panels and the surfaces that float above the page take the panel radius.
Small things inside a panel take the control radius. Pills and avatars stay
round.

### What the tokens do not cover

Plugin views (media library, notifications, blocks) and a host's own views
use their own classes, as does a grid view that a resource defines itself.
They follow the settings only if they use the classes above.

### Style guide

`/admin/styleguide` renders every component with the live tokens. Its Design
tokens section lists the table above from `ThemeTokens::design()` together with
the value this page actually uses. URL parameters make a render repeatable:

| Parameter | Values | Effect |
| --- | --- | --- |
| `mode` | `light`, `dark` | Render the style guide content in that mode. `light` removes the admin's `dark` class while the page is open. |
| `section` | a section id, such as `buttons` or `table` | Render only that section, without the page header. |
| `density` | `comfortable`, `compact` | Preview a density preset on the style guide only. |
| `radius` | `rounded`, `subtle`, `square` | Preview a radius preset. |
| `sidebar` | `standard`, `compact`, `dense` | Preview a sidebar size preset. |

Previews are never saved.

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

### Host stylesheets on Aura pages

Aura's stylesheet is a complete Tailwind build. When a host also loads its own
Tailwind build on Aura pages, two builds share one page, and for two selectors
with the same specificity the stylesheet that loads later wins. A published
`layout/app.blade.php` that calls `@vite(...)` after `@auraStyles` loads the
host CSS last.

Aura protects the theme tokens against this:

- The rules for `.aura-card`, `.aura-card-small`, `.aura-button`,
  `.aura-field`, the `.aura-control-*` heights, the layout gutters
  (`.aura-layout-*`), `.aura-radius-panel`, `.aura-radius-control`, the
  sidebar items, dropdown triggers, search and collapse toggles, and
  `.aura-sidebar-groups` use a
  doubled class (`.aura-button.aura-button`). A host `.rounded-lg`, `.p-4` or
  `.h-10` cannot override the radius, padding, gap or height of the Advanced
  appearance presets.
- These elements carry no `rounded-*`, height or vertical padding utility that
  competes with the token rule.

Aura cannot protect its responsive and dark variants. A host base utility
beats an Aura `md:` or `dark:` variant of the same property: host `.flex-col`
beats Aura's `md:flex-row`, `.grid-cols-1` beats `sm:grid-cols-2`, and
`.bg-white` beats `dark:bg-gray-800`. Media queries add no specificity, and
Aura's `dark:` variant compiles to `:where(.dark, .dark *)`, which adds none
either. The problem shows up when the host build has the base class but not
the variant, for example after an Aura update when the host CSS was not
rebuilt. The table toolbar (`.aura-table-toolbar`) and the Settings sub-panels
(`.aura-group-section`) use component classes instead of utility pairs, so a
host stylesheet cannot stack the toolbar or keep a light border in dark mode.

Recommended host setup:

1. Do not load the host's front-end build on Aura pages. Put admin-only CSS in
   `resources/views/components/layouts/aura-head.blade.php`. Aura includes
   that view in the head before its own stylesheet, so Aura wins every tie.
2. If you publish the layout, keep the host `@vite(...)` call before
   `@auraStyles`. Use `@push('styles')` only for CSS that must override Aura.
3. If the host CSS has to load after Aura's, scope it so it cannot match
   Aura's markup: a Tailwind `prefix` (for example `tw-`) or the `important`
   selector strategy with a root element of your own pages. If its `content`
   scans Aura's views instead, rebuild it after every Aura update.

A host on Tailwind 4 is not affected by load order: Tailwind 4 puts its
utilities in a cascade layer, and Aura's unlayered rules win over layered ones.

## Focused source references

For implementation details, start with the files relevant to your change:

| Area | Files |
| --- | --- |
| Defaults and validation | `config/aura.php`, `src/ThemeTokens.php` |
| Settings and navigation | `src/Livewire/Settings.php`, `src/Livewire/Navigation.php` |
| Theme styles | `resources/views/components/layout/colors.blade.php`, `resources/views/components/layout/styles.blade.php` |
| Login and icons | `resources/views/components/layout/login.blade.php`, `resources/views/components/layout/favicon.blade.php`, `resources/views/navigation/logo.blade.php` |
| Asset builds | `tailwind.config.js`, `vite.config.js` |
| Style guide | `src/Livewire/Styleguide.php`, `resources/views/livewire/styleguide.blade.php`, `resources/views/styleguide/sections/` |

Related guides:

- [Settings](/docs/settings)
- [Configuration](/docs/configuration)
- [Customizing views](/docs/customizing-views)
- [Teams](/docs/teams)
