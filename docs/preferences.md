# Scoped preferences

Aura preferences are typed declarations stored in the existing `options` table. They add validation,
explicit context, precedence, and authorization without changing the legacy option API.

## Declare a key

Register declarations during application boot through the singleton registry:

```php
use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;

app(PreferenceRegistry::class)->register(new PreferenceDefinition(
    key: 'contacts.density',
    type: PreferenceValueType::String,
    default: 'comfortable',
    scopes: [PreferenceScope::User, PreferenceScope::Team],
    resourceAware: true,
    allowedValues: ['comfortable', 'compact'],
    legacyKeys: ['density.{resource}'],
));
```

Keys are unique and must match `a-z`, digits, dots, hyphens, and underscores. Types are strict:
booleans do not accept `0`, integers do not accept `false`, and nullable declarations preserve a stored
`null` separately from a missing row. Array declarations may require lists and a typed item schema.

Aura includes UI-neutral examples for `table.view`, `table.columns`,
`navigation.sidebar.groups`, and `navigation.sidebar.collapsed`. They do not depend on Livewire table or
navigation components.

## Read and write

```php
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceScope;

$context = new PreferenceContext(
    application: 'crm',
    user: $user,
    team: $team,
    resource: 'Contact',
);

$preferences = app(PreferenceManager::class);
$view = $preferences->get('table.view', $context);
$result = $preferences->resolve('table.view', $context); // value and winning scope

$preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $actor);
$preferences->reset('table.view', PreferenceScope::User, $context, $actor);
```

Reads use only the supplied context. They never consult the authenticated user, so HTTP requests, queue
workers, CLI commands, and team switches resolve the same context identically. Writes also require an
explicit actor. A user may write only their own user scope; a team owner or Global Admin may write team
scope; only a Global Admin may write everyone scope. Guest writes fail.

## Precedence

For resource-aware keys, resolution is deterministic:

1. resource-specific user, team, everyone
2. application-wide user, team, everyone
3. declared default

Unsupported scopes and missing context subjects are skipped during reads. Writes reject unsupported scopes.
Everyone scope is optional per declaration. With teams enabled it uses the reserved option ownership
`team_id = 0`; normal TeamScope queries cannot see it. This avoids a destructive schema migration and keeps
the existing `(team_id, name)` uniqueness guarantee.

## Existing options and migration

`User::getOption()`, `updateOption()`, and `deleteOption()` and their Team equivalents remain unchanged.
Preference declarations may list `legacyKeys`, including `{application}` and `{resource}` placeholders.
Aura reads the canonical hashed preference identity first, then those legacy names. New writes use only the
canonical identity. Reset removes both the canonical row and declared aliases for that exact scope/context.
No data migration is required; applications can migrate lazily as users change preferences.

The explicit low-level adapters `User::getOptionEntryForTeam()`, `updateOptionForTeam()`,
`deleteOptionForTeam()`, and `Team::getOptionEntryExplicit()` exist for trusted services. They do not perform
authorization themselves. Use `PreferenceManager` for user-triggered writes.
