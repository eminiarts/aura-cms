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

The current Aura Base checkout is the V1 line: `composer.json` requires PHP
`^8.4`, Illuminate 12 or 13, and Livewire 4. Its published Git tags currently
stop at `v1.0.0-beta.3`; this ADR must not invent an Aura 2.x release or API.

The existing private `eminiarts/aura-data-exchange` repository is the approved
Aura Data Exchange product and already uses the `Aura\DataExchange` namespace.
Its untagged `main` branch was inspected read-only at
`781c8cabe8ee60f1280c3e2e746f07a85f03f0c6`. At that revision it requires
`eminiarts/aura-cms:^0.2.0|^2.0`, declares the local Aura path package as a fake
`2.0.0`, runs migrations in place, and writes Permission rows from provider
boot. Those are observations, not accepted contracts; PLUGIN-02 must correct
them before release. The expected local path
`/Users/bajram/Projekte/aura-data-exchange` was not present during this design
correction, so no local package state is claimed.

There is no Aura Comments package or remote repository at decision time. This
ADR selects a new local package identity only. It does not create a remote,
publish a package, or push code.

## Decision

| Prompt | Destination | Composer package | Namespace | Repository / fresh-session path |
|---|---|---|---|---|
| PLUGIN-01 saved views | Aura Base core | `eminiarts/aura-cms` | `Aura\Base` | existing `eminiarts/aura-cms`; `/Users/bajram/Projekte/aura-cms` |
| PLUGIN-02 import/export | Aura Data Exchange, Commercial Official Plugin | `eminiarts/aura-data-exchange` | `Aura\DataExchange` | existing private `eminiarts/aura-data-exchange`; expected local checkout `/Users/bajram/Projekte/aura-data-exchange` |
| PLUGIN-03 comments | Aura Comments, Free Official Plugin | `eminiarts/aura-comments` | `Aura\Comments` | new local package at `/Users/bajram/Projekte/aura-comments`; no remote selected or provisioned |

These destinations are final on acceptance of this ADR. PLUGIN-01 must not
silently move to a package. PLUGIN-02 must not fall back to Aura Base or the
legacy `eminiarts/aura-export` repository. PLUGIN-03 must not fall back to
Aura Base, Aura Activity, Aura Notifications, or another plugin.

This design task creates no package or repository. Acceptance authorizes a
future worker to create and implement the **local** `eminiarts/aura-comments`
package after PLUGIN-03's dependencies are complete. Creating a GitHub or other
remote repository, choosing its visibility, publishing to Packagist, and any
push all require separate repository-owner approval; none is performed or
implied here. A remote is therefore a distribution gate, not a blocker for
later local implementation.

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
6. The provider registers package migrations in place. Composer installation
   never runs them. The host may use its normal `php artisan migrate`, while
   the package's `*:install` command runs the same package path through
   Laravel's Migrator and then synchronizes permissions. Publishing a second
   copy of migrations that are also loaded in place is forbidden.
7. Every package has a committed migration manifest. Each entry contains the
   exact migration ledger id (the migration filename basename), source path,
   and exact owned tables/columns/indexes. Migration ids are collision-checked
   before install, and all new ids are package-prefixed; prefix or wildcard
   matching is never used for destructive work.
8. Migrations create or change only package-owned tables. Their `down()`
   methods are rerunnable against partial schema and never drop or rewrite
   Aura/host tables, following ADR 0002.
9. Composer removal never runs `down()`, drops tables, deletes artifacts,
   edits the global migration ledger, removes Permission rows, or contacts Aura
   Store. Disablement and removal are non-destructive by default.
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
13. Service-provider `register()` and `boot()` are database-write-free in both
    enabled and disabled modes. Permission synchronization and schema changes
    occur only in the commands or post-create lifecycle described below.

Package views/assets remain vendor-owned. Publishing them is an explicit host
customization escape hatch, not part of the normal install or update path.

### Exact lifecycle commands

The two packages expose the same lifecycle shape:

| Purpose | Aura Data Exchange | Aura Comments |
|---|---|---|
| Install/upgrade | `aura-data-exchange:install` | `aura-comments:install` |
| Permission reconciliation | `aura-data-exchange:permissions:sync` | `aura-comments:permissions:sync` |
| Safe removal preflight | `aura-data-exchange:uninstall` | `aura-comments:uninstall` |
| Explicit destructive purge | `aura-data-exchange:uninstall --purge --force` | `aura-comments:uninstall --purge --force` |

