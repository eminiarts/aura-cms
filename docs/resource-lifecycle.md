# Resource lifecycle events

Aura resources publish immutable, model-free events for durable integrations such as audit logs, workflows, webhooks, and queued listeners.

## Events

| Event | Event name | Meaning |
|---|---|---|
| `Aura\Base\Events\ResourceCreated` | `resource.created` | Physical row and Aura meta committed for a new resource. |
| `Aura\Base\Events\ResourceUpdated` | `resource.updated` | At least one persisted physical or meta value changed. |
| `Aura\Base\Events\ResourceDeleting` | `resource.deleting` | A successful delete operation is being reported. This after-commit event is not a veto hook. |
| `Aura\Base\Events\ResourceDeleted` | `resource.deleted` | A hard or soft delete committed. |
| `Aura\Base\Events\ResourceRestored` | `resource.restored` | A resource using Eloquent soft deletes was restored. |
| `Aura\Base\Events\ResourceForceDeleted` | `resource.force-deleted` | A force delete committed. |

Every event implements `Aura\Base\Contracts\ResourceLifecycleEvent` and Laravel's `ShouldDispatchAfterCommit`. Event objects contain only public readonly scalar values and nested scalar arrays; they never serialize an Eloquent model, relation, request, or Livewire component.

The common payload is:

- `eventId`: unique UUID for this event.
- `operationId`: UUID shared by events from one operation. The deleting/deleted pair and force-deleted event from one force delete share it.
- `resourceClass`, `resourceType`, `resourceMorphType`, `resourceId`.
- `occurredAt`, resolved `connectionName`, `connectionIdentity`, `table`, and `keyName`.
- `inheritanceColumn`, `inheritanceValue`, `scopeMode`, owner/team columns and IDs, and `sharedAcrossTeams`.
- `hardDelete`, which distinguishes removal from a restorable soft deletion.
- `physicalChanges` and `metaChanges`, keyed by stored field name. Each value is `['old' => scalar|null, 'new' => scalar|null]`.

Payload values represent raw stored state. Creates compare an empty old snapshot with the committed state. Updates include physical timestamps alongside a business change, but a timestamp-only write does not emit `ResourceUpdated`. Hard deletes compare the last stored state with `null`. Soft deletes report only the retained row's stored transition, normally `deleted_at` and `updated_at`, while retained meta has no removal delta. Restore changes include the restored physical columns. Meta values are read after Aura's existing `metaSaved` persistence step, so an event never exposes pending form state.

## Timing and ordering

Resource `save()`, `saveOrIgnore()`, and `delete()` boundaries are transactional. When the caller already owns a transaction, Aura joins it and Laravel holds lifecycle listeners until the outermost successful commit. A rollback discards the events.

Create and update ordering is:

1. Aura normalizes physical and meta-backed field input.
2. Eloquent persists the physical row and fires its legacy model events.
3. Aura persists meta and fires the legacy `metaSaved` model event.
4. Aura captures the final stored snapshot and submits the typed event.
5. Laravel invokes lifecycle listeners after commit.

Native synchronous Eloquent `deleting` listeners remain the supported veto hook. Returning `false` prevents the row deletion, dependent cleanup, and typed lifecycle events. Typed `ResourceDeleting` is deliberately delivered only for a successful operation and therefore runs after commit. Existing Eloquent and `metaSaved` listeners keep their synchronous behavior.

Soft delete retains all dependents. A hard delete, including force delete, removes only rows Aura can identify exactly:

- `meta` rows matching both `metable_type` and `metable_id`.
- `post_relations` rows matching type and id on either the resource or related side.

Cleanup uses the resource's database connection and shares the delete transaction. A cleanup or synchronous legacy-listener failure rolls back the physical delete. Generic attachments, options, plugin tables, and application-owned rows are not deleted by this contract.

## Listener failure policy

Lifecycle listeners are post-commit observers. An exception from a synchronous create, update, or restore listener is propagated to the caller, but the committed resource mutation is not rolled back. Deletion is terminal: exceptions from synchronous deleting, deleted, and force-deleted lifecycle listeners are reported through Laravel's exception handler, while Aura continues the remaining typed and native terminal notifications. This prevents one observer from stranding a committed force delete in an incomplete state. Queueable listeners follow Laravel's normal queue retry and failure policy. Put fallible or remote work in queued listeners.

## Suppression and explicit writes

Laravel's `Model::withoutEvents()`, `saveQuietly()`, `deleteQuietly()`, and force-delete quiet variants intentionally suppress lifecycle events. Exact hard-delete cleanup still runs because referential integrity does not depend on event delivery. Callers that need the complete normalization, persistence, cleanup, and event pipeline must use the normal Resource write methods rather than quiet or query-builder writes.

`Aura\Base\ResourceLifecycle\ResourceLifecycleDispatcher` is the public dispatcher seam for Aura's explicit write pipeline and first-party integrations. Its `beginSave()`, `beginDelete()`, and `beginRestore()` methods return a controlled, process-local operation snapshot bound to the resource object, stored subject, resolved connection fingerprint, table, operation kind, and the active native model callback sequence. Lifecycle state is intentionally not serializable. The matching `dispatchSaved()`, `dispatchDeleted()`, `dispatchRestored()`, and `dispatchForceDeleted()` methods validate those invariants, verify the persisted row transition and native callback completion, and consume the operation token exactly once. Mixed resources, connections, operation types, premature dispatch, stale state, cloned replay, and serialized replay are rejected. Ordinary consumers should listen for the event classes instead of dispatching them manually.
