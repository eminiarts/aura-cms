# Proposed: two supported Livewire component slots

**Status:** Proposed for CORE-20 approval on 2026-08-09. This is a design gate,
not an implemented or supported API yet.

Aura should expose exactly two replaceable Livewire surfaces in the first public
component-slot contract:

| Stable slot | Aura default | Host config key | Private Livewire transport ID |
|---|---|---|---|
| `global-search` | `Aura\Base\Livewire\GlobalSearch` | `aura.component-slots.global-search` | `aura.slot.global-search` |
| `media-manager` | `Aura\Base\Livewire\MediaManager` | `aura.component-slots.media-manager` | `aura.slot.media-manager` |

The stable names, config keys, registration and resolution rules, mount inputs,
required events, and layout/authorization responsibilities below are the public
contract. The `aura.slot.*` IDs are reserved implementation details: Aura-owned
views use them, but applications and plugins register against the stable slot
names and must not register or mount the transport IDs directly.

Implementation remains blocked until this contract is approved. In particular,
the existing documentation must not advertise the proposed keys or registration
method as available before the runtime and its tests land.

## Why the boundary is this small

Livewire 4.3.5 resolves a name containing `::` through its registered class
namespace before consulting explicitly registered class components. Therefore
`Livewire::component('aura::global-search', CustomSearch::class)` does not replace
`Aura\Base\Livewire\GlobalSearch`; the same rule affects every physical
`aura::*` class. Aura's missing-component resolver runs even later and cannot
change that result.

This does not make every Aura component a suitable public replacement surface.
The source inventory shows only two current seams that need the new contract:

- Global search is a documented replacement use case and currently requires a
  published layout workaround.
- Media manager already has a documented `aura.components.media-manager`
  replacement key, but Aura mounts `aura::media-manager`, so the physical Aura
  class can win before that configured class is consulted.

The other surfaces either already have a working, narrower extension mechanism
or carry a substantially larger state/security contract. Exposing them now
would turn implementation details into permanent API without a current use case.

## Surface inventory

| Surface | Current seam and runtime coupling | CORE-20 decision |
|---|---|---|
| Global search | The app layout mounts `aura::global-search` when `aura.features.global_search` is enabled. It receives no mount data and its overlay consumes the browser `search` event emitted by the shell. Search results require per-resource authorization and tenant scoping. | Add `global-search`. |
| Media manager | The media uploader emits `openModal` for `aura::media-manager` with the target model, field slug, and selected IDs. The modal host adds modal attributes. Confirmation emits `updateField`. The existing config replacement is bypassed by namespaced resolution. | Add `media-manager`. |
| Dashboard, profile, settings | `routes/web.php` passes `aura.components.dashboard`, `profile`, and `settings` directly as full-page route actions. These replacements do not depend on Livewire name lookup. | Keep the existing direct config API; do not duplicate it as slots. |
| Resource index/create/edit/view pages | Each Resource exposes `indexComponent()`, `createComponent()`, `editComponent()`, and `viewComponent()` and routes call those FQCNs directly. | Keep the resource hooks; no global slots. |
| Navigation | Mounted once in the authenticated shell; reads user/team preferences, exposes sidebar state/actions, and emits `NavigationMounted`. Replacing it would freeze a broad preference, navigation, and authorization contract. | Exclude. Continue using resource navigation declarations, hooks, and published views. |
| Notifications | Mounted behind a feature flag and coupled to the `notifications` slide-over key, `openSlideOver`, `activate()`, authenticated notification relations, and mutation actions. | Exclude until a dedicated notification-provider design exists. |
| Modal host | Owns the `openModal`/`closeModal` event protocol, modal stack identity, dynamic child resolution, sizing, persistence, and slide-over behavior for every modal. | Exclude. It is infrastructure, not a leaf replacement. |
| Layout and guest navigation | Blade components/views are configurable or publishable and are not interchangeable Livewire leaf components. | Keep the existing view/layout seams. |
| Tables, fields, uploaders, attachment details, resource editor, auth/team components | Internal components with parent/property/event coupling. Fields already have a component field type and resources have page hooks. | Exclude. |
| Widgets and record-page regions | Multi-contributor, ordered, authorized composition rather than one-for-one replacement. CORE-25 and CORE-27 define those contracts separately. | Reserve for those tasks; do not make this registry mergeable. |

## Accepted component type

Both host and plugin candidates must be PHP class strings. A candidate must:

- exist at application boot;
- be concrete and instantiable;
- extend `Livewire\Component`; and
- accept the named mount inputs defined for its slot, using Livewire's supported
  public-property or `mount()` parameter binding.

