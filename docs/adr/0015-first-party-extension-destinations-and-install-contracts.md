# PLUGIN-PRE: first-party extension destinations and install contracts

Status: Proposed for independent design review

## Context

Saved views, data import/export, and comments must not begin implementation
until each capability has one explicit owner and install contract. Aura Base
already has two boundaries that constrain the decision:

- saved views were advertised as a core table capability, so ADR 0008 prevents
  moving them behind a separately licensed package;
- packages with their own operational schema or collaboration lifecycle should
  not make every Aura installation carry that burden.

The local sibling inventory contains no saved-views or comments package. The
existing public `eminiarts/aura-cms-activity` package is an activity-log
adapter, not a comments domain. The existing private
`eminiarts/aura-data-exchange` repository is the approved Aura Data Exchange
product and already uses the `Aura\DataExchange` namespace. This gate does
not accept its current implementation; it only selects its repository and
defines the contract PLUGIN-02 must satisfy.

## Decision

| Prompt | Destination | Composer package | Namespace | Repository / fresh-session path |
|---|---|---|---|---|
| PLUGIN-01 saved views | Aura Base core | `eminiarts/aura-cms` | `Aura\Base` | existing `eminiarts/aura-cms`; `/Users/bajram/Projekte/aura-cms` |
| PLUGIN-02 import/export | Aura Data Exchange, Commercial Official Plugin | `eminiarts/aura-data-exchange` | `Aura\DataExchange` | existing private `eminiarts/aura-data-exchange`; `/Users/bajram/Projekte/aura-data-exchange` after checkout |
| PLUGIN-03 comments | Aura Comments, Free Official Plugin | `eminiarts/aura-comments` | `Aura\Comments` | approved public repository name `eminiarts/aura-comments`; `/Users/bajram/Projekte/aura-comments` after repository provisioning and checkout |

These destinations are final on acceptance of this ADR. PLUGIN-01 must not
silently move to a package. PLUGIN-02 must not fall back to Aura Base or the
legacy `eminiarts/aura-export` repository. PLUGIN-03 must not fall back to
Aura Base, Aura Activity, Aura Notifications, or another plugin.

This task does not create or clone a repository. The Aura Comments repository
does not exist at decision time; an Aura repository owner must provision the
exact approved repository after this ADR passes independent review. Until it
exists, PLUGIN-03 remains operationally blocked even though its destination is
unambiguous.

## Dependency direction

```text
host Laravel application
  └── eminiarts/aura-cms
        └── saved views (inside Aura Base)

eminiarts/aura-data-exchange ──requires──> eminiarts/aura-cms
eminiarts/aura-comments      ──requires──> eminiarts/aura-cms
```

- Aura Base never imports, discovers, or conditionally references either
  plugin's classes.
- Neither plugin may require the other, Aura Flows, Aura Revisions, Aura
  Activity, Aura Notifications, or any other Commercial Plugin.
- Optional cross-plugin behavior is event- or interface-driven and must degrade
  to the plugin's standalone behavior when the other package is absent.
- Host applications may depend on any combination of the two packages. The
  packages never depend on the host application's concrete models beyond
  configured Aura Resource/User contracts.

This preserves ADR 0010 and prevents circular Composer or runtime dependencies.

## Common first-party package contract

Aura Data Exchange and Aura Comments follow this package contract:

1. The package owns its source, configuration, migrations, views, routes,
   policies, tests, documentation, changelog, and releases in its own
   repository.
2. Composer PSR-4 autoloading maps its declared namespace to `src/`.
3. Laravel package discovery is authoritative through
   `extra.laravel.providers`. Host applications do not manually add providers.
4. The provider extends Spatie Laravel Package Tools'
   `PackageServiceProvider`, merges vendor configuration without requiring a
   publish step, and exposes an optional `*-config` publish tag.
5. Configuration contains only cache-safe scalars and class strings. It must
   work under `config:cache`; closures and request/user objects are forbidden.
