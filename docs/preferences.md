# Preferences

Preferences let your application declare values with a type, a default, and rules for where they can be stored. A preference can belong to a user, a team, or everyone. Aura validates each value and reads it from the first applicable scope, using the existing option helpers for storage.

Register your preferences in an application or plugin service provider. Aura has no built-in definitions. Its service provider binds a shared `PreferenceRegistry` for definitions and a shared `PreferenceManager` for reading and writing values.

## Choose the right store

Aura has several stores with different jobs:

| Store | Use it for | Main entry point |
| --- | --- | --- |
| Configuration | Values that belong to the deployed application and are defined in code or environment variables | `config()` |
| Settings | The built-in appearance form and its current team or instance settings | `Aura::getOption('settings')` and `Aura::updateOption('settings', $value)` |
| Options | Existing untyped key/value data that does not need a declaration or scope precedence | `Aura`, `User`, and `Team` option helpers |
| Preferences | Declared values that need type validation, scope resolution, and write authorization | `PreferenceManager` or the `Preferences` facade |

Preferences use the existing `options` table, so they need no new table or migration. The Settings page and the option resource keep their existing option names and APIs.

For the built-in Settings page, read and write the `settings` option:

```php
<?php

use Aura\Base\Facades\Aura;

$settings = Aura::getOption('settings');

Aura::updateOption('settings', array_replace($settings, [
    'sidebar-size' => 'compact',
]));
```

See [Settings](/docs/settings) for the page fields, authorization, and how storage differs when teams are enabled or disabled.

## Register a definition

Register definitions during application boot. A normal Laravel application can inject the package registry into `AppServiceProvider::boot()`:

```php
<?php

namespace App\Providers;

use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(PreferenceRegistry $preferences): void
    {
        $preferences->register(new PreferenceDefinition(
            key: 'contacts.density',
            type: PreferenceValueType::String,
            default: 'comfortable',
            scopes: [PreferenceScope::User, PreferenceScope::Team],
            resourceAware: true,
            allowedValues: ['comfortable', 'compact'],
            legacyKeys: ['contacts.density.v0'],
        ));
    }
}
```

Each key must be unique. Registering the same key twice throws `InvalidArgumentException`. The registration method returns the registry, so calls can be chained. Keys such as `table.view` are available only after an application or plugin registers them.

The key must start with a lowercase letter and contain only lowercase letters, digits, dots, hyphens, and underscores. The definition validates its default value when constructed. It must also support at least one scope. By default, preferences support the user and team scopes.

## Definition options

`PreferenceDefinition` has these typed constructor arguments:

| Argument | Meaning |
| --- | --- |
| `key` | The canonical key that callers pass to the manager. |
| `type` | One of `String`, `Boolean`, `Float`, `Integer`, or `Array` from `PreferenceValueType`. |
| `default` | The value returned when no accepted stored value is found. The definition validates it. |
| `scopes` | A non-empty list of `PreferenceScope::User`, `Team`, or `Everyone`. |
| `nullable` | Allows `null` in `set()`. The default is `false`. |
| `resourceAware` | Appends the context resource to the canonical option key when a resource is present. |
| `allowedValues` | A strict allow-list. An empty list disables this check. |
| `itemType` | Validates every value in an array with another `PreferenceValueType`. |
| `list` | Requires an array with consecutive integer keys. It can be used only with the `Array` type. |
| `legacyKeys` | Raw user option names that the manager checks after the canonical key. It never writes them. |

Types are checked strictly. Booleans accept only `true` or `false`, and integers reject numeric strings and booleans. Floats must be finite PHP floats. Arrays may also require consecutive integer keys or a specific type for each item.

For example, an ordered list of string values can be declared as follows:

```php
<?php

use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceValueType;

$definition = new PreferenceDefinition(
    key: 'contacts.columns',
    type: PreferenceValueType::Array,
    default: ['name', 'email'],
    itemType: PreferenceValueType::String,
    list: true,
);
```

## Read and write at runtime

Every read and write needs a context identifying the application and, where relevant, the user, team, and resource. Create it with `PreferenceContext`, passing Aura user and team models when needed. The application name and any supplied resource name must be non-empty strings without null bytes.

```php
<?php

use Aura\Base\Facades\Preferences;
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceScope;

$user = auth()->user();
$team = config('aura.teams') ? $user->currentTeam : null;

$context = new PreferenceContext(
    application: (string) config('app.name', 'app'),
    user: $user,
    team: $team,
    resource: 'Contact',
);

$density = Preferences::get('contacts.density', $context);
$resolved = Preferences::resolve('contacts.density', $context);

Preferences::set(
    'contacts.density',
    'compact',
    PreferenceScope::User,
    $context,
    $user,
);

Preferences::reset(
    'contacts.density',
    PreferenceScope::User,
    $context,
    $user,
);
```

