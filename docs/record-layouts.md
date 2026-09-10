# Record layouts

Record layout panels are optional Livewire components on the default record detail
view. `Aura\Base\Livewire\Resource\View` resolves the panels for a full page and
for the same view inside a modal. The default view still renders the resource
header, actions, title, and fields.

If no panel remains visible, Aura renders the default record view and does not add
the record-layout wrapper. If a resource overrides `viewView()`, Aura passes the
resolved `$recordLayout` to that view, but the custom view must render the layout
or its panels itself.

This feature is for record detail pages. It does not arrange fields in create or
edit forms. Use the `Panel` and `Tab` field classes for form field layout. It also
does not configure the global application layout, navigation, theme, or sidebar.
Those settings live in `config('aura.views.layout')`, `config('aura.theme.*')`,
the Settings API, and the Navigation API. See
[Creating fields](/docs/creating-fields), [Configuration](/docs/configuration),
and [Themes](/docs/themes).

## Regions

Every panel belongs to one `RecordLayoutRegion` case:

| Case | Value | Location in the default record view |
| --- | --- | --- |
| `HeaderActions` | `header-actions` | The view header, before Aura's resource actions and Edit button |
| `LeftSummary` | `left-summary` | The left three-column aside when panels are present |
| `MainContent` | `main-content` | After the default resource fields in the main column |
| `RightSidebar` | `right-sidebar` | The right three-column aside when panels are present |
| `ActivityTimeline` | `activity-timeline` | After main-content panels in the main column |

The default record fields remain in `main-content`. The layout changes the main
column to six columns when both side regions contain panels, or to nine columns
when only one side region contains panels. Panels in one region are ordered by
ascending `order`, then by their source string, then by their key.

## Register a plugin panel

Register panels while service providers are booting. With Spatie's
`PackageServiceProvider`, `packageBooted()` is the package hook to use. Do not put
the registration in `$this->app->booted()`. Aura finalizes the registry from its
application `booted` callback and rejects later registrations.

The following provider registers a panel for a host resource whose slug is
`contact`. The example also registers the boolean preference used by the panel.
The host resource must define an `owner()` relationship because the panel asks
Aura to eager-load it.

```php
<?php

namespace Acme\Contacts;

use Acme\Contacts\Livewire\ContactHealthPanel;
use Aura\Base\Facades\Aura;
use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ContactsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('contacts')->hasViews('acme-contacts');
    }

    public function packageBooted(): void
    {
        app(PreferenceRegistry::class)->register(new PreferenceDefinition(
            key: 'contacts.show-health',
            type: PreferenceValueType::Boolean,
            default: true,
            scopes: [PreferenceScope::User, PreferenceScope::Team],
            resourceAware: true,
        ));

        Aura::registerRecordLayoutPanels('acme/contacts', [
            new RecordLayoutPanel(
                key: 'contact-health',
                region: RecordLayoutRegion::RightSidebar,
                component: ContactHealthPanel::class,
                order: 20,
                resources: ['contact'],
                ability: 'view-contact-health',
                preferenceKey: 'contacts.show-health',
                eagerLoad: ['owner'],
            ),
        ]);
    }
}
```

The panel component must accept the canonical record as `model` and the page or
modal state as `inModal`. It can use public properties, `mount()` parameters, or
one of each. This component uses public properties and a package view:

```php
<?php

namespace Acme\Contacts\Livewire;

use Aura\Base\Resource;
use Livewire\Component;

final class ContactHealthPanel extends Component
{
    public bool $inModal = false;

    public Resource $model;

    public function render()
    {
        return view('acme-contacts::livewire.contact-health-panel');
    }
}
```

`resources/views/livewire/contact-health-panel.blade.php`:

```blade
<aside>
    <h2>Contact health</h2>
    <p>{{ $model->title() }}</p>
    @if ($inModal)
        <p>This record is open in a modal.</p>
    @endif
</aside>
```

The `ability` in the example is a host application Gate ability. Define it in
the host, or use an existing policy ability. For example:

```php
<?php

namespace App\Providers;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('view-contact-health', function (User $user, Resource $record): bool {
            return $user->can('view', $record);
        });
    }
}
```

If the host does not need an extra authorization rule, omit `ability`. A panel
with an `ability` is rendered only when the authenticated Aura user is allowed
to perform that ability on the record.

## Register panels from a host resource

A resource can own panels without a plugin registry. Implement
`DefinesRecordLayoutPanels` and return `RecordLayoutPanel` objects. Aura scopes
these declarations to that resource's class when it captures the boot baseline.