6. Package migrations run in place when the host runs `php artisan migrate`.
   Composer installation itself never runs migrations. Documentation must not
   instruct users to publish a second copy of migrations that are also loaded
   from the package.
7. Migrations create or change only package-owned tables. Their `down()`
   methods never drop or rewrite Aura/host tables, following ADR 0002.
8. Composer removal never runs `down()`, drops tables, deletes files, or
   contacts Aura Store. Disablement and removal are non-destructive by default.
9. Each package provides an explicit, confirmation-protected uninstall command
   before removal for operators who intentionally want to destroy its owned
   database rows and private artifacts:
   `aura-data-exchange:uninstall --purge --force` or
   `aura-comments:uninstall --purge --force`. The command is unavailable
   after Composer removal, so documentation fixes the order.
10. Runtime licensing is forbidden. Commercial download/update entitlement is
    enforced only by Aura Store/Satis, following ADRs 0006, 0007, 0012, and
    0013.
11. With its `enabled` setting false, the provider may merge configuration and
    register migrations/container contracts, but it registers no routes,
    navigation, Livewire panels, actions, listeners, scheduled work, or
    permission-catalog writes.
12. Enabling a package before its schema is installed produces one targeted
    installation exception in its admin surface or command, not an arbitrary
    SQL error during unrelated Aura requests.

Package views/assets remain vendor-owned. Publishing them is an explicit host
customization escape hatch, not part of the normal install or update path.

## PLUGIN-01: saved views in Aura Base

### Ownership and paths

Saved views remain MIT-licensed Aura Base code. The implementation uses the
existing technical directories:

- `Aura\Base\Models\SavedView` at `src/Models/SavedView.php`;
- `Aura\Base\Policies\SavedViewPolicy` at
  `src/Policies/SavedViewPolicy.php`;
- `Aura\Base\Services\SavedViewManager` at
  `src/Services/SavedViewManager.php`;
- table/kanban integration under `src/Livewire/Table/` and only through the
  accepted CORE-21/CORE-22 state contracts;
- the migration stub
  `database/migrations/create_aura_saved_views_table.php.stub`;
- feature configuration at `aura.features.saved_views`.

The core-owned table is `aura_saved_views`. It stores a schema version and a
JSON-compatible state document; it never stores closures, query builders,
serialized PHP objects, or executable expressions. The model/manager own
version upgrades and reject state that cannot be validated against the current
Resource and query-state contracts.

### Install, opt-in, disablement, and removal

- Fresh Aura installs receive the migration through Aura's existing
  `aura-migrations`/install path.
- Existing applications publish the newly available Aura migration, run
  `php artisan migrate`, then opt in with
  `AURA_SAVED_VIEWS=true` through `aura.features.saved_views`.
- The default is `false` until the migration has shipped and the host opts in.
  When false, no saved-view query or UI control runs.
- Disabling the feature hides the UI and stops reads/writes but retains all
  rows. There is no separate Composer uninstall because the capability belongs
  to Aura Base.
- Aura migration rollback may drop only `aura_saved_views`. Ordinary disable,
  update, or core removal never deletes saved-view rows automatically.

### Authorization and tenancy

- A private view is visible and mutable only by its owner, subject to
  `viewAny` access to the referenced Resource.
- A Team-shared view is readable only in the same current Team by a User who can
  view the referenced Resource.
- Creating, editing, deleting, or setting a Team-shared/default view requires
  the `manage-aura-saved-views` permission in that Team as well as Resource
  access.
- The server derives owner and Team; neither value is accepted from browser
  state. A Global Admin still selects an explicit Team before managing that
  Team's shared/default views.
- In Teams-off mode `team_id` is null and a shared view is instance-shared,
  still guarded by the same permission and Resource policy.
- A URL/default may select only a view authorized in the current context.
  Removed Fields, operators, Resources, parents, or computed columns fail
  closed through schema validation.

### Test ownership

Aura Base owns all saved-view tests: schema up/down and existing-host upgrade,
private/shared/default policy matrices, Teams-on/off isolation, stale schema
versions, removed Fields, canonical URL/state round trips, parent scope,
computed columns, config-disabled/no-table behavior, and CORE-21/22/23
integration. No plugin test suite is required for PLUGIN-01.