Livewire aliases, component instances, closures, Blade view names, and Volt file
paths are rejected. A bad winning candidate fails application boot with a message
that names the slot, source, class, and violated requirement. Aura must not
silently fall through to a lower-precedence candidate.

There is no marker interface in V1. Such an interface could prove only nominal
membership, not the browser-event, authorization, or layout behavior that makes
the component compatible. Slot-specific reflection checks plus render/event
contract tests provide useful validation without creating a hollow second API.

## Slot contracts

### `global-search`

- **Mount inputs:** none. A `mount()` hook may use container injection or optional
  parameters, but it may not require application-supplied scalar data.
- **Inbound event:** consume the bubbling browser `search` event with no required
  payload. Aura's shell owns keyboard/button dispatch; the component owns overlay
  open/close and focus behavior.
- **Outbound events:** none required.
- **Layout:** render one inline Livewire root suitable for one singleton mount in
  `aura::components.layout.app`; do not apply a full-page Livewire layout. Aura
  mounts the winner only when `aura.features.global_search` is enabled.
- **Authorization:** the Aura admin route middleware supplies authentication, but
  the component must authorize every searchable resource and destination in the
  current user/team context. The slot registry does not authorize results for a
  replacement.

Aura does not promise the default component's public properties, result shape,
bookmarks, DOM, CSS classes, or Alpine implementation as part of this slot.

### `media-manager`

- **Named mount inputs:**
  - `model`: a `class-string<Aura\Base\Resource>` for the form owner;
  - `slug`: the target field slug;
  - `selected`: a list of integer or string attachment IDs, or `null` for an
    empty field; and
  - `modalAttributes`: an array containing at least `persistent: bool`,
    `modalClasses: string`, and `slideOver: bool`, supplied by Aura's modal host.
- **Required outbound event:** after confirmation, dispatch the Livewire event
  `updateField` with the named argument
  `data: ['slug' => string, 'value' => list<string>]`.
- **Other events:** `selectedRows`, `tableMounted`, `selection-changed`,
  `media-uploaded`, and `media-manager-selected` are implementation details of
  the current table/uploader composition, not slot requirements.
- **Layout:** render one inline root inside Aura's modal panel; do not apply a
  full-page layout. If the class has a public static `modalClasses(): string`,
  Aura may use it; otherwise the slot default width is used. Confirmation must
  complete the `updateField` round trip before closing the dialog.
- **Authorization:** treat all mount/event values as untrusted. The component must
  validate the resource class and field, honor current team scopes, authorize
  attachment visibility and every mutation/upload, and never widen access merely
  because the request passed through the admin middleware.

Aura does not promise the built-in attachment table, uploader, selection-sync
events, child component tree, DOM, or CSS as part of this slot.

## Registration and deterministic resolution

The host config is a map whose shipped values are `null`, not Aura's defaults:

```php
'component-slots' => [
    'global-search' => null,
    'media-manager' => null,
],
```

A non-null value is an explicit host selection. Keeping defaults outside this
map is necessary: otherwise a package default is indistinguishable from a host
that deliberately selected the default, and a plugin candidate could never win.

An enabled plugin registers candidates during its non-deferred service
provider's `boot()` method:

```php
Aura::registerComponentSlots(
    source: 'vendor/package',
    slots: [
        'global-search' => PluginGlobalSearch::class,
    ],
);
```

`source` is the plugin's lowercase Composer package name. The proposed public
method has the signature
`registerComponentSlots(string $source, array $slots): void`, where `$slots` is
an array from stable slot name to component class string. Registration does not
itself resolve or register a Livewire alias.

For each slot, resolution is:

1. a non-null host value in `aura.component-slots`;
2. the single distinct valid plugin candidate;
3. Aura's internal default.

This is value-based precedence, never service-provider order. All declarations
are collected, sorted by source for diagnostics, validated, and frozen from an
`Application::booted` callback after every provider has booted. Registration or
mutation after freeze throws. Resolution is unavailable during provider boot;
providers register declarations there and normal requests resolve after boot.

### Duplicates and conflicts

- The same source registering the same slot/class again is idempotent.
- The same source registering two different classes for one slot is an error.
- Different sources registering the same class for one slot collapse to one
  distinct candidate.
- Different sources registering different classes are ambiguous and fail boot
  unless a valid host selection exists. The host selection deliberately resolves
  that ambiguity and all shadowed candidates remain visible in diagnostics.
- An unknown slot or invalid source/class fails boot even when a higher layer
  would otherwise win.
