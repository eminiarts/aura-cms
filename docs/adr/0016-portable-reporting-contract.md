# Portable reporting uses exact physical values or an additive typed projection

Status: Accepted for CORE-29 implementation

## Context

Aura's reporting consumers currently embed storage and SQL assumptions:

- `ValueWidget` joins the literal `meta` table, casts values `AS SIGNED`, assumes `created_at`, and implements count/average/sum/min/max itself.
- `Pie` and `Donut` repeat the literal meta join and signed cast, group by a raw label expression, and assume the resource timestamp.
- `Sparkline` groups with `DATE(created_at)`, uses the signed meta cast, and silently turns a requested aggregate on a missing physical metric into a row count.
- the generic `Dashboard` also groups with `DATE(created_at)`.
- `Attachment` has a separate SQLite/MySQL/MariaDB/PostgreSQL month-expression branch.

`meta.value` is long text. Signed integer casts truncate decimals, accept malformed legacy input differently by engine, and are not portable. Direct date functions bucket UTC timestamps rather than the viewer's local calendar.

## Decision

CORE-29 should proceed, but only within this boundary:

1. Introduce one shared, immutable aggregate API consumed by widgets and dashboards. It supports count, sum, average, min, max, optional grouping, and day/week/month/quarter/year buckets.
2. Aggregate declared physical numeric fields directly. Aggregate declared meta-backed numeric fields only through a typed, indexed projection. Safe dialect-specific text casts are approved for backfill validation and shadow comparison, not for interactive runtime aggregation.
3. Do not destructively type or rewrite `meta`, and do not change normal Resource persistence. The projection is additive and disposable.
4. Replace the hard-coded reporting SQL listed above only after equivalent consumers use the shared engine. Table filtering and sorting are separate contracts and are outside CORE-29.

### Public shape

The implementation should expose immutable value objects similar to:

```php
final readonly class AggregateDefinition
{
    public function __construct(
        public string $resource,
        public AggregateOperation $operation,
        public ?string $metric,
        public ?string $groupBy,
        public ?DateRange $range,
        public ?DateBucket $bucket,
        public string $timezone = 'UTC',
    ) {}
}

interface AggregateEngine
{
    public function run(AggregateDefinition $definition): AggregateResult;
}
```

Names may follow established Aura conventions, but these invariants may not change: callers provide no table, SQL expression, Team, owner, connection, or actor identifier; enum-backed operations and buckets are allowlisted; fields resolve from the Resource declaration; and results are immutable. V1 runs only for the currently authenticated actor. Background reporting needs a later explicit trusted-context contract.

### Numeric semantics

- Count counts authorized Resource rows after the half-open time range is applied; it does not require a metric.
- Numeric operations accept declared Number/Currency fields whose configured precision is at most 18 and scale at most 6.
- Values are normalized to a signed scaled integer (`value * 1,000,000`). Missing, null, blank, malformed, exponent-form, excess-scale, and out-of-range legacy meta values are excluded from numeric operations.
- Sum/min/max return a canonical decimal string with six fractional digits. Average is exact scaled sum divided by the valid-value count, rounded half away from zero to six digits. An all-null numeric set returns null. Count returns an integer.
- Overflow fails explicitly; implementations may never fall back to binary floating point.

### Time semantics

- Stored timestamps are UTC. Ranges are half-open: `[start, end)`.
- A valid IANA timezone selects the viewer's calendar. Buckets are local day, ISO week starting Monday, month, quarter, or year. Date-only fields do not shift timezone.
- The portable V1 strategy generates bounded UTC bucket boundaries in PHP and uses a bound `CASE` expression. This handles daylight-saving gaps and overlaps without database timezone-table dependencies.
- A request is limited to 400 bucket points and must reject an invalid timezone, range, or bucket.

### Authorization and tenancy

The engine first authorizes `viewAny` for the resolved Resource, then starts from `Resource::query()` on that Resource's connection. It preserves `TypeScope`, `TeamScope`, `ScopedScope`, soft-delete behavior, `indexQuery()`, and any declared safe query scope. It never accepts client-provided Team/owner predicates and never calls `withoutGlobalScopes()`. Group labels and rows remain inside the same scoped query. This matches the policy/scope contracts already proved by `OwnershipStorageContractTest` and `TeamSharingTest`.

### Projection ownership and lifecycle

CORE-29 may add an Aura-owned `aura_reporting_values` table through an exact, additive migration and ownership manifest. V1 uses Aura's current unsigned-big-integer Resource identity and an exact unique identity `(resource_type, resource_id, field_key)`. A row stores a nullable signed `value_scaled`, precision/scale contract version, and timestamps. Required indexes cover identity and `(resource_type, field_key, value_scaled, resource_id)`. The live authorized Resource query remains the left side of every join; copied Team/owner values are neither required nor trusted.

Projection maintenance listens to the immutable, after-commit `ResourceCreated`, `ResourceUpdated`, `ResourceDeleted`, `ResourceRestored`, and `ResourceForceDeleted` events from CORE-16. A projector re-reads the current Resource on its recorded connection and upserts/deletes idempotently. It ignores stale/out-of-order events based on the latest stored source version and never writes Resource/meta values. Quiet writes deliberately do not emit lifecycle events, so the documented explicit resync command is required after trusted quiet or legacy writes.

Deployment is expand/backfill/verify/cut over:

1. create the table and indexes with reporting disabled;
2. run a resumable, idempotent, per-Resource-class backfill in bounded keyset chunks;
3. enable event projection and repeat backfill to close the race;
4. shadow-read aggregate checks, including rejected legacy values, before enabling meta-backed reads;
5. switch the feature flag per environment.

Rollback first disables projected reads and returns consumers to their pre-CORE-29 behavior. It preserves projection rows and the migration; destructive rollback is not permitted. A later deploy may resume or rebuild the projection entirely from authoritative Resource/meta state.

## Evidence and threshold

The reproducible harness covers SQLite 3.53.1, MySQL 8.4.11, MariaDB 11.8.8, and PostgreSQL 16.14. The correctness matrix proves exact decimals, invalid/null legacy values, numeric ranges, Europe/Zurich daylight-saving buckets, cross-tenant exclusion, and non-empty `EXPLAIN` output for physical, safe-meta, and projection paths. Each benchmark workload performs one query.

At 100k Resources plus 1.19 million meta rows, the accepted interactive gate is p95 at most 500 ms and no more than five times the physical path on every claimed engine. Direct meta casting fails: MySQL grouped sum reaches 1,407.227 ms and MariaDB reaches 3,165.048 ms; MariaDB aggregate and range also exceed 500 ms. The projection's worst p95 is 169.567 ms. Full results and limitations are in `docs/benchmarks/core-28-reporting-baseline.md` and its JSON companion.

The tested engine versions are the CORE-29 claim. Broader minimum-version claims require CI evidence before release. Benchmark thresholds are regression comparisons on a controlled runner, not production latency promises.

## Consequences

- CORE-29 is authorized to implement the bounded API, projection, backfill/resync, feature flag, and migration plan above.
- Runtime aggregation over raw meta text is rejected even where one engine's benchmark happens to pass.
- Physical metrics avoid projection write amplification. Meta numeric reporting gains exact cross-database behavior and indexed ranges at the cost of eventual, after-commit projection maintenance.
- Production reporting consumers must not add new SQL-dialect branches. The shared engine owns dialect-specific exact expressions and time boundaries.
- CORE-29 must add Teams-on/off authorization tests, migration interruption/recovery tests, event idempotency/out-of-order tests, and benchmark regression gates before projected reads default on.