## PLUGIN-02: Aura Data Exchange

### Existing repository contract

PLUGIN-02 works only in the existing private repository:

- GitHub: `eminiarts/aura-data-exchange`;
- local checkout: `/Users/bajram/Projekte/aura-data-exchange`;
- package: `eminiarts/aura-data-exchange`;
- namespace: `Aura\DataExchange\`;
- provider:
  `Aura\DataExchange\AuraDataExchangeServiceProvider`;
- config: `config/aura-data-exchange.php`;
- package-owned tables use the existing `aura_data_mappings` and
  `aura_exchange_*` names.

The repository exists and is currently untagged. Its present code is not
accepted by this destination ADR. In particular, PLUGIN-02 must reconcile its
provider, opt-in default, dependency constraints, migrations, public Aura
contracts, and verification with this ADR and its listed CORE dependencies.

### Install and opt-in

```bash
composer require eminiarts/aura-data-exchange:^1.0
php artisan migrate --force
```

The Plugin Buyer first configures the authenticated Aura Composer repository
issued for the Licensed Project. Configuration publishing is optional:

```bash
php artisan vendor:publish --tag=aura-data-exchange-config
```

`AURA_DATA_EXCHANGE_ENABLED=false` is the default. After migrations, private
storage, queue, retention, and permissions are configured, the operator sets it
to `true`, rebuilds configuration cache, and restarts web/queue workers. The
package contributes no Resource action, route, navigation, permission rows, or
worker behavior while disabled.

The package owns queue and scheduler documentation. Enablement requires a
private filesystem disk, a running queue worker, and a scheduled
`aura-data-exchange:purge` command.

### Authorization and tenancy

The package owns these default abilities and permission slugs:

| Ability | Default permission |
|---|---|
| `aura-data-exchange.view` | `view-aura-data-exchange` |
| `aura-data-exchange.export` | `export-aura-data` |
| `aura-data-exchange.import` | `import-aura-data` |
| `aura-data-exchange.download` | `download-aura-data-exchange-artifacts` |
| `aura-data-exchange.manage-mappings` | `manage-aura-data-mappings` |

- Permission-catalog synchronization occurs in an explicit idempotent install
  or sync command, not as an unconditional write during every provider boot.
- UI and service entry points authorize both the plugin ability and the
  Resource operation. Queue workers re-resolve the actor and reauthorize Team
  membership, plugin ability, Resource policy, row access, and immutable
  mapping/source identity.
- Actor, Team, owner, Resource type, and protected Fields are server-derived.
  Teams-on requires a non-null captured Team except for a separately tested,
  explicit Global Admin operation. Teams-off never adds a Team predicate.
- CORE-22 query state is an input contract; Data Exchange may consume it but
  may not replace Aura's Table component or bypass Resource `indexQuery()`,
  scopes, policies, or CORE-17 writes.

### Uninstall and retention

1. Set `AURA_DATA_EXCHANGE_ENABLED=false`, rebuild configuration cache, stop
   dispatch, and drain/cancel package jobs.
2. Run the normal retention purge.
3. Choose either:
   - retain mappings, run summaries, rows, and remaining private artifacts for
     reinstall; or
   - before Composer removal, run
     `php artisan aura-data-exchange:uninstall --purge --force`.
4. Run `composer remove eminiarts/aura-data-exchange`.

Default retention remains: source payloads and generated artifacts 30 days,
run summaries and reusable mappings indefinitely. Composer removal never
changes those values or deletes data.

### Test ownership

Aura Data Exchange owns its package, queue, browser, security, migration,
retention, large-file/memory, and teams-mode suites. It tests against an Aura
fixture but Aura Base does not copy these tests. Aura Base owns only the fake
package tests for the public action/query/write contracts Data Exchange uses.

## PLUGIN-03: Aura Comments

### Repository contract

On acceptance, Aura approves this new Free Official Plugin identity:

- GitHub: `eminiarts/aura-comments` (public; provisioned after approval);
- local checkout: `/Users/bajram/Projekte/aura-comments`;
- package: `eminiarts/aura-comments`;
- namespace: `Aura\Comments\`;
- provider: `Aura\Comments\AuraCommentsServiceProvider`;
- config: `config/aura-comments.php`;
- models: `Aura\Comments\Models\Comment` and
  `Aura\Comments\Models\CommentRevision`;
- policy: `Aura\Comments\Policies\CommentPolicy`;
- panel: `Aura\Comments\Livewire\CommentsPanel`;
- package-owned tables: `aura_comments` and
  `aura_comment_revisions`.

The package is MIT licensed and distributed through Packagist. It is not a
Commercial Plugin, Aura Pro entitlement, or addition to the Commercial Target
Portfolio. That avoids silently changing the approved commerce plan merely to
choose an extension boundary.

### Install and opt-in

```bash
composer require eminiarts/aura-comments:^1.0
php artisan migrate --force
```

Configuration publishing is optional:

```bash
php artisan vendor:publish --tag=aura-comments-config
```

`AURA_COMMENTS_ENABLED=false` is the default. After migration the host enables
it, rebuilds configuration cache, and restarts long-running workers. When
disabled, no panel, route, navigation, listener, permission sync, or query is
registered. Integration occurs only through the accepted CORE-25 record-layout
panel contract; the package never replaces or publishes every Aura Resource
view.

### Authorization, tenancy, and history

Default permission slugs are:

- `view-aura-comments`;
- `create-aura-comments`;
- `update-own-aura-comments`;
- `delete-own-aura-comments`;
- `manage-aura-comments`.

Every operation must also pass the parent Resource policy and current-Team
scope. Plugin permission alone never grants access to a parent record.
Comment/author/Team/target identifiers are server-derived and cannot be
mass-assigned from Livewire state. Teams-off stores a null `team_id` and uses
the same policy without a Team predicate.

Edits and deletes retain audit history:

- comments are soft deleted;
- every edit/delete writes an immutable `aura_comment_revisions` entry with
  actor and timestamp;
- deleting a User nulls the author foreign key but preserves an immutable
  display-name snapshot;
- soft-deleting a Resource hides comments unless the actor can view the trashed
  Resource;
- force-deleting the target removes its comments and revision history through
  an explicit lifecycle listener;
- comment bodies are validated as bounded plain text or sanitized markup and
  are escaped/sanitized at the rendering boundary.

No mention, notification, attachment, rich-text, activity-log, or revision
package is a dependency. Later integrations may listen to public comment
events without changing this package's standalone behavior.

### Uninstall and retention

Disable the package first. Composer removal retains comments, soft-delete
tombstones, revisions, and author snapshots indefinitely for safe reinstall.
Intentional destruction requires the package's confirmation-protected
`php artisan aura-comments:uninstall --purge --force` command before
`composer remove eminiarts/aura-comments`. The purge drops only the two
package-owned tables.

### Test ownership

Aura Comments owns installation/disable/removal/purge tests; migrations on
every supported database; two Resource types; Teams-on/off and two-Team
isolation; all permission/policy combinations; deleted Users; soft/force-deleted
targets; edit/delete history; XSS; pagination; lifecycle events; config cache;
absence of schema; and browser panel behavior. Aura Base owns only the generic
CORE-16/CORE-25 lifecycle and panel-extension contract tests with a fake
consumer.

## Compatibility matrix

The post-V1 extension program targets Aura Base 2.x. Package major versions are
independent of the core major:

| Component | Supported contract |
|---|---|
| Aura Base saved views | `eminiarts/aura-cms:^2.0` |
| Aura Data Exchange 1.x | `eminiarts/aura-cms:^2.0` |
| Aura Comments 1.x | `eminiarts/aura-cms:^2.0` |
| PHP | `^8.4`; CI covers 8.4 and 8.5 |
| Laravel | 12.x and 13.x |
| Livewire | 4.x |
| Testbench | 10.x for Laravel 12; 11.x for Laravel 13 |
| Teams | Teams-on and Teams-off are equally supported |
| Databases | MySQL 8.0+, PostgreSQL 12+, SQLite 3.8.8+, SQL Server 2017+ |

Before its first tag, Aura Data Exchange must remove its legacy Aura 0.2 and
Laravel 11 compatibility claims and resolve against this matrix. Aura Comments
starts with this matrix. Package CI runs the PHP/Laravel/Testbench matrix and
both team modes on every pull request. SQLite runs the full suite; release CI
also runs migration, uniqueness, JSON/state, tenancy, and rollback tests against
MySQL, PostgreSQL, and SQL Server.

## Release coordination

1. The required CORE dependencies merge and ship in Aura Base 2.0 before either
   plugin publishes a stable tag.
2. PLUGIN-01 ships in the Aura Base changelog/release; it has no independent
   package version.
3. Aura Data Exchange and Aura Comments use independent Semantic Versioning,
   beginning at `1.0.0`. Their `composer.json` files require
   `eminiarts/aura-cms:^2.0`.
4. A core public-contract removal or incompatible semantic change waits for the
   next Aura Base major. Additive optional contracts may ship in a core minor.
5. Plugin releases test against the lowest supported Aura Base 2.x tag and the
   current 2.x branch. A plugin raises its minimum core version when it adopts a
   newer additive contract.
6. Data Exchange releases are immutable Satis distributions authorized by Aura
   Store. Comments releases are immutable Packagist distributions. Neither has
   a runtime license request.
7. Each release coordinates its own changelog, upgrade notes, migration
   rollback evidence, Composer validation/audit, Pest matrix, Pint, PHPStan,
   browser tests where applicable, and a fresh-host install/remove/reinstall
   proof.

## Pre-mortem risks

- **A destination is mistaken for feature acceptance (high):** keep this ADR
  proposed until independent review, keep all three tracker rows blocked on
  their feature dependencies, and require separate implementation review.
- **A disabled package still mutates permissions or exposes routes (high):**
  make disabled-provider behavior an install test and move permission sync to
  an explicit idempotent command.
- **Package removal destroys operational history (high):** retain data on
  disable/remove and require the named pre-remove command plus `--purge
  --force` for destruction.
- **Core and plugin contracts drift (high):** release Aura Base 2.0 first and
  test each plugin against both its lowest supported core tag and current 2.x.
- **Aura Comments implementation starts before its repository or CORE-25 panel
  contract exists (high):** do not create a fallback implementation in Aura
  Base or Aura Activity; leave PLUGIN-03 blocked.

## Rejected alternatives

- **Put all three in Aura Base:** rejected because Data Exchange and Comments
  add substantial schema, operations, and collaboration lifecycle to
  installations that did not request them.
- **Move saved views to a paid or free plugin:** rejected because the capability
  was already advertised as Aura Base and is a shared query/presentation
  primitive.
- **Create one umbrella extensions package:** rejected because it couples
  unrelated release, migration, support, and entitlement lifecycles.
- **Put comments in Aura Activity:** rejected because activity history and
  mutable user collaboration have different policy, retention, editing, and
  deletion semantics.
- **Reuse Aura Notifications for comments:** rejected because notifications are
  delivery state, not the authoritative discussion record.
- **Let packages modify host/core tables:** rejected because uninstall and
  rollback ownership becomes unsafe.

## Gate consequences

- This ADR is a destination/install-contract candidate, not feature completion.
- PLUGIN-01 remains blocked on CORE-21, CORE-22, and CORE-23.
- PLUGIN-02 remains blocked on CORE-06, CORE-09, CORE-10, CORE-11, CORE-17, and
  CORE-22 and must begin in the existing Data Exchange repository.
- PLUGIN-03 remains blocked on CORE-16, CORE-25, independent acceptance of this
  ADR, and provisioning of the exact Aura Comments repository.
- No plugin task is unblocked until an independent reviewer accepts this ADR
  and the program tracker records the destinations.
