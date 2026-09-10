# Configuration

This page describes the configuration shipped on the current `main` branch. The public Composer package is `v1.0.0-beta.4`. Its installer has older behavior around environment-backed values. Follow [Installation](/docs/installation) for the beta-specific setup notes.

<a id="configuration-overview"></a>

## Configuration overview

Aura reads two package files from the host application:

- `config/aura.php` controls routes, teams, components, resources, theme, features, authentication, reporting and media.
- `config/aura-settings.php` controls discovery paths and the middleware arrays used by Aura's routes.

The package defaults apply without publishing either file. Publish them when you need to change a value:

```bash
php artisan vendor:publish --tag=aura-config
```

`php artisan aura:install` publishes both files before it asks whether to run `aura:install-config`. See [Installation](/docs/installation) for the complete installer flow.

<a id="quick-configuration"></a>

## Quick configuration

After publishing `config/aura.php`, you can use the interactive command:

```bash
php artisan aura:install-config
```

The command changes teams, registration, feature flags and selected theme scalars. It does not configure every key in the file. See [the installer section](#configuration-command) for the exact behavior.

<a id="main-configuration"></a>

## Main configuration

### Path and domain

```php
// config/aura.php
'path' => env('AURA_PATH', 'admin'),
'domain' => env('AURA_DOMAIN'),
```

`path` is the URL prefix for the authenticated admin group. The default routes include `/admin`, `/admin/profile`, `/admin/settings` and each registered resource route. `domain` restricts that group to one host. Leave it empty to accept every host.

The authentication routes are outside this domain and prefix group. They use root paths such as `/login`, `/register` when registration is enabled, `/forgot-password`, `/reset-password` and the two-factor paths. The default `auth.redirect` is derived from `AURA_PATH`, so Aura's auth-controller redirects follow the admin path. If you replace `auth.redirect` with a literal, keep it aligned with your chosen path. Aura's two-factor response also honors `aura.auth.redirect` and the intended URL.

### Teams

```php
'teams' => env('AURA_TEAMS', true),
```

Teams are enabled by default. The value is read when Aura's migration runs. With teams enabled, the migration creates the team tables and team columns. With teams disabled, team-specific schema is omitted, `TeamScope` does nothing, and the team switcher and team registration routes are unavailable. Roles use the global Role Catalog in teams-off mode.

Set the value before the first migration. Changing it on an existing installation requires a deliberate schema and data migration. Do not treat it as a runtime toggle for an already-migrated database. See [Teams](/docs/teams) and [Installation](/docs/installation#without-teams).

### Authentication settings

The keys below live under `auth` in `config/aura.php`.

| Key | Default | What it controls |
| --- | --- | --- |
| `registration` | `env('AURA_REGISTRATION', true)` | Registers the public registration routes and controls the registration link. Disabled registration returns 404. |
| `redirect` | `'/'.trim(env('AURA_PATH', 'admin'), '/')` | The destination used by Aura's login, registration, password confirmation, email verification and invitation-registration controllers. The default is `/admin`. |
| `2fa` | `true` | Registers Aura's two-factor challenge, enable, confirm, disable, QR-code, secret-key and recovery-code routes. |
| `user_invitations` | `true` | Allows invitation registration and shows invitation actions in the User and Team Invitation resources. The invitation registration routes also require `teams`. |
| `invitation_expiry` | `7` | Number of days that the signed invitation link remains valid. |
| `create_teams` | `env('AURA_CREATE_TEAMS', true)` | The `TeamPolicy::create()` check. Set it to `false` to prevent team creation, including for Global Admins. |

`redirect` is a package setting. It does not change Laravel's unrelated application defaults. The package's auth routes use the value directly.

### Feature flags

The shipped `features` array contains these keys. Unknown keys have no package behavior.

| Key | Default | What it controls |
| --- | --- | --- |
| `dashboard` | `true` | The Dashboard navigation item. The `/admin` route still registers when the item is hidden. |
| `global_search` | `true` | The global-search component, its navigation button and the search UI. |
| `bookmarks` | `true` | The bookmark button. The button also requires `global_search`. |
| `notifications` | `true` | The notification component and its navigation button. |
| `plugins` | `true` | The Plugins quick action on the dashboard. The `/admin/plugins` route still registers when this is `false`. |
| `settings` | `true` | The Settings navigation item and component. The component returns 404 when disabled and requires a Super Admin. |
| `profile` | `true` | The profile navigation item and component. The component rejects access when disabled. |
| `create_resource` | `true` | The Create Resource navigation item and dashboard quick actions. The navigation item is restricted to Super Admins, and the modal rejects other users. |
| `resource_editor` | `config('app.env') == 'local'` | The Resource Editor. The route and component require the `local` or `testing` environment, this flag and a Super Admin. Vendor resources cannot be edited. |
| `custom_tables_for_resources` | `false` | The Resource Editor migration listener. See [Custom tables](#custom-tables). |
| `legacy_fields_append` | `true` | Whether the `fields` accessor is appended during resource array and JSON serialization. Set it to `false` and call `$resource->append('fields')` only where serialized field values are needed. |

The default `resource_editor` expression enables the flag only when the application environment is `local`. The environment check remains active even if you set the flag to `true` elsewhere.

### Components

`components` maps the top-level Livewire pages and the media-manager modal to classes:

```php
'components' => [
    'dashboard' => Aura\Base\Livewire\Dashboard::class,
    'profile' => Aura\Base\Livewire\Profile::class,
    'settings' => Aura\Base\Livewire\Settings::class,
    'media-manager' => Aura\Base\Livewire\MediaManager::class,
],
```

The first three entries are used by the `/admin`, `/admin/profile` and `/admin/settings` routes. `media-manager` is resolved by the `aura::media-manager` Livewire modal. Replace an entry with a subclass or a compatible component to customize that page.

### Built-in resources

`resources` maps Aura's built-in resource keys to their classes:

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

The `team` and `team-invitation` classes are registered only when teams are enabled. To replace a built-in resource, extend it and update the matching entry. Resource fields use the static array returned by `getFields()`:

```php
namespace App\Aura\Resources;

use Aura\Base\Resources\User as BaseUser;

class User extends BaseUser
{
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Department',
                'slug' => 'department',
            ],
        ]);
    }
}
```

`php artisan aura:customize` can generate the subclass and update the matching config entry. See [Creating resources](/docs/creating-resources).

<a id="view-customization"></a>

### Views

The shipped `views` block contains three keys:

```php
'views' => [
    'layout' => 'aura::layout.app',
    'login-layout' => 'aura::layout.login',
    'logo' => 'aura::application-logo',
],
```

- `layout` is read by the package's config-driven Blade views, including the dashboard, profile and team views. The default Livewire dashboard and profile routes render `aura::components.layout.app` directly.
- `login-layout` wraps login, registration, password reset and forgot-password pages.
- `logo` is rendered inside the login layout.

The package does not read `views.dashboard`, `views.index`, `views.view`, `views.create`, `views.edit` or `views.navigation`. Publish and override the relevant Blade view when you need to change those pages.

The `layout` value is a Blade component alias, `aura::layout.app`. Full-page Livewire components use the view name `aura::components.layout.app`. These names serve different consumers. See [Customizing views](/docs/customizing-views) for the matching override.

<a id="theme-configuration"></a>

### Theme

The theme defaults live under `theme` in `config/aura.php`:

```php
'theme' => [
    'color-palette' => 'aura',
    'gray-color-palette' => 'slate',
    'darkmode-type' => 'auto',
    'font' => [
        'family' => ['ui-sans-serif', 'system-ui', 'sans-serif'],
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
    'sidebar-size' => 'standard',
    'sidebar-type' => 'dark',
    'sidebar-darkmode-type' => 'dark',
    'login-bg' => false,
    'login-bg-darkmode' => false,
    'app-favicon' => false,
    'app-favicon-darkmode' => false,
],
```

`ThemeTokens::resolve()` merges package defaults, the host's `config('aura.theme')`, then the settings stored in the `options` table. A Super Admin can edit settings at `/admin/settings` when `features.settings` is enabled. With teams enabled, the record name is `team.{id}.settings`. Without teams, it is `settings`.

The Settings page initializes and edits these theme preferences in the option record:

- `darkmode-type`
- `sidebar-type`
- `color-palette`
- `gray-color-palette`
- `sidebar-size`
- `sidebar-darkmode-type`

It also stores logo values and the shade values used by a custom primary or gray palette. Those values are option data, not keys under `theme`.

`darkmode-type` accepts `auto`, `light` or `dark`. `sidebar-size` accepts `standard` or `compact`. `sidebar-type` and `sidebar-darkmode-type` accept `primary`, `light` or `dark`.

The `colors.light` and `colors.dark` maps define the nine semantic CSS variables `primary`, `background`, `panel`, `border`, `text`, `muted`, `success`, `warning` and `danger`. Each value must be an RGB channel string such as `24 24 27` or a variable reference such as `var(--primary-600)`. Invalid values fall back to the package default. The renderer emits these values as `--aura-color-*` variables.

`font.family` accepts an array or a comma-separated string. `font.stylesheet` accepts a host-local public path such as `fonts/brand.css`. External URLs, backslashes, control characters and `..` path segments are rejected. The default system stack makes no font request.

The built-in primary palettes are `aura`, `red`, `orange`, `amber`, `yellow`, `lime`, `forest-green`, `green`, `emerald`, `mountain-meadow`, `teal`, `ocean-breeze`, `cyan`, `sky`, `blue`, `indigo`, `violet`, `purple`, `fuchsia`, `pink`, `rose`, `sandal`, `desert-sand`, `salmon`, `autumn-rust`, `slate`, `dark-slate`, `blackout`, `obsidian`, `amethyst`, `opal`, `gray`, `zinc`, `neutral`, `stone`, `sandstone`, `rose-quartz`, `olive`, `smaragd` and `custom`. Gray palettes are `slate`, `dark-slate`, `blackout`, `obsidian`, `amethyst`, `opal`, `gray`, `zinc`, `neutral`, `stone`, `sandstone`, `rose-quartz`, `olive`, `smaragd` and `custom`. The `custom` choice reads its shade values from the Settings option record.

`login-bg` and `login-bg-darkmode` are read by the login layout. Set one or both to a public image path. When both are set, the layout switches to the dark image when the document has the `dark` class. The published defaults are `false`.

`app-favicon` and `app-favicon-darkmode` are read by the favicon component. Set them to public paths. The published `false` values render an empty favicon URL. If only the light favicon is set, it is used for both modes.

<a id="media-configuration"></a>

### Media

The media settings are read from `config/aura.php`:

| Key | Default | What it controls |
| --- | --- | --- |
| `disk` | `public` | The filesystem disk used for uploads, thumbnails and image responses. A non-public disk uses that disk's URL resolver. |
| `path` | `media` | The directory inside the configured disk where uploads are stored. |
| `quality` | `80` | JPEG quality used when encoding thumbnails. |
| `restrict_to_dimensions` | `true` | Whether the image route accepts only the width and height pairs listed in `dimensions`. |
| `max_file_size` | `10000` | Maximum upload size in kilobytes for each file. The uploader reads this key for both server validation and its browser policy. |
| `generate_thumbnails` | `true` | Whether the queued image-thumbnail job pre-renders each configured size after an image is saved. The image route can still generate thumbnails on demand. |
| `dimensions` | `xs` 200, `sm` 600, `md` 1200, `lg` 2000, `thumbnail` 600x600 | Named dimensions used by `Attachment::thumbnail()` and thumbnail generation. |

The shipped `max_file_size` value is 10,000 KB, about 9.8 MiB. The uploader accepts at most 20 files per batch and uses a fixed extension allowlist: `jpg`, `jpeg`, `png`, `gif`, `webp`, `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `txt`, `csv`, `zip`, `mp4`, `mov`, `avi`, `mp3` and `wav`. SVG is blocked. Aura's thumbnail service uses Intervention Image 3 with the GD driver, so PHP GD must be enabled.

<a id="reporting-configuration"></a>

### Reporting

The shipped reporting block is:

```php
'reporting' => [
    'projection' => [
        'reads_enabled' => false,
    ],
],
```

Typed projection reads are not available in this build. Leave `reads_enabled` set to `false`. Setting it to `true` does not enable the projection stack. It only changes the error text when a report requests a meta-backed numeric metric.

<a id="settings-configuration"></a>

## `aura-settings.php`

### Discovery paths

The default paths are:

```php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
    ],
    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
    ],
],

