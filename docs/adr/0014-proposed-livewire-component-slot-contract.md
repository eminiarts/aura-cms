# Proposed: two supported Livewire component slots

**Status:** Proposed for CORE-20 approval on 2026-08-09. This is a design gate,
not an implemented or supported API yet.

Aura should expose exactly two replaceable Livewire surfaces in the first public
component-slot contract:

| Stable slot | Aura default | Host config key | Private Livewire transport ID |
|---|---|---|---|
| `global-search` | `Aura\Base\Livewire\GlobalSearch` | `aura.component-slots.global-search` | `aura-slot-5d08acbafc1799908d00c98ba128984f725d8bc43d13679c7689c9e24e2c107c` |
| `media-manager` | `Aura\Base\Livewire\MediaManager` | `aura.component-slots.media-manager` | `aura-slot-f16ee9c2b47b1df672e85903a69ffc98066ccd885e96e5f18536c737f00c5a88` |

The stable names, config keys, registration and resolution rules, mount inputs,
required events, and layout/authorization responsibilities below are the public
contract. The long `aura-slot-*` IDs are fixed SHA-256-derived, non-namespaced
implementation identifiers. Aura-owned views use them, but applications and
plugins register against stable slot names and must not register or mount the
transport IDs directly.

Implementation remains blocked until this contract is approved. The registry
must not ship until Aura's three affected defaults also pass the conformance and
authorization prerequisites below. Existing documentation must not advertise the
proposed keys or registration method before the runtime and tests land.

## Correct Livewire 4 baseline

Aura does **not** register a Livewire class or view namespace named `aura`.
`AuraServiceProvider::bootLivewireComponents()` installs a
`resolveMissingComponent` callback whose map supplies `aura::*` names. In
Livewire 4.3.5, the Finder first checks a registered namespace; because Aura has
none, the Factory reaches that callback, adds the returned class to the Finder,
and caches the resolved name/class pair.

Consequently, `aura.components.media-manager` works today: its value is placed in
the missing-resolver map for both `aura::media-manager` and the conventional dot
name. CORE-20 must preserve that host override. The actual gap is deterministic
plugin participation: a plugin calling `Livewire::component()` or adding another
missing resolver competes through provider/resolver order, has no provenance or
conflict diagnostics, and can be masked by the Factory's first-resolution cache.

Both existing names for each surface are compatibility aliases:

| Slot | Blade-style alias | Existing dot alias |
|---|---|---|
| `global-search` | `aura::global-search` | `aura.base.livewire.global-search` |
| `media-manager` | `aura::media-manager` | `aura.base.livewire.media-manager` |

All four aliases track the final host/plugin/default winner through the major
following CORE-20. Keeping the dot aliases on Aura's default would let
FQCN-derived or saved callers bypass the selected winner, so default-only
behavior is rejected.

## Why the boundary is this small

- Global search is the requested package-level replacement surface and has no
  equivalent host/plugin arbitration contract today.
- Media manager already has a working host-only config override. It becomes a
  slot so a host and enabled plugins can participate with deterministic
  precedence while the existing host behavior remains compatible.
- The other surfaces either have a narrower working extension mechanism or carry
  a substantially larger state/security contract. Exposing them would freeze
  implementation details without a present use case.

## Surface inventory

