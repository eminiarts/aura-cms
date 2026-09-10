# Preferences

A preference is an application-defined value with a key, a value type, a default, and one or more storage scopes. `PreferenceManager` validates values, reads the selected scope, and writes through Aura's existing option helpers.

Aura does not register application preference definitions for you. Register each definition from your application or plugin service provider. The package binds one `PreferenceRegistry` and one `PreferenceManager` through `AuraServiceProvider`.

## Choose the right store

Aura has several stores with different jobs:

| Store | Use it for | Main entry point |
| --- | --- | --- |
| Configuration | Values that belong to the deployed application and are defined in code or environment variables | `config()` |
| Settings | The built-in appearance form and its current Team or instance settings | `Aura::getOption('settings')` and `Aura::updateOption('settings', $value)` |
| Options | Existing untyped key/value data that does not need a declaration or scope precedence | `Aura`, `User`, and `Team` option helpers |
| Preferences | Declared values that need type validation, scope resolution, and write authorization | `PreferenceManager` or the `Preferences` facade |

Preferences use the existing `options` table. They do not add a preferences table or a migration. The Settings page and the `Option` Resource continue to use their existing option names. A preference definition does not change those APIs.

For the built-in Settings page, read and write the `settings` option:

```php
<?php

use Aura\Base\Facades\Aura;

$settings = Aura::getOption('settings');

Aura::updateOption('settings', array_replace($settings, [
    'sidebar-size' => 'compact',
]));
```

See [Settings](/docs/settings) for the page fields, authorization, and the teams-on and teams-off option rows.

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

`PreferenceRegistry::register()` returns the registry. Registering the same key twice throws `InvalidArgumentException`. The package has no built-in definitions, so keys such as `table.view` exist only when an application or plugin registers them.

The key must start with a lowercase letter and then contain only lowercase letters, digits, dots, hyphens, and underscores. The definition validates its default when it is constructed. `scopes` must contain at least one `PreferenceScope` value. Its default is `User` and `Team`.

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

The value checks are strict. A Boolean accepts only `true` or `false`. An Integer does not accept a numeric string or a Boolean. A Float accepts only a finite PHP float. An Array accepts an array, with optional list and item-type checks.

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

Build a `PreferenceContext` for every read and write. Its constructor accepts an application string, an optional Aura `User`, an optional Aura `Team`, and an optional resource string. The application and resource strings must be non-empty and must not contain a null byte.

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

The facade resolves the same `PreferenceManager` that the container returns. The equivalent service call is:

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

For one canonical option key, the manager checks supported scopes in this order:

1. User
2. Team
3. Everyone

It skips scopes that the definition does not support. For a non-nullable definition, a missing or `null` value and a value that fails validation allow the manager to continue to the next scope. If no canonical value is accepted, the manager checks each `legacyKeys` entry in the User scope and then returns the declared default.

When `resourceAware` is `true` and the context resource is `Contact`, the canonical key passed to the User or Team helper is `preference.contacts.density.Contact`. Without a resource, it is `preference.contacts.density`. The current manager does not fall back from the resource-specific key to the unsuffixed key. `PreferenceContext::forApplication()` creates a context with its resource removed, but `PreferenceManager` does not call it during resolution.

`legacyKeys` are passed to `User::getOption()` as written. The manager does not expand `{resource}` or `{application}`, and it does not check legacy Team or Everyone rows. Use a literal old option name, such as `contacts.density.v0`, when a legacy read is required.

## Nullable values and reset

Set `nullable: true` when `null` is a valid stored value. The manager accepts `null` for `set()` and `reset()` writes `null` through the selected option helper. `reset()` does not call the helper's delete method.

The current option helpers return `null` for both a missing row and a row whose value is `null`. The manager treats either result as a nullable value and stops at that scope. A nullable User scope can therefore prevent a Team or Everyone value from winning. Use a non-nullable definition when a missing row must continue through the fallback order.

## Write authorization

`set()` and `reset()` require an explicit actor. The manager applies these checks before calling an option helper:

| Scope | Required actor and context |
| --- | --- |
| User | `context->user` must exist and its key must equal the actor's key. |
| Team | `context->team` must exist and the actor must pass Gate's `update` ability for that Team. The built-in Team policy allows a Global Admin or the Team owner when team editing is enabled. |
| Everyone | The actor must pass `User::GLOBAL_ADMIN_GATE`. |

An unsupported scope, a missing actor, or a failed check throws `InvalidArgumentException`. A missing User or Team context also fails a write before storage. Guest writes therefore fail because they cannot provide a `User` actor.

The manager authorizes the explicit `$actor`, then calls the existing helpers. Those helpers still use their normal current-authentication and current-Team routing. The manager does not switch authentication or make `context->team` an explicit argument to `User::updateOption()` or `Team::updateOption()`. Keep the authenticated actor and current Team aligned with the context when writing in a teams-enabled application.

## Aura facade and storage limits

The `Preferences` facade resolves `PreferenceManager`. The `Aura` facade remains the low-level option and Settings API. `PreferenceManager` uses `Aura::getOption()` and `Aura::updateOption()` only for the `Everyone` scope. User and Team scopes call the corresponding resource helpers.

The current implementation has three limits to account for:

- `PreferenceContext::$application` is validated and carried through the API, but `PreferenceManager` does not include it in option keys. It does not isolate two applications that share the same options table.
- `Everyone` reads and writes delegate to `Aura`, whose teams-enabled path uses the authenticated user's current Team. They are not resolved from the supplied context alone, so queue and CLI calls need an authenticated Aura context. The manager also passes an already prefixed canonical key to `Aura`, which currently produces a `preference.everyone.preference...` option name.
- There is no preference-specific cache or migration layer. Existing option helper persistence and cache behavior applies.

These limits are part of the current lightweight implementation. Do not treat the `application` field as a storage namespace or assume that Everyone values are independent of the current authenticated Team.

## Related APIs

- [Settings](/docs/settings) documents the built-in appearance option and the `Option` Resource.
- [Teams](/docs/teams) documents Team context and the existing `User` and `Team` option helpers.
- [Record layouts](/docs/record-layouts) documents the optional boolean preference used to show a registered panel.