You can also resolve the preference manager directly from the container. It is the same instance used by the facade:

```php
<?php

use Aura\Base\Preferences\PreferenceManager;

$preferences = app(PreferenceManager::class);
$density = $preferences->get('contacts.density', $context);
```

`get()` returns the selected value. `resolve()` returns a `PreferenceResult` with these public properties:

| Property | Value |
| --- | --- |
| `value` | The stored value or the declared default. |
| `scope` | The winning `PreferenceScope`, or `null` when the default wins. |
| `resourceSpecific` | `true` when a resource-aware definition was resolved with a resource context. |
| `isDefault` | `true` only when the declared default was returned. |
| `isLegacy` | `true` when a legacy user option supplied the value. |

## Scope resolution

For each preference key, the manager checks the supported scopes in this order:

1. User
2. Team
3. Everyone

The manager skips scopes the definition does not support. For a non-nullable preference, it continues to the next scope when a value is missing, null, or invalid. If no valid value is found under the canonical key, it checks the declared legacy keys in the user scope. If those checks also fail, it returns the default.

A resource-aware preference stores a separate value for each resource. For the example above, a Contact resource context produces the option key `preference.contacts.density.Contact`. A context without a resource produces `preference.contacts.density`. These are the keys passed to the user or team option helper.

The manager does not fall back from a resource-specific key to the key without a resource suffix. You can create a context without a resource using `PreferenceContext::forApplication()`, but the manager does not do this during resolution.

Legacy keys are literal user option names, such as `contacts.density.v0`. The manager passes them unchanged to `User::getOption()`. It does not substitute `{resource}` or `{application}`, or check legacy values in the team or everyone scopes.

## Nullable values and reset

Set `nullable: true` when null is a valid stored value. This allows you to pass `null` to `set()`. Resetting a preference with `reset()` writes null through the selected option helper rather than deleting the option.

The option helpers return null both for missing rows and for rows that store null. For a nullable preference, the manager accepts either result and stops at that scope. A nullable user scope can therefore prevent the manager from reaching a team or everyone value. Use a non-nullable definition when missing values must fall back to the next scope.

## Write authorization

Pass the user performing the write as an explicit actor to `set()` and `reset()`. Before saving, the manager checks that this user can write to the selected scope:

| Scope | Required actor and context |
| --- | --- |
| User | `context->user` must exist and its key must equal the actor's key. |
| Team | `context->team` must exist and the actor must pass Gate's `update` ability for that team. The built-in team policy allows a global admin or the team owner when team editing is enabled. |
| Everyone | The actor must pass `User::GLOBAL_ADMIN_GATE`. |

The manager throws `InvalidArgumentException` if the scope is unsupported, the actor is missing, or authorization fails. Writes also fail before reaching storage if the selected scope lacks its required user or team context. Guests cannot write preferences because an Aura user is required as the actor.

Keep the authenticated user and current team aligned with the preference context when writing in an application with teams enabled. The manager authorizes the actor you pass, but the option helpers still use the current authentication and team to route storage. It does not switch authentication or pass the context team as an explicit argument to the user or team update helper.

## Aura facade and storage limits

Use the `Preferences` facade to access the preference manager. The `Aura` facade remains the API for options and the built-in Settings page. The manager uses its option helpers only for the everyone scope. User and team scopes use the option helpers on their respective resources.

The current implementation has three limits to account for:

- The application name in the context is validated but omitted from option keys. Two applications sharing an options table are therefore not isolated by their application names.
- The everyone scope delegates reads and writes to the Aura facade. With teams enabled, this uses the authenticated user's current team, so the supplied preference context alone is not enough. Queue and CLI calls also need an authenticated Aura context. The manager passes an already prefixed key to the facade, which currently produces an option name beginning with `preference.everyone.preference...`.
- There is no preference-specific cache or migration layer. Existing option helper persistence and cache behavior applies.

Do not use the application name as a storage namespace or assume that everyone values are independent of the authenticated user's current team.

## Related APIs

- [Settings](/docs/settings) documents the built-in appearance option and the option resource.
- [Teams](/docs/teams) documents team context and the user and team option helpers.
- [Record layouts](/docs/record-layouts) documents the optional boolean preference used to show a registered panel.