| Surface | Current seam and runtime coupling | CORE-20 decision |
|---|---|---|
| Global search | The app layout mounts `aura::global-search` behind `aura.features.global_search`. It receives no mount data and its overlay consumes the browser `search` event. Results require resource, record, destination, and current-team authorization. | Add `global-search`; harden the Aura default before release. |
| Media manager | The existing `aura.components.media-manager` value is returned by Aura's missing resolver. The uploader opens `aura::media-manager` with owner/field/selection data; the modal host adds attributes; confirmation broadcasts `updateField`. | Add `media-manager` for deterministic plugin precedence, preserve the legacy host key, and harden the owner/event boundary before release. |
| Dashboard, profile, settings | `routes/web.php` passes their `aura.components.*` values directly as full-page route actions. | Keep the direct config API; do not duplicate it as slots. |
| Resource index/create/edit/view pages | Each Resource exposes `indexComponent()`, `createComponent()`, `editComponent()`, and `viewComponent()` and routes call those FQCNs directly. | Keep the resource hooks; no global slots. |
| Navigation | Reads user/team preferences and exposes broad shell state/actions. | Exclude; retain resource navigation declarations, hooks, and published views. |
| Notifications | Coupled to the notifications slide-over, authenticated relations, and mutation actions. | Exclude pending a dedicated provider contract. |
| Modal host | Owns dynamic child resolution, stack identity, sizing, persistence, and slide-over behavior for every modal. | Exclude; it is infrastructure, not a leaf replacement. |
| Layout and guest navigation | Blade components/views are configurable or publishable, not interchangeable Livewire leaves. | Keep existing seams. |
| Tables, fields, uploader, attachment details, resource editor, auth/team components | Internal parent/property/event coupling. Fields and Resource pages already have extension hooks. | Exclude. `MediaUploader` is hardened as supporting infrastructure, not exposed as a third slot. |
| Widgets and record-page regions | Multi-contributor, ordered composition belongs to CORE-25/CORE-27. | Reserve for those tasks; slots remain one-for-one. |

## Accepted component type and exact boot validation

Host and plugin candidates are PHP class strings only. Aliases, objects,
closures, Blade names, and Volt paths are rejected. Every declaration is
validated, including shadowed candidates; an invalid declaration never falls
through to another layer. Aura's two defaults must pass the same structural
validator before release. Every failure names the slot, source (`host`, Composer
package, or `aura`), candidate class/value, and violated requirement.

For each candidate, boot-time validation must prove all of the following:

1. Trim one optional leading `\`, autoload the class, and require
   `ReflectionClass::getName()` to equal that canonical, case-correct FQCN.
2. Require a class—not an interface, trait, enum, or anonymous object—that extends
   `Livewire\Component`, is non-abstract, and is instantiable.
3. Require no constructor or a constructor with zero required parameters.
   Livewire's Factory performs `new $class` and does not constructor-inject.
4. If `mount()` exists, require it to be public and non-static. For every named
   slot input, require either a writable public, non-static, non-readonly property
   declared on the candidate or an ancestor below `Livewire\Component`, or a
   `mount()` parameter with the same name. If both exist, both must accept the
   input type. Untyped and `mixed` accept the contract; nullable, union, and
   intersection types accept it only when every value the slot may supply is
   valid for that declaration.
5. Reject any other required `mount()` parameter that is builtin, enum,
   `UrlRoutable`, union/intersection, or otherwise expects caller data. For an
   extra named non-builtin dependency, reflect that exact parameter and mirror
   Laravel's `BoundMethod`: use its default only when available and the reflected
   type is not container-bound; otherwise call `Container::make()` for the
   reflected FQCN. Resolution succeeds only when the returned object is an
   instance of that declared class or interface. An unbound interface, wrong
   interface binding, scalar/null result, or thrown construction fails boot.
   Other extra parameters must be optional or variadic, so Aura never invents
   caller data.
6. For `media-manager`, require these public, non-static, declared-`void`
   methods with the exact parameter names, order, types, and defaults shown in
   the correlated protocol: `requestMediaSelection`,
   `acknowledgeMediaSelection`, and `expireMediaSelection`.
   `acknowledgeMediaSelection` must carry Livewire's
   `#[On('aura-media-selection-acknowledged')]` attribute.
7. If `modalClasses` exists for `media-manager`, require exactly a public static
   method with zero parameters and declared return type `string`. Invoke it
   once during validation and require a non-empty value of at most 512 bytes with
   no ASCII control characters. Absence uses Aura's default modal width.

Type compatibility for supplied values is exact:

