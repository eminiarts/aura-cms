# Settings

Aura has separate configuration, settings, options, and user preference stores. The built-in Settings page writes the current theme and appearance choices to an Option record. It does not edit a configuration file and it does not use user preference rows.

![Settings page](/images/docs/settings/settings-general.png)

## Settings page

The built-in page is the `Aura\Base\Livewire\Settings` component. Its route is `/{path}/settings`, which is `/admin/settings` when `aura.path` keeps its default. The route name is `aura.settings`.

The component is selected through `aura.components.settings`:

~~~php
// config/aura.php
'components' => [
    'settings' => Aura\Base\Livewire\Settings::class,
],
~~~

The route uses the middleware configured as `aura-settings.middleware.aura-admin`, which includes the `web` and `auth` middleware.

## Access and authorization

The component checks access in `mount()`:

| Check | Result when it fails |
| --- | --- |
| `config('aura.features.settings')` | 404 |
| `auth()->user()->isSuperAdmin()` | 403 |

Set the feature flag to `false` to remove the page:

~~~php
// config/aura.php
'features' => [
    'settings' => false,
],
~~~

The page requires a Super Admin role. A Global Admin status alone does not satisfy this check. A Global Admin who also has a Super Admin role in the current Team passes it.

The separate Option Resource uses the normal Resource policy and permission checks. A Super Admin or Global Admin has the policy's blanket Resource access. Other users need the relevant Resource abilities. The Settings page's `isSuperAdmin()` check still applies even when the same user can access the Option Resource.

## Fields on the page

`Settings::getFields()` returns Aura Field definitions as arrays. The built-in form contains these input fields:

| Field | Slug | Type | Values or behavior |
| --- | --- | --- | --- |
| Logo | `logo` | `Image` | Selects an Attachment for the admin navigation |
| Logo Darkmode | `logo-darkmode` | `Image` | Selects the dark-mode navigation logo |
| Size | `sidebar-size` | `Radio` | `standard`, `compact` |
| Sidebar | `sidebar-type` | `Radio` | `primary`, `light`, `dark` |
| Darkmode | `darkmode-type` | `Radio` | `auto`, `light`, `dark` |
| Sidebar Darkmode | `sidebar-darkmode-type` | `Radio` | `primary`, `light`, `dark`, shown when `darkmode-type` is `auto` |
| Primary Color Palette | `color-palette` | `Select` | Preset palette or `custom` |
| Gray Color Palette | `gray-color-palette` | `Select` | Preset palette or `custom` |

The primary palette options are:

~~~text
aura, red, orange, amber, yellow, lime, forest-green, green, emerald,
mountain-meadow, teal, ocean-breeze, cyan, sky, blue, indigo, violet,
purple, fuchsia, pink, rose, sandal, desert-sand, salmon, autumn-rust,
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc,
neutral, stone, sandstone, rose-quartz, olive, smaragd, custom
~~~

The gray palette options are:

~~~text
slate, dark-slate, blackout, obsidian, amethyst, opal, gray, zinc,
neutral, stone, sandstone, rose-quartz, olive, smaragd, custom
~~~

Selecting `custom` shows Color fields for these shade slugs:

~~~text
primary-25, primary-50, primary-100, primary-200, primary-300,
primary-400, primary-500, primary-600, primary-700, primary-800,
primary-900, primary-950

gray-25, gray-50, gray-100, gray-200, gray-300, gray-400,
gray-500, gray-600, gray-700, gray-800, gray-900, gray-950
~~~

The built-in page does not edit `login-bg`, `login-bg-darkmode`, `app-favicon`, `app-favicon-darkmode`, `font`, or semantic `colors`. Configure those under `aura.theme`, or add fields through a custom Settings component.

## Storage and Team context

Settings use the `Aura\Base\Resources\Option` Resource and the `options` table:

~~~php
namespace Aura\Base\Resources;

use Aura\Base\Resource;