All four forms support Laravel's global `--no-interaction` option. Their
contract is:

- `*:install` is idempotent. It refuses an unsupported database or a migration
  id collision. It also fails with a targeted recovery instruction when the
  manifest's schema and ledger disagree; it never fabricates a ledger row or
  skips an unledgered table. With a consistent state it runs only the committed
  package migration paths through Laravel's Migrator, invokes
  `*:permissions:sync`, and leaves the feature disabled until the operator
  changes configuration. Running it again changes neither schema nor grants.
- `*:uninstall` **without** `--purge` is an idempotent, read-only preflight. It
  requires the feature to be disabled and, where applicable, confirms no
  package jobs are active. It preserves package tables and rows, private
  artifacts, Permission catalog rows, Role grants, and every package migration
  ledger row. The operator may then remove and later require the Composer
  package without data loss.
- `--purge` is rejected unless `--force` is also present and the package is
  disabled. Data Exchange additionally requires all package jobs to be terminal
  and workers stopped. The command acquires a package maintenance lock and
  operates only on the exact committed ownership manifests.
- A purge snapshots and deletes only package-owned private artifact paths,
  runs the package migrations down in reverse order, and removes the matching
  global `migrations` row only after that migration's owned schema is confirmed
  absent. It removes only the package's exact Permission catalog slugs; it does
  not rewrite host Role JSON/grants or any other Aura/host row.
- A ledger-present/schema-absent state is repaired by the idempotent `down()`
  followed by deletion of that exact ledger row. A schema-present/ledger-absent
  state is removed only because `--purge --force` explicitly authorized
  destruction of the manifest's exact owned objects. Unknown objects, name
  collisions, unexpected foreign keys, or ownership drift fail closed.
- A failed/partial purge never deletes a ledger row for a migration whose owned
  schema remains. The command reports the exact manifest entry and is safe to
  rerun. Success requires both zero owned schema objects and zero exact
  package migration ledger rows. This prevents a reinstall from silently
  skipping migrations because of stale ledger state.
- Reinstall after safe uninstall finds the retained ledger/schema and only
  re-runs permission synchronization. Reinstall after purge sees no package
  ledger rows and recreates a fresh schema through the normal migrator.

The required lifecycle acceptance sequence is: fresh install twice; disable
and re-enable; safe uninstall, Composer remove/require, reinstall with rows and
artifacts unchanged; purge, Composer remove/require, fresh reinstall; and
recovery from both ledger-present/schema-absent and
schema-present/ledger-absent fixtures. A mixed migration batch must prove that
no Aura/host migration ledger row or table is touched.

### Exact permission synchronization

Each package owns a versioned permission-definition manifest and the command
`*:permissions:sync {--team=*} {--all-teams}`. `--team` and `--all-teams` are
mutually exclusive.

- With Teams enabled, no scope option or `--all-teams` enumerates every existing
  Aura Team without global scopes; repeated `--team=<id>` values reconcile only
  validated Team ids.
- With Teams disabled, no scope option or `--all-teams` reconciles one flat
  catalog, while `--team` is rejected. Identity is `slug` in flat mode and
  `(slug, team_id)` in Teams mode.
- Reconciliation uses an idempotent upsert for the exact package slugs and may
  update package-owned name, description, and group labels. It never grants or
  revokes a Role, rewrites `roles.permissions`, creates a Role, or deletes an
  unknown/retired slug.
- While enabled, the provider may register a database-write-free Aura
  `Team::created` after-commit listener. The listener calls the same synchronizer
  for that new Team only. When disabled, no listener is registered; the next
  install/re-enable sync catches up every Team created while it was disabled.
- Re-enable and every package upgrade run `*:permissions:sync --all-teams`
  explicitly before workers restart. Composer scripts and provider boot never
  substitute for this command.