| Input | Values Aura may supply | Compatible named types |
|---|---|---|
| `model` | canonical Resource class string | `string`, `mixed`, or a union containing `string` |
| `slug` | non-empty field slug string | `string`, `mixed`, or a union containing `string` |
| `selected` | `list<int|string>` or `null` | a type accepting both `array` and `null`, or `mixed` |
| `ownerToken` | non-empty opaque string | `string`, `mixed`, or a union containing `string` |
| `modalAttributes` | the array shape below | `array`, `iterable`, `mixed`, or a union containing one of them |

These reflection checks establish only mountability. They cannot prove rendered
DOM, event behavior, or authorization, so the conformance/security suite remains
a release gate for Aura defaults and a required certification suite for third-
party candidates.

There is no marker interface in V1. Nominal membership would not prove the
behavior that matters and would duplicate the concrete checks above.

## Slot contracts

### `global-search`

- **Mount inputs:** none. `mount()` may use resolvable container injection or
  optional parameters; it may not require caller-supplied data.
- **Inbound event:** consume the bubbling browser `search` event with no required
  payload. Aura's shell owns keyboard/button dispatch; the component owns overlay
  open/close and focus behavior.
- **Outbound events:** none required.
- **Layout:** render one inline Livewire root for one singleton mount in
  `aura::components.layout.app`; never apply a full-page layout. Aura mounts the
  winner only while `aura.features.global_search` is enabled.
- **Authorization:** require an authenticated current actor on initial mount and
  every hydration. Before any resource query, authorize `viewAny` and apply the
  current-team visibility boundary. Before emitting every result/destination,
  authorize `view` on that exact freshly loaded record. A replacement may be
  stricter, never weaker; route or Livewire persistent middleware is defense in
  depth, not the authorization implementation.

Aura does not promise the default component's properties, result shape,
bookmarks, DOM, CSS, or Alpine implementation as part of this slot.

### `media-manager`

- **Named mount inputs:**
  - `model`: canonical `class-string<Aura\Base\Resource>` for the form owner;
  - `slug`: target field slug;
  - `selected`: `list<int|string>|null`;
  - `ownerToken`: Aura's authenticated opaque owner token described below; and
  - `modalAttributes`: at least `persistent: bool`, `modalClasses: string`, and
    `slideOver: bool`, supplied by Aura's modal host.
- **Required actions:** expose
  `requestMediaSelection(array $value): void`,
  `acknowledgeMediaSelection(string $ownerToken, string $requestToken, string $outcome, ?string $errorCode = null): void`, and
  `expireMediaSelection(string $requestToken): void` with the behavior below.
- **Required events:** dispatch `aura-media-selection-requested` and consume
  `aura-media-selection-acknowledged` with the exact named payloads below. The
  former slug-only `updateField` broadcast is not part of the new protocol.
- **Other events:** `selectedRows`, `tableMounted`, `selection-changed`,
  `media-uploaded`, and `media-manager-selected` remain implementation details.
- **Layout:** render one inline root inside Aura's modal panel. Confirmation
  enters an observable pending state, disables duplicate submission, and closes
  only after the correlated success acknowledgement has been verified.
- **Authorization:** treat every mount/event value as untrusted. Resolve the token
  to fresh server state, require `model` and `slug` to match it, authorize the
  owner action, enforce current-team attachment visibility, and authorize every
  attachment view or create operation. Admin middleware alone is insufficient.

Aura does not promise the built-in attachment table/uploader, child tree, DOM,
CSS, or selection-sync events as part of this slot.

## Opaque media owner token

Slug-only global broadcasts are not safe when two forms contain the same field
slug. CORE-20 therefore includes a token broker and changes Aura's default media
event path before exposing `media-manager` as a slot.

The owner component issues one cryptographically authenticated and encrypted,
base64url token per mounted media field. The payload binds a 256-bit random nonce,
the owner Livewire component ID, canonical Resource class, persisted key or
`null`, action (`create` or `update`), field slug, authenticated user identifier,
and current-team identifier. Consumers treat the token as opaque. Possession is
not authorization: the broker must verify integrity and actor/team binding, then
the component reloads and authorizes the represented state.

- For `create`, authorize `create` on the owner Resource before accepting field
  state. For `update`, reload the exact owner through current-team visibility and
  authorize `update` on the record.
