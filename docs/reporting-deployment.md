# Reporting projection deployment

Meta-backed numeric reports read only from Aura's additive typed projection. Keep both projection switches disabled until the reporting migrations are installed on every Resource connection.

## Expand and backfill

1. Deploy and run Aura's migrations while `AURA_REPORTING_PROJECTION_ENABLED=false` and `AURA_REPORTING_PROJECTION_READS_ENABLED=false`.
2. Enable `AURA_REPORTING_PROJECTION_ENABLED=true` so committed Resource lifecycle events maintain the projection.
3. Run `php artisan aura:reporting:resync` to reconcile every declared Resource in bounded keyset chunks.
4. Run the same command a second time to close scan/update races. The command is idempotent and always re-reads authoritative current state.
5. Compare representative physical and projected aggregates, including null, malformed, exponent-form, excess-scale, and out-of-range legacy meta values.
6. Enable `AURA_REPORTING_PROJECTION_READS_ENABLED=true` only after the comparison succeeds.

The coordinator and value tables live on each Resource's declared connection. Run the migration and resync process for every application database that hosts Aura Resources.

## Recovery

Duplicate or out-of-order lifecycle events are safe because projection maintenance never treats event identifiers or timestamps as source versions. Re-run `aura:reporting:resync` after queue loss, quiet writes, direct SQL, interrupted workers, or declaration changes.

If a deploy or backfill is interrupted, leave projection reads disabled, correct the failure, and rerun the command. Existing coordinator tombstones and values are reusable; no destructive cleanup is required.

## Rollback

Set `AURA_REPORTING_PROJECTION_READS_ENABLED=false` first. If projection maintenance must also stop, set `AURA_REPORTING_PROJECTION_ENABLED=false`. Preserve the projection migration and its rows so a later deploy can resume reconciliation or rebuild from authoritative Resource and meta state.

Do not drop, rewrite, or cast the `meta` table as part of reporting rollback.

## Reporting query scopes

A Resource may expose a no-argument Eloquent scope to aggregate definitions only by implementing `Aura\Base\Contracts\DeclaresReportingQueryScopes` and returning its exact scope name from `reportingQueryScopes()`. Method existence alone is not authorization. Treat the allowlist as a security boundary: a reporting scope may narrow the already authorized Resource query, but must not remove tenant, owner, soft-delete, or other global scopes.