- No CORE-20 slot is mergeable. A slot always resolves to one class. Ordered
  panels, widgets, actions, and other multi-contributor extension points require
  their own definition and conflict rules.

## Livewire integration boundary

After freezing, Aura registers each winner once under its reserved,
non-namespaced `aura.slot.*` transport ID, then Aura-owned layouts/emitters mount
that ID. Livewire 4 checks explicit registrations for these IDs, so this path
does not depend on provider order, namespaced convention lookup, or
`resolveMissingComponent`.

Mounting only the FQCN is not the chosen transport. Livewire normalizes a class
back to a registered alias when possible; an old explicit `aura::*` registration
could therefore put the namespaced ID back into the component snapshot and
reintroduce the same resolver bug on the next request. A reserved explicit
non-namespaced ID keeps initial mount and hydration on one deterministic path.

The existing `aura::*` aliases remain compatibility names for Aura's concrete
components, not override hooks. Directly registering over them is unsupported and
must be removed from the customization documentation when implementation lands.

## Legacy media-manager config

`aura.components.media-manager` is already published and documented. The first
implementation should adapt it without letting the shipped default block plugin
selection:

- a non-null new `aura.component-slots.media-manager` value is the host choice;
- otherwise, a legacy value different from
  `Aura\Base\Livewire\MediaManager::class` is treated as the host choice;
- the shipped/default legacy class means "no host choice";
- matching new and custom legacy values are accepted; conflicting custom values
  fail with a migration message.

The legacy key is deprecated when the slot implementation ships and may be
removed only in the next major release. Dashboard, profile, and settings remain
under `aura.components` because their direct route resolution is a different,
working contract.

## Config cache, workers, and multiple containers

- Config and plugin declarations contain strings/arrays only: no closures,
  objects, container instances, or request/user state.
- The registry is a singleton owned by one Laravel `Application` container, not
  static state and not a shared cache-store entry.
- The frozen resolved map lives only for that container. A second Testbench or
  application container receives a fresh registry and fresh Livewire Finder;
  registrations cannot leak between them.
- An Octane/queue worker may reuse its frozen boot-time map safely because slot
  selection is deployment configuration, not per-request state. Changing config
  or plugin declarations requires the normal worker restart/reload.
- `config:cache` preserves host class strings. Registry finalization and explicit
  Livewire alias registration still run during each application bootstrap; no
  serialized registry artifact is required.

## Compatibility policy

The following are public semver commitments once approved and released: stable
slot names, host config keys, the registration method/signature, accepted
candidate type, precedence/conflict behavior, named mount inputs, required event
names/payloads, and layout/authorization ownership.

- Adding an independent optional slot is a minor release.
- Fixing validation, diagnostics, or a security defect without weakening the
  stated contract is a patch release.
- Removing/renaming a slot, accepting a different candidate category, changing
  precedence/conflict semantics, or changing a required mount input/event/layout
  responsibility is a major release.
- Default component internals, views, DOM/CSS, optional methods/events, and the
  private `aura.slot.*` transport IDs are not compatibility promises.

## Required implementation proof after approval

CORE-20 implementation is not complete until focused tests prove both slots'
defaults, host-only selection, plugin-only selection with the plugin provider
both before and after Aura, host-over-plugin precedence, idempotent duplicates,
ambiguous plugins, invalid/unknown/late declarations, config caching, event/mount
contracts, feature/auth boundaries, and two independent application containers.
The normal full non-browser suite, relevant browser coverage, Pint, and PHPStan
remain required by the project verify loop.

## Alternatives rejected

- **Only add `aura.components.global-search`:** fixes one host but has no explicit
  plugin provenance, duplicate handling, or deterministic host/plugin precedence.
- **Let plugins call `Livewire::component()` directly:** remains order-dependent,
  fails for physical `aura::*` names on Livewire 4, and cannot report ambiguity.
- **Expose every `aura::*` component:** freezes internal state/event contracts and
  overlaps the dedicated record-region and widget tasks.
- **Make slots mergeable:** conflates one-for-one replacement with ordered
  composition and leaves precedence/authorization undefined.

## Approval questions

1. Approve the initial public surface as exactly `global-search` and
   `media-manager`, with navigation, notifications, the modal host, resources,
   record regions, and widgets excluded?
2. Approve class-string-only candidates and the frozen
   `Aura::registerComponentSlots(source, slots)` API with host > unique plugin >
   Aura default precedence?
3. Approve the legacy `aura.components.media-manager` adapter through the next
   major release?
