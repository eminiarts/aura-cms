# Record layouts

Record layout panels let you add Livewire components to a record's detail page.
The same panels appear when you open the record in a modal. Aura continues to
render the resource header, actions, title, and fields alongside your panels.

When no panels are visible, Aura renders the default record view without a layout
wrapper. If you provide a custom view through `viewView()`, Aura passes the
resolved layout as `$recordLayout`. Your view must render the layout or its
panels itself.

Record layouts apply only to detail pages. To arrange fields in create and edit
forms, use panel and tab fields as described in [Creating fields](/docs/creating-fields).

The application layout, navigation, theme, and sidebar have separate settings.
Use `config('aura.views.layout')` for the application layout and
`config('aura.theme.*')` for theme options, along with the Settings and Navigation
APIs. See [Configuration](/docs/configuration) and [Themes](/docs/themes).

## Regions

Choose a region for each panel using the `RecordLayoutRegion` enum:

| Case | Value | Location in the default record view |
| --- | --- | --- |
| `HeaderActions` | `header-actions` | The view header, before Aura's resource actions and Edit button |
| `LeftSummary` | `left-summary` | The left three-column aside when panels are present |
| `MainContent` | `main-content` | After the default resource fields in the main column |
| `RightSidebar` | `right-sidebar` | The right three-column aside when panels are present |
| `ActivityTimeline` | `activity-timeline` | After main-content panels in the main column |

The record's fields stay in the main content region. The main area spans six
columns when both side regions have panels, or nine columns when only one side
has panels.

Within each region, panels appear from lowest to highest `order`. Ties are
resolved by the registration source string, then by the panel key.

## Register a plugin panel

Register panels during service provider boot. If your package uses Spatie's
`PackageServiceProvider`, put the registration in `packageBooted()`.

Do not register panels in `$this->app->booted()`. Aura finalizes the registry in
its own application booted callback and rejects registrations after that point.

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

Aura passes the record to your component as `model` and indicates whether it is
open in a modal with `inModal`. Accept each input through a public property or a
`mount()` parameter. You can also use a property for one and a parameter for the
other. This component uses public properties and a package view:

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

Create the panel's view at `resources/views/livewire/contact-health-panel.blade.php`:

```blade
<aside>
    <h2>Contact health</h2>
    <p>{{ $model->title() }}</p>
    @if ($inModal)
        <p>This record is open in a modal.</p>
    @endif
</aside>
```

The example restricts access through a Gate ability defined in the host
application. Define that ability as shown below, or use an existing policy
ability:

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

Aura displays a panel with an `ability` only when the authenticated user may
perform that ability on the record. Omit this option if the host application
does not need an extra authorization rule.

## Register panels from a host resource

You can define panels directly on a resource. Implement
`DefinesRecordLayoutPanels` and return your panel definitions from
`recordLayoutPanels()`. During boot, Aura registers these panels for that
resource's class.

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

The resource must be registered with Aura when the application boots. Its panels
accept the same inputs and pass the same validation as plugin panels. Here is a
minimal component:

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

Create each panel definition with `RecordLayoutPanel`. This readonly object
accepts the following constructor arguments:

| Argument | Type and default | Meaning |
| --- | --- | --- |
| `key` | `string` | Stable panel key within the registration source. It uses lowercase letters, digits, `.`, `_`, and `-`, and cannot start or end with a separator. |
| `region` | `RecordLayoutRegion` | One of the five regions above. |
| `component` | `string` | A concrete Livewire component class. Aura validates the class during boot. |
| `order` | `int`, `0` | Lower values render first within a region. |
| `resources` | `list<string>`, `['*']` | Resource class, slug, or type values that may use the panel. `'*'` matches every resource. |
| `ability` | `?string`, `null` | Gate or policy ability checked against the record before rendering. |
| `visible` | `bool`, `true` | Static switch. `false` always hides the panel. |
| `preferenceKey` | `?string`, `null` | Registered boolean user or team preference. The panel is shown only when its resolved value is exactly `true`. |
| `eagerLoad` | `list<string>`, `[]` | Relationship names or dot paths to load before the panel components render. |

You can limit a panel to at most 32 resource entries. Each entry can match a
resource's class name, slug returned by `getSlug()`, or type returned by
`getType()`.

Each panel can request up to 12 relationship paths through `eagerLoad`. Aura
validates the paths and skips a panel if the first relationship method in any
path is missing from the record. Before rendering, it combines the remaining
paths, removes duplicates, and loads them with a single call to `loadMissing()`.

The registry accepts at most 100 panels. When calling
`Aura::registerRecordLayoutPanels()`, use a lowercase Composer package name such
as `acme/contacts` as the registration source.

Aura identifies each panel by its source and key. Before boot finalization,
registering an identical definition again has no effect. Reusing that identity
with any different value throws an exception. If any definition in a batch fails
validation, Aura does not activate the batch.

## Visibility and component validation

Aura evaluates these conditions when it resolves a record layout:

1. `visible` must be `true`.
2. If an ability is set, the authenticated Aura user must pass its Gate check
   for the record. Aura hides the panel for guests or if authorization throws an
   exception.
3. If a preference key is set, it must refer to a registered boolean preference
   that supports user or team scope. The panel appears only when the preference
   resolves to the boolean `true`. Aura resolves a shared key once per record.
   When no value is stored, the preference's normal default rules apply. See
   [Preferences](/docs/preferences).
4. Every declared relationship path must pass the panel's syntax validation. A
   panel with a missing first relationship method is skipped.

At boot, Aura validates each component. It must be a concrete, canonical
Livewire component with no required constructor arguments.

The component must accept the record and modal flag as writable public properties
or as `mount()` parameters:

| Input | Accepted types |
| --- | --- |
| `model` | Aura `Resource`, `object`, `mixed`, or untyped |
| `inModal` | `bool`, `mixed`, or untyped |

Any other mount parameters must be optional.

The initial ability check does not authorize later Livewire requests. Any panel
action that changes state must check authorization again in the method that
handles the request.

## Record page resolution

The default record view resolves the layout for the current record during
`View::render()` and passes that record and the modal flag to each panel.
The modal embeds the same view with `inModal` set to `true`, so panels work in
both contexts without separate declarations.

During boot, Aura gives each panel component an internal Livewire name based on
its registration source. It rejects a name already assigned to a different
component. Aura also saves the boot registrations and restores them through
`Aura::flushState()` between queue jobs and at supported long-running worker
boundaries.

Focused package coverage for this contract is in
`tests/Feature/Resource/RecordLayoutTest.php`. It covers default rendering,
region ordering, resource declarations, visibility, authorization, preferences,
component validation, registration collisions, eager-load and preference
bounds, modal context, and custom-table resources.