- The owner keeps a digest of its expected token in a Livewire `#[Locked]`
  property. Its `applyMediaSelection` listener compares the presented token
  digest in constant time, verifies the matching slug, re-resolves the owner
  context, reauthorizes, validates the attachment ID list, and ignores every
  event for another token. The locked property is tamper-resistant component
  state, not a substitute for verifying and authorizing the token again.
- The manager retains the mounted `ownerToken` only in `#[Locked]` state and
  verifies it again when confirmation, acknowledgement, or timeout actions run.
  A client mutation of the mounted model, slug, selection, or token cannot change
  the owner context recorded by the broker.
- Media manager requires `viewAny` for Attachment, scopes the listing to the
  current team, authorizes `view` on every displayed, preselected, and confirmed
  attachment, and rejects foreign/missing IDs rather than filtering silently.
- Media uploader performs those same read checks. Before storing bytes or
  creating a row it also authorizes `create` for Attachment and the token's owner
  action; denial leaves no stored file and no database row. Standalone library
  upload receives an Aura-issued library token with the same actor/team binding.

## Correlated media selection acknowledgement

A Livewire server dispatch is returned to the browser as an effect. A listener on
another component runs in a later HTTP request; the manager's confirmation action
cannot synchronously await the owner listener. CORE-20 therefore defines this
three-request protocol and forbids closing from the first request:

1. The manager action `requestMediaSelection(array $value): void` normalizes and
   authorizes the selected Attachment IDs, asks Aura's server-side selection
   broker for an opaque 256-bit `requestToken`, stores only its digest and pending
   timestamp in `#[Locked]` state, and dispatches
   `aura-media-selection-requested` with the named payload
   `ownerToken: string`, `requestToken: string`, `slug: string`, and
   `value: list<string>`. The returned browser effect launches the owner request;
   the manager remains open and visibly pending.
2. Aura's owner infrastructure listens with
   `#[On('aura-media-selection-requested')]` on
   `applyMediaSelection(string $ownerToken, string $requestToken, string $slug, array $value): void`.
   Non-matching owner components ignore the global event. The matching owner
   atomically claims the pending request, verifies both tokens and the value
   digest, reloads and authorizes the owner and attachments, and commits the new
   value to its authoritative Livewire field state. If that owner uses an
   immediate durable write, its transaction must also commit before success; the
   protocol does not otherwise save a create/edit form prematurely.
3. The owner atomically records `succeeded` or `failed` in the broker, then
   dispatches `aura-media-selection-acknowledged` with named payload
   `ownerToken: string`, `requestToken: string`, `outcome: 'succeeded'|'failed'`,
   and `errorCode: string|null`. This browser effect launches a third request to
   the manager's `acknowledgeMediaSelection(...)` listener. Event fields are
   untrusted hints: the manager compares both token digests and reads the
   authoritative broker outcome before changing UI state. Success requires a
   null error code; failure uses a non-sensitive machine code recorded by the
   broker.
4. On authoritative success, the acknowledgement action marks the local request
   settled and only then emits the local dialog-close effect. On failure it keeps
   the modal open, clears pending state, shows a non-sensitive error, and permits
   a retry with a new request token. It ignores unrelated, forged, stale, and
   duplicate acknowledgements.

The broker record binds the request token digest, owner token digest, manager and
owner Livewire component IDs, actor/team, slug, normalized value digest, issued
time, deadline, and state (`pending`, `processing`, `succeeded`, `failed`, or
`expired`). It lives in server-backed shared storage with atomic transitions and
a short TTL so separate PHP workers observe the same state. Knowing either token
cannot manufacture success because only the owner-side mutation may transition a
request to `succeeded`.