class Option extends Resource
{
    public static $customTable = true;
    public static ?string $slug = 'option';
    public static string $type = 'Option';

    protected $table = 'options';
    protected $fillable = ['name', 'value', 'team_id'];
    protected $casts = ['value' => 'array'];
}
~~~

The `value` column stores JSON. Eloquent returns it as an array through the `array` cast.

### Teams enabled

Teams are enabled by default. On the first visit, `Settings::mount()` creates or loads this row:

~~~text
name: team.{currentTeamId}.settings
team_id: currentTeamId
~~~

`Option` applies `TeamScope` when `aura.teams` is `true`. Its saving hook fills `team_id` from the authenticated user's `current_team_id` when the value is not already set. The option name and `team_id` therefore both identify the current Team.

Switching the authenticated user to another Team makes the Settings page read that Team's row. There is no shared database settings row that the page merges into every Team.

### Teams-off mode

Set `aura.teams` to `false`, normally through `AURA_TEAMS=false`. The page uses one row:

~~~text
name: settings
team_id: null
~~~

`Option` does not apply TeamScope in teams-off mode. `Aura::getOption('settings')` then reads the unscoped `settings` row.

## Defaults and precedence

On the first visit, the Settings component seeds six keys from `config('aura.theme')`:

~~~json
{
    "darkmode-type": "auto",
    "sidebar-type": "dark",
    "color-palette": "aura",
    "gray-color-palette": "slate",
    "sidebar-size": "standard",
    "sidebar-darkmode-type": "dark"
}
~~~

The values in this example are the shipped defaults. If the host application changes `aura.theme`, the component uses those values when it creates the row.

After the component loads the row, it maps every declared input slug into `form.fields`. When `save()` runs, it writes the full field map back to `Option.value`. A saved row can therefore contain `logo`, `logo-darkmode`, custom color fields, and empty values in addition to the six initial keys.

Runtime theme resolution uses three sources in this order:

1. Aura's package theme defaults.
2. The host application's `config('aura.theme')`.
3. The current Team's saved `settings` Option, or the teams-off `settings` Option.

The later source wins. `ThemeTokens::resolve()` applies this order for fonts and semantic colors. Navigation reads saved sidebar settings and falls back to `config('aura.theme.*')` when a saved key is absent. The color renderer uses the saved palette names and custom shade values.

`config('aura.theme.*')` is configuration data. `Aura::option('theme')` and `Aura::options()` read `config('aura')`. Neither reads the saved settings row. The built-in Settings component uses the option name `settings`, not `theme`.

## Reading and updating settings

Read the current settings through the Aura facade:

~~~php
use Aura\Base\Facades\Aura;

$settings = Aura::getOption('settings') ?: [];

$palette = $settings['color-palette']
    ?? config('aura.theme.color-palette');
~~~

`Aura::getOption('settings')` resolves the current Team when teams are enabled. It returns an empty array when no matching row exists and caches the result for up to one hour.

Use the matching facade method to update the current context:

~~~php
use Aura\Base\Facades\Aura;

$settings = Aura::getOption('settings') ?: [];
$settings['color-palette'] = 'emerald';

Aura::updateOption('settings', $settings);
~~~

With teams enabled, `Aura::updateOption()` writes `team.{currentTeamId}.settings`. With teams-off mode, it writes `settings`. The helper does not perform an authorization check. Authorize the caller before invoking it.

The package does not generate a REST endpoint for settings or Options. A host application that needs an HTTP API must define its own route and controller and apply its own authorization.

## The Option Resource

`Aura\Base\Resources\Option` is a generic Resource registered under `aura.resources.option`. It is separate from the Settings page. With the default path, its CRUD index is `/admin/option`.

Its declared Resource fields are:

| Field | Slug | Type | Rules |
| --- | --- | --- | --- |
| Name | `name` | `Text` | Required, shown on the index |
| Value | `value` | `Textarea` | Required, hidden from the index |