```php
<?php

namespace App\Aura\Resources;

use App\Livewire\ContactSummaryPanel;
use Aura\Base\RecordLayout\DefinesRecordLayoutPanels;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Aura\Base\Resource;

final class Contact extends Resource implements DefinesRecordLayoutPanels
{
    public static string $type = 'Contact';

    protected static ?string $slug = 'contact';

    public static function recordLayoutPanels(): array
    {
        return [
            new RecordLayoutPanel(
                key: 'summary',
                region: RecordLayoutRegion::LeftSummary,
                component: ContactSummaryPanel::class,
            ),
        ];
    }
}
```

The resource must be in Aura's registered resource list when the application
boots. Resource-owned panels use the same component contract and validation as
plugin panels. A minimal host component is:

```php
<?php

namespace App\Livewire;

use Aura\Base\Resource;
use Livewire\Component;

final class ContactSummaryPanel extends Component
{
    public bool $inModal = false;

    public Resource $model;

    public function render(): string
    {
        return '<section>'.e($this->model->title()).'</section>';
    }
}
```

## Define a panel

`RecordLayoutPanel` is a readonly value object. Its constructor accepts these
arguments:

| Argument | Type and default | Meaning |
| --- | --- | --- |
| `key` | `string` | Stable panel key within the registration source. It uses lowercase letters, digits, `.`, `_`, and `-`, and cannot start or end with a separator. |
| `region` | `RecordLayoutRegion` | One of the five regions above. |
| `component` | `string` | A concrete Livewire component class. Aura validates the class during boot. |
| `order` | `int`, `0` | Lower values render first within a region. |
| `resources` | `list<string>`, `['*']` | Resource class, slug, or type values that may use the panel. `'*'` matches every resource. |
| `ability` | `?string`, `null` | Gate or policy ability checked against the record before rendering. |
| `visible` | `bool`, `true` | Static switch. `false` always hides the panel. |
| `preferenceKey` | `?string`, `null` | Registered boolean User or Team preference. The panel is shown only when its resolved value is exactly `true`. |
| `eagerLoad` | `list<string>`, `[]` | Relationship names or dot paths to load before the panel components render. |

`resources` can contain at most 32 entries. A resource matches when a value is
its class name, `getSlug()` result, or `getType()` result. `eagerLoad` can contain
at most 12 relationship paths per panel. Aura validates the names, skips a panel
when its first relationship method does not exist on the record, deduplicates
the remaining paths, and calls `loadMissing()` once before rendering the panels.

The registry accepts at most 100 panels. The `source` passed to
`Aura::registerRecordLayoutPanels()` must be a lowercase Composer package name,
such as `acme/contacts`. A panel identity is the pair of source and key. An
identical duplicate is ignored before boot finalization. A duplicate with any
different value throws an exception. A batch does not become active when one of
its panel definitions fails validation.

## Visibility and component validation

Aura evaluates these conditions when it resolves a record layout:

1. `visible` must be `true`.
2. If `ability` is set, the authenticated Aura `User` must pass the Gate check
   for that ability and record. Guests and authorization exceptions fail closed.
3. If `preferenceKey` is set, the key must have been registered as a Boolean
   preference supporting the User or Team scope. Aura resolves each repeated key
   once for the record and keeps the panel only when the returned value is the
   boolean `true`. An unset stored value follows the preference definition's
   normal default rules. See [Preferences](/docs/preferences).
4. Every declared relationship path must pass the panel's syntax validation. A
   panel with a missing first relationship method is skipped.

At boot, Aura validates each component. It must be a concrete, canonical
Livewire component with no required constructor arguments. Its `model` input and
`inModal` input must each be accepted as a writable public property or as a
`mount()` parameter. `model` accepts an Aura `Resource` type, `object`, `mixed`,
or an untyped input. `inModal` accepts `bool`, `mixed`, or an untyped input.
Required `mount()` parameters other than `model` and `inModal` are rejected.

The initial `ability` check does not authorize later Livewire requests. Any
panel action that changes state must authorize that action again in the method
that handles the request.

## Record page resolution

The default resource view resolves the layout from the current record in
`View::render()`. It passes the canonical record and modal flag to each dynamic
panel component. `ViewModal` embeds the same resource view with `inModal` set to
`true`, so the same panel declarations work in a page and a modal.

Aura registers each panel component under an internal, source-specific Livewire
transport name during boot. The registry checks those names for collisions and
rejects a claim that points to a different component. Registrations captured in
the Aura boot baseline are restored by `Aura::flushState()` between queue work
and supported long-running worker boundaries.

Focused package coverage for this contract is in
`tests/Feature/Resource/RecordLayoutTest.php`. It covers default rendering,
region ordering, resource declarations, visibility, authorization, preferences,
component validation, registration collisions, eager-load and preference
bounds, modal context, and custom-table resources.