While pending, the Aura-owned client timer invokes
`expireMediaSelection(string $requestToken): void` in a separate manager request.
The broker resolves the timeout/success race atomically: an expired request keeps
the modal open with a retryable error, while an already-succeeded request follows
the normal success path. A late owner request cannot mutate an expired request.
Repeated request effects, owner handlers, acknowledgements, and timeout actions
are idempotent; a settled request never applies the value or closes twice.
Expected validation/authorization/persistence failures return a generic failed
acknowledgement. If transport or process failure prevents that effect, timeout is
the required recovery path. Timeout duration and presentation are private V1
details, but pending, success, failure, and retry behavior are contractual.

The owner-token broker, selection broker, and locked owner-listener behavior are
Aura infrastructure, not optional responsibilities that a replacement may
bypass. A legacy custom media manager must implement the three required actions,
accept the owner token, use Aura's broker plus the request/acknowledgement events,
and pass the new conformance suite. A class that cannot accept `ownerToken` or
lacks an action fails structural boot validation; a class that accepts the
signatures but still emits the former slug-only event, bypasses the broker, or
closes after its first request fails conformance. Both produce an actionable
migration failure, and no insecure fallback is retained.

## Default-component release prerequisite

The current registry proposal must not imply that existing defaults already meet
the new contract. CORE-20 cannot be released until runtime hardening and tests
prove:

- Aura `GlobalSearch`: authenticated mount/hydration, `viewAny` before querying,
  current-team isolation, per-record `view`, and authorized destinations.
- Aura `MediaManager`: verified owner token/context, owner `create` or `update`,
  Attachment `viewAny`/`view`, scoped selected IDs, pending/error UI, correlated
  acknowledgement, timeout, retry, and close-after-success behavior.
- Aura `MediaUploader`: the same owner/read checks plus Attachment `create` before
  storage or persistence, with no denied/orphaned side effects.
- Aura's media-field owner infrastructure: locked owner-token digest, atomic
  request claiming, fresh authorization, authoritative state mutation, broker
  settlement, and idempotent success/failure acknowledgement.

This prerequisite applies even when no host or plugin replacement is configured.

## Registration and deterministic resolution

The host config ships with `null` values, not default classes:

```php
'component-slots' => [
    'global-search' => null,
    'media-manager' => null,
],
```

Non-null means an explicit host choice. Defaults stay outside the map so a
package default is distinguishable from a host deliberately selecting it.

An enabled plugin declares candidates from its non-deferred provider's `boot()`:

```php
Aura::registerComponentSlots(
    source: 'vendor/package',
    slots: [
        'global-search' => PluginGlobalSearch::class,
    ],
);
```

`source` is the lowercase Composer package name. The public signature is
`registerComponentSlots(string $source, array $slots): void`; `$slots` maps stable
slot names to class strings. Registration only records declarations.

For each slot, resolution is:

1. non-null host value in `aura.component-slots` (or the adapted legacy media
   host value below);
2. the single distinct valid plugin candidate;
3. Aura's internal default.

This is value precedence, never provider order. The registry has three explicit
states: `collecting`, `finalizing`, and `finalized`. Declarations are accepted only
while collecting and sorted by source for diagnostics. An
`Application::booted` callback transitions to finalizing after providers have
booted, validates/selects/registers the winners, then transitions to finalized.
Public winner resolution before finalized, and every declaration or mutation
after collecting, throws a specific exception.

### Duplicates and conflicts

- Same source + slot + class is idempotent.
- Same source registering two classes for one slot is an error.
- Different sources registering the same class collapse to one distinct
  candidate while retaining all sources in diagnostics.
- Different plugin classes are ambiguous and fail boot unless a valid host choice
  wins; shadowed candidates remain visible in diagnostics.
- Unknown slots, malformed sources, and invalid classes fail boot even when
  shadowed by a higher-precedence value.
- Slots are never mergeable. Ordered panels/widgets/actions need their own API.

## Collision-safe Livewire integration

The two transport IDs are full SHA-256 derivations of
`eminiarts/aura-cms|component-slot:v1|<slot>`. They contain no `::`, cannot be
selected through config, and are improbable conventional application names.
Collision resistance reduces accidents; it does not replace detection.

Aura must finalize in this order:

1. Transition from collecting to finalizing, resolve and structurally validate
   every declaration, select each winner, and freeze that provisional winner map.
2. Before registering anything, inspect Livewire's current Finder and Factory for
   each transport ID and all four compatibility aliases. Snapshot the raw explicit
   class/view registrations, class/view namespaces, locations, resolver list, and
   Factory cache; run Finder's non-mutating conventional and single-/multi-file
   discovery; and invoke each pre-existing non-Aura missing resolver directly for
   the normalized identifier without asking the Factory to resolve or cache it.
   Detect every explicit, conventional, discoverable, resolver, already-resolved,
   and cache claim. Aura's known resolver is excluded by identity only. Any
   other claim fails boot with identifier, collision kind, and resolved target;
   even a claim for the same class is not silently adopted.
3. Register `Livewire::component($transportId, $winner)` exactly once.
4. Immediately resolve the ID through Livewire's Factory and assert both the
   normalized name and canonical class equal the expected pair. A mismatch fails
   boot; the successful resolution intentionally pins the Factory cache.
5. While finalizing, allow only Aura's own missing resolver to read the frozen
   provisional map. Prime both Blade-style and dot aliases for each slot through
   normal Factory resolution and assert all four resolve to the same final winner.
   Then transition to finalized. Any exception aborts application boot; there is
   no partially usable registry. Neither preflight nor assertion may delete,
   overwrite, or reorder a third-party registration or resolver.

### Supported Livewire-internals adapter

Livewire 4.3 has no public API that exposes all Finder registrations and Factory
cache entries. CORE-20 therefore introduces one internal
`Livewire43CollisionInspector`; no registry code outside that adapter may reflect
Livewire's protected state. The first implementation supports
`livewire/livewire: ~4.3.5` and changes Aura's Composer constraint from `^4.0` to
that range in the same release. Unsupported minor versions fail dependency
resolution during install/update, rather than first breaking an application at
ordinary boot after a nominally allowed upgrade.

An adapter factory reads the installed version through Composer's
`InstalledVersions`, selects only an adapter whose declared range contains that
version, and has no generic reflection fallback. Absence of an adapter is the
dedicated compatibility failure.

The adapter owns a version/shape compatibility check for the exact Finder and
Factory classes, properties, collection shapes, and method signatures it reads.
That check runs before Aura installs its resolver or begins finalization and
throws a dedicated unsupported-internals exception, never a generic reflection
error or a false slot-collision diagnostic. A supported-but-modified vendor build
therefore fails intentionally before registry mutation. Normal component
resolution after the complete collision preflight remains an assertion, not the
collision detector.

CI tests the lowest supported version (`4.3.5`) and the latest resolvable `4.3.x`
patch, with the adapter shape test plus every collision category on both. Before
widening the Composer constraint to another Livewire minor, Aura must inspect that
minor, add or affirm an explicit adapter, and pass the same lowest/latest matrix.
If Livewire later exposes a public collision API, a new adapter should use it
instead of protected-state reflection.

Aura must continue to register **no** Livewire namespace named `aura`. Its missing
resolver becomes dynamic for the four slot aliases: while collecting it rejects
early resolution, while finalizing it serves only the internal assertion from the
frozen provisional map, and after finalization it returns the frozen winner. All
other existing map entries retain current behavior.

Aura-owned layouts and modal emitters switch to the transport IDs. All four old
aliases remain supported through the major following CORE-20 and track the
**final winner**—host, plugin, or Aura default—on initial mount and hydration.
This is the safest compatibility rule because published views, FQCN-derived
names, saved snapshots, and existing callers keep addressing the same semantic
surface. The aliases are compatibility names, not plugin registration hooks;
direct registration under either alias form remains unsupported and a detected
pre-finalization claim fails boot.

The high-entropy IDs and pinned Factory cache make an accidental post-finalize
overwrite ineffective in the running container. Calls through Aura's registry
after finalization always throw. Direct mutation of Livewire internals after
application boot is outside the supported extension contract and must never be
used as a fallback.

## Legacy media-manager config