'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
],
```

At boot, Aura scans each configured path and maps relative file names to the configured namespace. Resource discovery keeps only classes that extend `Aura\Base\Resource`. Field and widget discovery expects class files in the directory, so keep non-class files out of those paths.

The field path is `aura-settings.paths.fields.path`. It is not a key under `config('aura')`. The published `widgets.register` array is not consumed by the current discovery code. Register extra classes from a service provider with `Aura::registerResources()`, `Aura::registerFields()` or `Aura::registerWidgets()`.

### Middleware stacks

The default stacks are:

```php
'middleware' => [
    'aura-admin' => ['web', 'auth'],
    'aura-guest' => ['web'],
    'aura-base' => [
        Aura\Base\Http\Middleware\EncryptCookies::class,
        Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        Illuminate\Session\Middleware\StartSession::class,
        Illuminate\View\Middleware\ShareErrorsFromSession::class,
        Aura\Base\Http\Middleware\VerifyCsrfToken::class,
        Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
],
```

`aura-guest` is applied to Aura's authentication routes. `aura-admin` is applied to the impersonation route and the admin route group. Append host middleware to `aura-admin` when it must run on every authenticated Aura route. `aura-base` is defined for host use but is not attached to a package route by default.

<a id="environment-variables"></a>

## Environment variables

Aura reads these environment variables through `config/aura.php`:

| Variable | Config key | Default | Effect |
| --- | --- | --- | --- |
| `AURA_PATH` | `path` and the default `auth.redirect` | `admin` | Changes the admin URL prefix and the default post-authentication path. |
| `AURA_DOMAIN` | `domain` | empty | Restricts the admin route group to one host. |
| `AURA_TEAMS` | `teams` | `true` | Selects the teams-on or teams-off schema and behavior at migration and runtime. |
| `AURA_REGISTRATION` | `auth.registration` | `true` | Enables or disables public registration routes and the registration link. |
| `AURA_CREATE_TEAMS` | `auth.create_teams` | `true` | Allows or prevents team creation in `TeamPolicy`. |

There are no package-supported `AURA_FEATURES_*`, `AURA_THEME_*` or `AURA_MEDIA_*` variables. Change those values in the published config file. Clear Laravel's config cache after changing environment-backed configuration:

```bash
php artisan config:clear
```

<a id="configuration-command"></a>

## `aura:install-config`

Run this command after publishing `config/aura.php`:

```bash
php artisan aura:install-config
```

In interactive mode it:

- asks whether to use teams;
- optionally asks about each boolean entry in `features`;
- asks whether to allow public registration; and
- optionally asks for `color-palette`, `gray-color-palette`, `darkmode-type`, `sidebar-size` and `sidebar-type`.

It skips nested theme arrays (`font` and `colors`), both login background keys, both favicon keys and `sidebar-darkmode-type`. Edit those values directly in `config/aura.php` or use the Settings page where it applies.

In non-interactive mode, only the two command options below are available:

```bash
php artisan aura:install-config \
    --no-interaction \
    --teams=false \
    --registration=false
```

For the current `main` implementation, the command preserves comments and `env()` expressions while it updates the published file. With the default config, the teams and registration choices are written to `.env` as `AURA_TEAMS` and `AURA_REGISTRATION`. It does not write `AURA_PATH`, `AURA_DOMAIN` or `AURA_CREATE_TEAMS`, and it does not change `redirect`, invitations, components, resources, views, reporting or media.

The command updates the running process configuration and clears both the configuration cache and application cache. The public beta can replace `env()` expressions with literal values during installation. See [Installation](/docs/installation) for the beta caveat before changing beta configuration.

<a id="custom-tables"></a>

## Custom tables

Per-resource storage and the Resource Editor migration listener are separate settings.

Set these properties on a resource when it should use its own table:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static $customTable = true;

    protected $table = 'products';
}
```

`$usesMeta` remains `true` by default. That means a custom-table resource can still store fields outside its base fillable columns in the shared `meta` table. Set `public static bool $usesMeta = false;` when every input field should be a column on the custom table.

`features.custom_tables_for_resources` only controls migration listeners used when the Resource Editor saves fields:

- `false` disables the listener.
- `true` or `'single'` keeps one `create_{table}_table` migration in sync.
- `'multiple'` creates a new migration for each save.

This feature flag does not give a resource its own table. `$customTable = true` does that. See [Custom tables](/docs/custom-tables).

<a id="configuration-troubleshooting"></a>

## Troubleshooting

If a config edit has no effect, check the resolved value with Tinker or a temporary route and clear the config cache:

```bash
php artisan config:clear
php artisan tinker
```

Theme values selected in `/admin/settings` are stored in the `options` table and override the corresponding config defaults. Check the team-specific option when teams are enabled.

If a newly discovered resource does not appear in navigation, verify its namespace and path in `aura-settings.paths.resources`, then clear the application cache. The navigation cache is separate from Laravel's config cache.

Changing `teams` after the schema already exists is a migration task. Do not use a destructive reset against an application that contains data.

## Related guides

- [Installation](/docs/installation)
- [Quick start](/docs/quick-start)
- [Creating resources](/docs/creating-resources)
- [Teams](/docs/teams)
- [Themes](/docs/themes)
- [Settings](/docs/settings)
- [Custom tables](/docs/custom-tables)
- [Media Library](/docs/media-manager)