The model's `value` cast turns valid JSON values into PHP arrays when they are read. `Option::byName($name)` returns the first matching row under the current query scope.

Use the Option Resource when you need generic CRUD over an Option row. For the built-in theme settings, `Aura::getOption('settings')` and `Aura::updateOption('settings', ...)` keep the option name, Team context, and facade cache aligned.

## User preferences

User preferences are stored separately from the Settings page. `User::getOption()`, `User::updateOption()`, and `User::deleteOption()` prefix names with `user.{userId}.`.

~~~php
$user = auth()->user();

$user->updateOption('sidebarToggled', false);
$user->updateOption('table_view.Post', 'kanban');

$view = $user->getOption('table_view.Post');

$user->deleteOption('table_view.Post');
~~~

When teams are enabled, `User::updateOption()` stores the current `team_id`, and `User::getOption()` reads the current Team's scoped rows. These preferences are therefore per user and current Team. In teams-off mode, they have no `team_id`.

Aura's navigation uses user preferences for sidebar collapse and expanded groups. Table components use user preferences for table views, columns, column order, saved filters, Kanban statuses, and bookmarks. These rows do not override the theme settings row.

Team-specific application options can also be accessed through `Team::getOption()`, `Team::updateOption()`, and `Team::deleteOption()`. Those methods prefix names with `team.{teamId}.`.

## Extending the Settings component

Point `aura.components.settings` at a component that extends the built-in class. Add fields with the same array format used by Resources:

~~~php
namespace App\Livewire;

use Aura\Base\Livewire\Settings as BaseSettings;

class CustomSettings extends BaseSettings
{
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Custom',
                'slug' => 'panel-custom',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Support email',
                'slug' => 'support-email',
            ],
        ]);
    }
}
~~~

~~~php
// config/aura.php
'components' => [
    'settings' => App\Livewire\CustomSettings::class,
],
~~~

The Settings component writes the added `support-email` field into the same `Option.value` array. Read it through `Aura::getOption('settings')`:

~~~php
$email = (Aura::getOption('settings') ?: [])['support-email'] ?? null;
~~~

## Cache invalidation

The built-in `Settings::save()` calls `Cache::clear()` after updating its Option row.

`Aura::updateOption()` forgets the exact facade cache keys used by `Aura::getOption()`. Use it for programmatic settings changes when possible. Direct `Option` model updates do not clear those facade entries, so a direct update can remain invisible until the one-hour cache expires.

`User::updateOption()` and `User::deleteOption()` forget the cache entry for the user option they change. Team option writes also forget their prefixed entry. Switching Teams changes the facade cache key because it includes the current Team id.

## Supported APIs

These are the supported PHP entry points for this data:

| API | Reads or writes | Context |
| --- | --- | --- |
| `Aura::getOption($name)` | Reads a cached Option value | Current Team when teams are enabled |
| `Aura::updateOption($name, $value)` | Writes an Option value and clears facade cache | Current Team when teams are enabled |
| `Aura::option($key)` | Reads a top-level value from `config('aura')` | Application configuration |
| `Aura::options()` | Reads the full `config('aura')` array | Application configuration |
| `Option::byName($name)` | Reads an Option model | Current query scope |
| `Team::getOption()` / `updateOption()` / `deleteOption()` | Reads or writes Team-prefixed options | One Team |
| `User::getOption()` / `updateOption()` / `deleteOption()` | Reads or writes User-prefixed options | One User and current Team |
| `Settings::getFields()` | Defines the Settings form | The configured Settings component |

Authorize programmatic writes in the host application. The Settings page and generic Resource CRUD perform their own checks, but the helper methods are model and facade methods rather than authorization gates.

## Related guides

- [Themes](/docs/themes) for palette rendering and theme tokens
- [Configuration](/docs/configuration) for `aura.php` and component configuration
- [Teams](/docs/teams) for Team context and teams-off mode
- [Fields](/docs/fields) for Field definitions
- [Scoped preferences](/docs/preferences) for typed preference declarations