`aura.components.media-manager` is a working, published host override. CORE-20
adapts and deprecates it without changing its precedence:

- non-null `aura.component-slots.media-manager` is the host choice;
- otherwise, a legacy value different from
  `Aura\Base\Livewire\MediaManager::class` is the host choice;
- the shipped legacy default means "no host choice" so a unique plugin may win;
- matching new and custom legacy values are accepted;
- conflicting custom values fail boot with a migration message; and
- every legacy custom class must pass the new structural, owner-token, event, and
  authorization conformance contract. There is no slug-only security fallback.

The legacy key and all four compatibility aliases may be removed only in the next
major after CORE-20. Dashboard, profile, and settings remain under
`aura.components` because their direct route resolution is already deterministic.

## Config cache, workers, and multiple containers

- Config and declarations contain strings/arrays only—no closures, objects,
  container instances, or request/user state.
- The registry and collision inspector are scoped to one Laravel `Application`
  container, never static or cache-store state.
- A second Testbench/application container receives fresh registry, Finder,
  Factory, cache inspection, and aliases; state cannot leak.
- Octane/queue workers reuse one frozen boot map. Config/plugin changes require a
  normal restart/reload; user/team/token state never enters the registry.
- `config:cache` preserves class strings. Finalization, collision checks, explicit
  registration, and assertions run on every application bootstrap.

## Compatibility policy

Once approved and released, stable slot names, config keys, registration
signature, precedence/conflict behavior, accepted candidate structure, named
mount inputs, required media actions/events, owner/request token payloads, and
authorization/layout ownership are public semver commitments.

- Adding an independent optional slot is a minor release when it does not narrow
  existing dependency or candidate support.
- Enforcing an invariant already stated by the released contract is a patch only
  when it does not reject a previously documented valid candidate or payload.
- Newly rejecting a previously accepted candidate/configuration, narrowing the
  supported Livewire range, or changing required mount/action/event data is a
  major release, including when the motivation is security hardening.
- Removing/renaming a slot or changing precedence or authorization ownership is
  also a major release.
- Default DOM/CSS/internal events and private transport IDs are not public API.
- The existing media config key and old aliases have the explicit next-major
  deprecation window above; the owner-token hardening is a security prerequisite,
  not an optional legacy mode.

The existing legacy media-manager seam accepts slug-only replacements today.
Requiring the owner/request-token handshake therefore cannot ship as a patch or
minor under this policy. The safest release vehicle for CORE-20 is the next Aura
major, with an upgrade error that names the incompatible custom class and links
to the migration contract. The aliases then remain available until the following
major as promised above.

## Tests as design

CORE-20 implementation is not complete until focused tests prove this matrix:

