# Package migrations never drop tables they did not create

The Aura install migration's `down()` used to unconditionally drop framework/host tables (`users`, `sessions`, `jobs`, `personal_access_tokens`, …) even though `up()` only creates them when absent — a package rollback could destroy the host application's data. We decided against a full restructuring into versioned per-table migrations (V1 is the schema baseline; splits become useful only once real schema upgrades exist) and instead enforce an ownership rule: `down()` may only drop tables Aura itself created. When Aura is installed into an existing application and merely added columns to a pre-existing table (e.g. `users`), rollback removes only those columns.

## Consequences

- The migration must record (or reliably detect) which tables it created in `up()`.
- Install/rollback safety is covered by tests: fresh install, install into an existing app, and rollback never touching foreign tables.
- The embedded-resource incarnation create and upgrade migrations are intentionally forward-only. Their `down()` methods are non-destructive no-ops because portable metadata cannot prove that destructive rollback still targets the original package-created object.
- Ownership is claimed atomically and advanced with compare-and-swap state transitions in `aura_migration_ownership`; the registry is accepted only when `migration` has an exact primary or unique index. A stale forward state reconciles each claimed DDL artifact with bounded retries and validates exact portable column definitions, the primary key, and final ordered indexes before becoming `owned`. Existing rows are preserved and rows predating the identity columns receive deterministic keys under the non-runtime `legacy` key type. Ownership is never encoded as rows or columns in the runtime incarnation table. Historical marker tuples and columns are retained because an exact host-owned collision cannot be distinguished safely.
- Rolling those migrations back leaves the incarnation table, ownership registry row, added columns, and indexes in place. Correcting a failed deployment requires a forward migration, not destructive rollback.
