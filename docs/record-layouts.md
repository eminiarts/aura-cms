# Record layouts

Aura record pages expose five stable regions:

- `header-actions`
- `left-summary`
- `main-content`
- `right-sidebar`
- `activity-timeline`

The built-in header, actions, title, and resource fields remain the default content. When no panel is
registered or visible, Aura renders the original record view. Custom `viewView()` implementations remain an
opt-out and may build their own layout.

## Plugin panels

Register panels from a non-deferred service provider before the application finishes booting. Definitions
are immutable and contain only scalar values, enums, and class strings.

```php
use Aura\Base\Facades\Aura;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Vendor\Package\Livewire\ContactHealthPanel;

public function boot(): void
{
    Aura::registerRecordLayoutPanels('vendor/package', [
        new RecordLayoutPanel(
            key: 'contact-health',
            region: RecordLayoutRegion::RightSidebar,
            component: ContactHealthPanel::class,
            order: 20,
            resources: ['contact'],
            ability: 'view-health',
            preferenceKey: 'contacts.show-health',
            eagerLoad: ['owner'],
        ),
    ]);
}
```

Panel keys are unique within their Composer package source. Ordering is deterministic by region, numeric
order, package source, then panel key. A duplicate declaration is idempotent; a conflicting duplicate is
rejected atomically. Invalid, missing, abstract, or incompatible Livewire component classes are rejected.

Panel components receive the canonical record and modal state. They must accept both inputs as public
properties or `mount()` parameters and must re-authorize every state-changing Livewire action:

```php
use Aura\Base\Resource;
use Livewire\Component;

class ContactHealthPanel extends Component
{
    public bool $inModal = false;

    public Resource $model;

    public function render(): string
    {
        return '<div>...</div>';
    }
}
```

`ability` is checked against the record before initial rendering. `visible: false` always hides a panel.
`preferenceKey` must identify a registered boolean CORE-23 preference; `true` shows the panel and every
missing, invalid, or false value fails closed. Repeated keys are resolved once per record render. Declared
relationships are deduplicated and loaded together before panels render; panel views must not query the
record again.

CORE-20 component slots remain one-for-one replacement points. Record layouts follow the same validated,
boot-time Livewire transport approach, but use this separate registry because record regions deliberately
allow multiple ordered contributors.

## Resource panels

A resource can own panels without changing Aura's base `Resource` class:

```php
use Aura\Base\RecordLayout\DefinesRecordLayoutPanels;

class Contact extends Resource implements DefinesRecordLayoutPanels
{
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

Resource-owned panels are automatically scoped to that resource. Both plugin and resource registrations are
captured in Aura's worker baseline so request and queue resets cannot leak or discard declarations.