Tests run each sync twice for existing Teams, a newly created Team, Teams-off,
re-enable, and an upgraded manifest. They assert one row per identity, updated
labels, no implicit Role grants, and zero SQL writes from provider boot.

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
- expected local checkout: `/Users/bajram/Projekte/aura-data-exchange`;
- package: `eminiarts/aura-data-exchange`;
- namespace: `Aura\DataExchange\`;
- provider:
  `Aura\DataExchange\AuraDataExchangeServiceProvider`;
- config: `config/aura-data-exchange.php`;
- package-owned tables are exactly `aura_data_mappings`,
  `aura_exchange_runs`, and `aura_exchange_row_results`.

The repository exists and is currently untagged. The read-only source evidence
for this decision is `main` commit
`781c8cabe8ee60f1280c3e2e746f07a85f03f0c6`; the expected local checkout was
absent during review. Its present code is not accepted by this destination ADR.
PLUGIN-02 must reconcile its provider, opt-in default, dependency constraints,
migrations, lifecycle commands, permission writes, public Aura contracts, and
verification with this ADR and its listed CORE dependencies.

Before PLUGIN-02 implementation is reviewable, its root `composer.json` must:

- replace `eminiarts/aura-cms:^0.2.0|^2.0` with the Aura V1 constraint;
- remove Laravel 11 compatibility because Aura V1 supports Laravel 12/13;
- replace the fake local path version `2.0.0` with the development-line version
  `1.0.x-dev` and use `eminiarts/aura-cms:^1.0@dev` only while testing an
  untagged local Aura checkout;
- require `eminiarts/aura-cms:^1.0` and remove the development alias/path
  override from release evidence before the first stable tag.

`1.0.x-dev` is deliberately a development version, not a claim that an
untagged checkout is a released `1.0.0`. No Data Exchange manifest or test may
resolve Aura as 2.x.

### Install and opt-in

```bash
composer require eminiarts/aura-data-exchange:^1.0
php artisan aura-data-exchange:install --no-interaction
```

The Plugin Buyer first configures the authenticated Aura Composer repository
issued for the Licensed Project. Configuration publishing is optional:

```bash
php artisan vendor:publish --tag=aura-data-exchange-config
```

For pre-tag local development the host uses a Composer path repository and
requires `eminiarts/aura-data-exchange:@dev`; this is not a release install
claim. `AURA_DATA_EXCHANGE_ENABLED=false` is the default. The install command
runs the package migration manifest and
`aura-data-exchange:permissions:sync --all-teams` idempotently. After private
storage, queue, retention, and Role grants are configured, the operator sets the
feature to `true`, rebuilds configuration cache, and restarts web/queue
workers. The package contributes no Resource action, route, navigation,
listener, Permission write, or worker behavior while disabled.

The package owns queue and scheduler documentation. Enablement requires a
private filesystem disk, a running queue worker, and a scheduled
`aura-data-exchange:purge` retention command. That existing command deletes
only expired payloads/artifacts/summaries; it never drops schema or edits the
migration ledger and must not be confused with
`aura-data-exchange:uninstall --purge --force`.

### Authorization and tenancy

The package owns these default abilities and permission slugs:

| Ability | Default permission |
|---|---|
| `aura-data-exchange.view` | `view-aura-data-exchange` |
| `aura-data-exchange.export` | `export-aura-data` |
| `aura-data-exchange.import` | `import-aura-data` |
| `aura-data-exchange.download` | `download-aura-data-exchange-artifacts` |
| `aura-data-exchange.manage-mappings` | `manage-aura-data-mappings` |

- These five definitions are the package's permission manifest. The exact
  command is `aura-data-exchange:permissions:sync {--team=*} {--all-teams}` and
  follows the common existing-Team, new-Team, re-enable, and upgrade behavior.
  The current `registerExistingTeams()` provider-boot write must be removed.
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
2. Run `php artisan aura-data-exchange:uninstall --no-interaction`. This is the
   default, read-only preflight and retains mappings, runs, row results, private
   artifacts, Permission rows, and every migration ledger id, including the
   four current ids listed below.
3. Run `composer remove eminiarts/aura-data-exchange`; later require plus
   `aura-data-exchange:install` preserves and reuses that data.

Intentional destruction is a separate branch before Composer removal:

```bash
php artisan aura-data-exchange:uninstall --purge --force --no-interaction
composer remove eminiarts/aura-data-exchange
```

The destructive manifest initially contains these exact ledger ids:

- `create_aura_data_exchange_tables`;
- `change_aura_exchange_retry_payload_to_text`;
- `upgrade_aura_exchange_row_identity_and_child_deduplication`;
- `upgrade_remove_aura_exchange_retry_payload`.

PLUGIN-02 must prefix any future migration id with `aura_data_exchange_` and add
it to the manifest. The owned-schema inventory names only the three tables
above and each manifest migration's exact columns/indexes; `aura_exchange_*`
is never used as a destructive match. Purge follows the common reverse-down,
exact-ledger reconciliation and postcondition rules; it never deletes another
package's migration row.

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

On acceptance, Aura approves this new **local** Free Official Plugin identity:

- remote: none selected or provisioned by this ADR;
- local package path: `/Users/bajram/Projekte/aura-comments`;
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

The local package is designated MIT and is not a Commercial Plugin, Aura Pro
entitlement, or addition to the Commercial Target Portfolio. Public repository
provisioning, visibility, Packagist registration, and the first push/release
remain separate owner-approved actions. Local implementation may start after
PLUGIN-PRE acceptance and CORE-16/CORE-25; it does not wait for a remote.

### Install and opt-in

```bash
composer config repositories.aura-comments path ../aura-comments
composer require eminiarts/aura-comments:@dev
php artisan aura-comments:install --no-interaction
```

This is the local development install. A future published stable install may
use `composer require eminiarts/aura-comments:^1.0` only after separate remote,
distribution, and release approval.

Configuration publishing is optional:

```bash
php artisan vendor:publish --tag=aura-comments-config
```

`AURA_COMMENTS_ENABLED=false` is the default. The install command runs the
package migration manifest and `aura-comments:permissions:sync --all-teams`
idempotently. After Role grants are configured, the host enables it, rebuilds
configuration cache, and restarts long-running workers. When disabled, no
panel, route, navigation, listener, Permission write, or query is registered.
Integration occurs only through the accepted CORE-25 record-layout panel
contract; the package never replaces or publishes every Aura Resource view.

### Authorization, tenancy, and history

Default permission slugs are:

- `view-aura-comments`;
- `create-aura-comments`;
- `update-own-aura-comments`;
- `delete-own-aura-comments`;
- `manage-aura-comments`.

These five definitions are the package permission manifest. The exact command
is `aura-comments:permissions:sync {--team=*} {--all-teams}` and follows the
common existing-Team, new-Team, re-enable, and upgrade behavior. No Permission
row is written during provider boot, and synchronization never grants a Role.

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

Disable the package first and rebuild configuration cache. Then run
`php artisan aura-comments:uninstall --no-interaction`; this read-only preflight
retains comments, soft-delete tombstones, revisions, author snapshots,
Permission rows, schema, and the exact package migration ledger rows for safe
Composer removal and reinstall.

Intentional destruction requires
`php artisan aura-comments:uninstall --purge --force --no-interaction` before
Composer removal. The initial manifest uses the exact ledger ids
`aura_comments_create_comments_table` for `aura_comments` and
`aura_comments_create_comment_revisions_table` for
`aura_comment_revisions`; every future id retains the `aura_comments_` prefix.
Purge drops only those two manifest tables, deletes only those exact ledger ids
after verified `down()`, removes only the five exact Permission catalog slugs,
and must satisfy the common zero-schema/zero-ledger postcondition. Role grants
remain host-owned and untouched.

### Test ownership

Aura Comments owns installation/disable/removal/purge tests; migrations on
every supported database; two Resource types; Teams-on/off and two-Team
isolation; all permission/policy combinations; deleted Users; soft/force-deleted
targets; edit/delete history; XSS; pagination; lifecycle events; config cache;
absence of schema; and browser panel behavior. Aura Base owns only the generic
CORE-16/CORE-25 lifecycle and panel-extension contract tests with a fake
consumer.

## Compatibility matrix

This extension program targets the actual Aura Base V1 line. Package major
versions are independent of the core major, but neither plugin may claim an
unreleased Aura 2.x contract:

| Component | Supported contract |
|---|---|
| Aura Base saved views | Aura Base V1, inside `eminiarts/aura-cms` |
| Aura Data Exchange 1.x | `eminiarts/aura-cms:^1.0` |
| Aura Comments 1.x | `eminiarts/aura-cms:^1.0` |
| Local pre-tag Aura checkout | `1.0.x-dev` path version plus `^1.0@dev`; never a fake stable or 2.x version |
| PHP | `^8.4`; core matrix covers 8.4 and 8.5 |
| Laravel | 12.x and 13.x |
| Livewire | 4.x |
| Testbench | 10.x for Laravel 12; 11.x for Laravel 13 |
| Teams | Teams-on and Teams-off are equally supported |
| Databases | SQLite 3.26+, MySQL 8.0, and PostgreSQL 16 only |

Before its first tag, Aura Data Exchange must remove its legacy Aura 0.2/Aura
2.0 and Laravel 11 claims and resolve against this matrix. Aura Comments starts
with this matrix. SQL Server, MariaDB, and every other driver are outside this
contract until Aura Base and the package add a separate, passing gate; Laravel
framework support alone is not evidence that Aura supports a driver.

The database gates are exact:

| CI gate | Engine | Required proof |
|---|---|---|
| `sqlite` | PHP image SQLite, runtime assertion `>=3.26` | full package suite in Teams-on and Teams-off modes |
| `mysql` | `mysql:8.0` | database-contract suite in Teams-on and Teams-off modes |
| `pgsql` | `postgres:16` | database-contract suite in Teams-on and Teams-off modes |

For every package, `database-contract` covers: fresh `*:install`; a second
no-op install; exact indexes/uniqueness/JSON round trips; permission sync twice;
same-Team/cross-Team behavior; safe uninstall and retained-data reinstall;
purge and fresh reinstall; ledger-present/schema-absent and
schema-present/ledger-absent repair; a mixed host/package migration batch; and
confirmation that host schema and non-package migration rows are unchanged.
Data Exchange also runs one queued CSV import and one streamed CSV export per
driver. A driver is not documented as supported until its gate is green in the
release commit.

## Release coordination

1. The required CORE dependencies merge and ship in the Aura Base V1 line
   before either plugin publishes a stable tag. At decision time the newest
   repository tag is `v1.0.0-beta.3`, so no stable Aura release is asserted.
2. PLUGIN-01 ships in the Aura Base changelog/release; it has no independent
   package version.
3. Aura Data Exchange and Aura Comments use independent Semantic Versioning,
   beginning at `1.0.0`. Their `composer.json` files require
   `eminiarts/aura-cms:^1.0`; the first release waits for a stable Aura V1 tag
   containing all of its CORE contracts.
4. A core public-contract removal or incompatible semantic change waits for the
   next Aura Base major. Additive optional contracts may ship in a core minor.
5. Plugin releases test against the lowest supported Aura Base 1.x tag and the
   current V1 branch. A plugin raises its minimum core minor when it adopts a
   newer additive contract.
6. Data Exchange releases are immutable Satis distributions authorized by Aura
   Store. A Comments remote, Packagist registration, and release require
   separate owner approval and are not performed by this decision. Neither
   package has a runtime license request.
7. Each release coordinates its own changelog, upgrade notes, migration
   lifecycle/ledger evidence, permission-sync evidence, Composer
   validation/audit, Pest database matrix, Pint, PHPStan, browser tests where
   applicable, and a fresh-host install/remove/reinstall proof.

## Pre-mortem risks

- **A destination is mistaken for feature acceptance (high):** keep this ADR
  proposed until independent review, keep all three tracker rows blocked on
  their feature dependencies, and require separate implementation review.
- **A disabled package still mutates permissions or exposes routes (high):**
  make disabled-provider behavior an install test, require zero boot SQL writes,
  and keep permission sync in the explicit idempotent command/new-Team
  after-commit path.
- **Package removal destroys operational history (high):** retain data on
  disable/remove; make plain uninstall read-only; require both `--purge` and
  `--force` for destruction; test ledger/schema mismatch recovery.
- **Core and plugin contracts drift (high):** release the required Aura V1
  contracts first and test each plugin against both its lowest supported V1 tag
  and current V1 branch.
- **Aura Comments is pushed without owner authorization (high):** implement
  locally only after dependencies; do not provision a remote, publish, or push
  until the repository owner separately approves those actions.

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
- **Let package migrations modify host/core tables:** rejected because
  uninstall and rollback ownership becomes unsafe. Explicit Permission-catalog
  row synchronization is the narrow non-schema exception defined above.

## Gate consequences

- This ADR is a destination/install-contract candidate, not feature completion.
- PLUGIN-01 remains blocked on CORE-21, CORE-22, and CORE-23.
- PLUGIN-02 remains blocked on CORE-06, CORE-09, CORE-10, CORE-11, CORE-17, and
  CORE-22 and must begin in the existing Data Exchange repository.
- PLUGIN-03 remains blocked on CORE-16, CORE-25, and independent acceptance of
  this ADR. After those dependencies, the authorized target is the local
  `/Users/bajram/Projekte/aura-comments` package; a remote is not required for
  local implementation. Completion still requires its own implementation
  review.
- No plugin task is unblocked until an independent reviewer accepts this ADR
  and the program tracker records the destinations.
- Remote repository provisioning or push for Aura Comments remains outside this
  authorization even after local implementation is unblocked.
