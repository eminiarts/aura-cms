# Custom permissions are defined in code and checked through Gates

Aura generates eight permissions per Resource and stores them as `permissions`
rows. The role form renders those rows, and `ResourcePolicy` checks
`{ability}-{resourceSlug}` against the role's grant map. Applications also have
pages that are not Resources: an assistant chat, eval runs, an approval inbox.
Until now they had no permission to check, so they hard-coded role slugs
(`Roles::hasAny($user, ['admin'])`), and a role admin could not grant "use the
assistant" or "view evals but not run them".

An application or plugin now defines a **Custom Permission** in code, in a
service provider's `boot()`:

```php
Aura::registerPermissions([
    'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    'box.evals.run' => ['name' => 'Run evals', 'group' => 'Evals', 'description' => 'Start a new eval run'],
]);
```

The registry is the only definition. Aura writes no `permissions` row for it.
For every key Aura defines one Laravel Gate. The Gate allows the user when an
effective role grants the key (`User::hasPermission()`, so a Super Admin
passes) or when the user is a Global Admin. Code checks with `authorize()`,
`@can` and `can()`. Grants live in `roles.permissions` next to resource grants,
so Global Roles, Team Roles, Shadowing and Teams-off mode behave exactly as for
resource permissions (ADR 0005). The role form shows registered permissions in a
Features section beside the resource matrix.

Admins grant Custom Permissions. They do not create them. A permission created
in the UI has no code that enforces it, so granting it would protect nothing.

## Considered options

- **Define permissions in the database through the Permission resource or the
  role form.** Rejected: a row can exist without a check, and a mistyped slug
  fails silently.
- **Sync registered permissions into `permissions` rows.** Rejected: rows are
  team-scoped, so global rows would need a `TeamScope` exception. Every deploy
  would need a sync step, and until it ran only Super Admins would pass.
- **One `Gate::before` hook for all custom keys.** Rejected: host apps already
  register their own `before` hooks, and a hook sees every ability including
  policy abilities. One Gate per key affects nothing else.
- **Wildcard grants (`evals.*`).** Rejected: they silently grant actions added
  later.

## Consequences

- Keys are exact strings. The convention is lowercase and dotted, with plugins
  prefixing their own name (`box.`, `flows.`). The dot keeps them apart from
  generated `{ability}-{slug}` keys. Registering a key twice, or a key that
  already has a Gate, throws.
- A key that is no longer registered has no Gate, so `can()` denies it. Grants
  already stored for it stay in the role's JSON untouched. Renaming a key needs
  an explicit data migration.
- Names, groups and descriptions are stored untranslated and translated when the
  role form renders.
- `hasPermission()` still checks any stored grant. App code uses the Gate so
  that unregistered keys deny.
- The team invitation permission `invite-users-team` is registered through the
  registry. It no longer needs a hand-made `permissions` row, and
  `Team::customPermissions()` is removed.
- Pages that operate the instance (providers, models, costs, secrets) are not
  Custom Permissions. They check the `AuraGlobalAdmin` gate and never appear in
  the role form. A user's own data (own chats, own history) is guarded by
  ownership, not by a permission.
- Resource permissions are unchanged. Because they can be derived from the
  registered Resources, a later change could move them into the same registry
  and delete the generator job, its command and the `permissions` rows.