| Area | Required proof |
|---|---|
| Baseline | Aura registers no `aura` Livewire namespace; the current missing resolver honors a custom legacy media config before migration. |
| Selection | Defaults; host only; plugin only with provider before/after Aura; host over one/many plugins; duplicate collapse; ambiguous plugins; invalid shadowed declaration. |
| Compatibility | Both `aura::*` aliases and both `aura.base.livewire.*` dot aliases resolve to the same final default/host/plugin winner and hydrate under that alias through the major following CORE-20; FQCN normalization and existing dashboard/profile/settings routes remain unchanged. |
| Collision protocol | Before any registration, separate boot failures for preclaimed explicit class, explicit view, conventional class, class/view namespace, single-file, multi-file, third-party missing resolver, already-resolved Finder entry, and Factory-cache entry for every transport ID and all four aliases; registration followed by exact Factory assertion; no overwrite or fallback. |
| Livewire adapter | Composer accepts `4.3.5` and latest `4.3.x` but rejects unsupported minors; both supported endpoints pass the adapter shape and full collision matrix; a deliberately altered supported shape raises the dedicated compatibility exception before resolver/registry mutation. |
| Lifecycle | Unknown slot/source, same-source conflict, pre-finalization resolution, late registry registration, config cache, Octane reuse, and two independent containers. |
| Structural pass | Property-only, mount-only, mixed property/mount inputs, optional extras, correctly bound concrete/interface DI, all required manager actions, and valid optional `modalClasses`. |
| Structural fail | Noncanonical/missing/non-component/abstract class, required constructor, readonly/static/private or incompatible property, non-public/static `mount`, incompatible or extra required mount scalar, UrlRoutable/enum/union caller dependency, unresolvable or wrong-type DI binding, missing/mistyped manager action or acknowledgement listener, and non-public/non-static/parameterized/untyped/non-string/throwing `modalClasses`. |
| Global-search security | Guest and forged hydration fail; feature flag; `viewAny` precedes queries; record `view`, destination, and current-team isolation hold for every result and for a second request. |
| Owner routing | Two simultaneous forms with the same slug receive distinct tokens; confirmation updates only the matching owner; missing, swapped, tampered, cross-user, and cross-team owner/request tokens fail closed. |
| Acknowledgement lifecycle | Prove three independent Livewire requests (manager request, owner mutation, manager acknowledgement); no close after request one or two; success closes only after broker verification; a client-forged success while the broker is pending cannot close; known failure and transport timeout keep the modal open; retry gets a new token; duplicate/reordered/late request, acknowledgement, and timeout effects are idempotent. |
| Media read/update | Invalid owner class/field/action/key fails; create owner requires `create`; persisted owner requires fresh current-team load plus `update`; attachment listing/selection requires `viewAny` and per-record `view`; foreign IDs fail. |
| Upload | Attachment `create` and owner action are checked before storage; denied/failed uploads leave no file or row; standalone library token is actor/team bound; second requests reauthorize. |
| Browser | Default and one replacement per slot render in the real shell/modal, all aliases remain hydratable, pending state is observable, success closes only after the acknowledgement request, failure/timeout remains retryable, and authorization denials produce no console/JavaScript errors. |

The normal full non-browser suite, relevant browser suite, Pint, and PHPStan
remain required by the project verify loop.

## Alternatives rejected

- **Only add `aura.components.global-search`:** provides a host override but no
  plugin provenance, ambiguity handling, or deterministic host/plugin precedence.
- **Claim legacy media config is broken:** false under Livewire 4.3.5; Aura's
  missing resolver returns the configured class. The slot solves plugin
  arbitration, not a nonexistent host-config failure.
- **Let plugins call `Livewire::component()` or add missing resolvers:** remains
  provider/cache-order dependent and cannot report conflicts.
- **Keep old aliases tied to Aura defaults:** published callers would bypass a
  selected host/plugin winner. Tracking the final winner is more compatible.
- **Keep the dot aliases default-only:** FQCN-derived and saved callers could
  silently bypass the selected winner. They follow the same compatibility rule.
- **Use readable internal IDs without collision checks:** risks conventional,
  discoverable, explicit, or cached collisions and Livewire silently overwrites
  explicit Finder entries.
- **Retain slug-only media events:** one form can consume another form's broadcast
  and the payload carries no authenticated owner/action context.
- **Close when the manager dispatch returns:** that request has only queued a
  browser effect; the owner mutation and acknowledgement have not run yet.
- **Keep `^4.0` with best-effort reflection:** permits an untested minor update to
  alter protected internals and turn a dependency update into an application boot
  outage or silent collision miss.
- **Expose every `aura::*` component:** freezes internal contracts and overlaps
  dedicated record-region/widget work.
- **Make slots mergeable:** conflates replacement with ordered composition.

## Approval questions

1. Approve exactly `global-search` and `media-manager`, class-string candidates,
   host > unique plugin > Aura default precedence, fail-closed collision checks,
   and all four existing aliases tracking the final winner through the major
   following CORE-20?
2. Approve the owner/request-token acknowledgement protocol and default-component
   authorization hardening as a CORE-20 release prerequisite, with CORE-20 shipped
   in the next Aura major because incompatible legacy managers are rejected?
3. Approve `livewire/livewire: ~4.3.5` for the first protected-internals adapter,
   with lowest/latest `4.3.x` CI required before that range may be widened?
