# Livewire components

Aura CMS targets Livewire 4. Its admin pages, tables, forms, media picker, modals, and resource editor are Livewire components. You can embed these components in your own views or extend them to customize their behavior.

## Component registration

Aura resolves its built-in component names through Livewire 4's missing-component resolver. The service provider sets up this mapping in `bootLivewireComponents()`, without separate calls to `Livewire::component()`.

Use the short `aura::` names when embedding a component in a Blade view:

```blade
@livewire('aura::resource-index', ['slug' => 'post'])
@livewire('aura::table', [
    'model' => $resource,
    'settings' => $resource->indexTableSettings(),
])
```

The current names use hyphens. Names such as `aura::resource.index` and `aura::resource.create` are not in the current component map.

### Registered names

The provider registers these short names.

| Name | Class or configuration key |
| --- | --- |
| `aura::resource-index` | `Aura\Base\Livewire\Resource\Index` |
| `aura::resource-create` | `Aura\Base\Livewire\Resource\Create` |
| `aura::resource-create-modal` | `Aura\Base\Livewire\Resource\CreateModal` |
| `aura::resource-edit` | `Aura\Base\Livewire\Resource\Edit` |
| `aura::resource-edit-modal` | `Aura\Base\Livewire\Resource\EditModal` |
| `aura::resource-view` | `Aura\Base\Livewire\Resource\View` |
| `aura::resource-view-modal` | `Aura\Base\Livewire\Resource\ViewModal` |
| `aura::table` | `Aura\Base\Livewire\Table\Table` |
| `aura::attachment-index` | `Aura\Base\Livewire\Attachment\Index` |
| `aura::media-manager` | `config('aura.components.media-manager')` |
| `aura::media-uploader` | `Aura\Base\Livewire\MediaUploader` |
| `aura::attachment-details` | `Aura\Base\Livewire\AttachmentDetails` |
| `aura::navigation` | `Aura\Base\Livewire\Navigation` |
| `aura::global-search` | `Aura\Base\Livewire\GlobalSearch` |
| `aura::bookmark-page` | `Aura\Base\Livewire\BookmarkPage` |
| `aura::notifications` | `Aura\Base\Livewire\Notifications` |
| `aura::modals` | `Aura\Base\Livewire\Modals` |
| `aura::edit-resource-field` | `Aura\Base\Livewire\EditResourceField` |
| `edit-field` | `Aura\Base\Livewire\EditResourceField` |
| `aura::create-resource` | `Aura\Base\Livewire\CreateResource` |
| `aura::resource-editor` | `Aura\Base\Livewire\ResourceEditor` |
| `aura::choose-template` | `Aura\Base\Livewire\ChooseTemplate` |
| `aura::invite-user` | `Aura\Base\Livewire\InviteUser` |
| `aura::user-teams` | `Aura\Base\Livewire\UserTeams` |
| `aura::two-factor-authentication-form` | `Aura\Base\Livewire\TwoFactorAuthenticationForm` |
| `aura::user-two-factor-authentication-form` | `Aura\Base\Livewire\TwoFactorAuthenticationForm` |
| `aura::plugins-page` | `Aura\Base\Livewire\PluginsPage` |
| `aura::styleguide` | `Aura\Base\Livewire\Styleguide` |
| `aura::dashboard` | `config('aura.components.dashboard')` |
| `aura::profile` | `config('aura.components.profile')` |
| `aura::settings` | `config('aura.components.settings')` |
| `aura::widgets` | `Aura\Base\Widgets\Widgets` |
| `aura::widgets.value-widget` | `Aura\Base\Widgets\ValueWidget` |
| `aura::widgets.sparkline-area` | `Aura\Base\Widgets\SparklineArea` |
| `aura::widgets.sparkline-bar` | `Aura\Base\Widgets\SparklineBar` |
| `aura::widgets.donut` | `Aura\Base\Widgets\Donut` |
| `aura::widgets.pie` | `Aura\Base\Widgets\Pie` |
| `aura::widgets.bar` | `Aura\Base\Widgets\Bar` |

The provider also accepts dot-notation aliases. These include `aura.base.livewire.resource`, resource pages under `aura.base.livewire.resource.*`, `aura.base.livewire.table.table`, and both `aura.base.livewire.attachment` and `aura.base.livewire.attachment.index`. The service provider lists additional aliases for top-level components and widgets. These names resolve components. They are not route names.

Component names also differ from Blade view names. For example, `resources/views/livewire/resource/view.blade.php` is the template rendered by the record view component, not another component you can route to. The modal templates in that directory wrap content for the modal classes. Use the index, create, edit, or view page class for a full-page route.

## Routed admin pages

Aura places admin routes under `/admin` by default. You can change their domain, path, and middleware through `aura.domain`, `aura.path`, and `aura-settings.middleware.aura-admin`. Each resource chooses its page components through four static methods provided by the `AuraResourceComponents` concern.

| URL | Route name | Default component |
| --- | --- | --- |
| `/{slug}` | `aura.{slug}.index` | `Index` |
| `/{slug}/create` | `aura.{slug}.create` | `Create` |
| `/{slug}/{id}/edit` | `aura.{slug}.edit` | `Edit` |
| `/{slug}/{id}` | `aura.{slug}.view` | `View` |

The URLs above are relative to the configured admin path. To replace a page component, override the resource's `indexComponent()`, `createComponent()`, `editComponent()`, or `viewComponent()` method. The existing route name and generated resource URLs stay the same.

Attachments have a dedicated index component at `aura.attachment.index`. They do not receive the generic create, edit, and view routes.

### Resource page lifecycle

The index page finds the resource by its slug and checks the `viewAny` permission. It redirects to the dashboard if the resource is unknown or its `$indexViewEnabled` flag is false. The page renders the resource's `indexView()` within the Aura application layout. By default, this shows the resource's widgets and table.

The create page takes its resource slug from the mount argument or current route. It checks the `create` permission, then prepares the public `$form` array. Resources stored in a custom table start with a fields array. Resources stored in the posts table also receive their base post fields. Aura then applies field defaults, URL query values, and any modal parameters. Validation rules use the binding path `form.fields.<slug>`.

Saving a new record validates the form and keeps only declared input fields and explicitly supported setter fields. The server assigns ownership and team values. After creating the record, Aura sends a notification. It then closes the modal and refreshes its contents, or redirects to the record's edit page.

The edit page accepts a record id and optional resource slug. It loads the record, checks the `update` permission, and fills the form with the record's attributes and field values. Saving validates and sanitizes the submitted fields before updating the record. The page then sends a success notification, refreshes the form, and dispatches `refreshComponent`. In a modal, it also dispatches `closeModal` and `refreshTable`.

The view page also accepts a record id and optional resource slug. It loads the record, checks the `view` permission, and copies the attributes into the form. It resolves the record layout and renders the resource's `viewView()` template. Custom views can refresh the record after an action by dispatching `reload` or `refreshComponent`.

The page classes are full-page components when a route points at them. Do not point a resource route at `CreateModal`, `EditModal`, or `ViewModal`. Those classes exist for the modal container described below.

### Record view lifecycle and panels

When a record view has registered panels, Aura checks which ones it should display. The record layout resolver checks each panel's visibility, optional policy ability, optional boolean preference, and whether its declared relationships exist. It eager-loads the relationships needed by the remaining panels once, then mounts each panel with:

- `model`, the canonical resource record
- `inModal`, the current page or modal context

Panel components must accept both values as public properties or `mount()` parameters. State-changing methods must authorize again on each Livewire request. A resource can declare panels by implementing `DefinesRecordLayoutPanels`, or a plugin can register them through `Aura::registerRecordLayoutPanels()`. See [Record layouts](/docs/record-layouts) for the complete panel contract.

When no panels are visible, Aura renders the default record view. Otherwise, it adds the registered regions alongside the standard header and fields. Views shown in a modal use the same resolver, with `inModal` set to `true`.

## Form state, field bindings, and actions

Create and edit components expose a public `$form` array. Aura's field Blade views bind declared field values to `form.fields.<slug>`:

```blade
<input
    type="text"
    wire:model="form.fields.{{ $field['slug'] }}"
    aria-label="{{ $field['name'] }}"
>
```

Use `wire:model.live` when a field must update the component immediately, or plain `wire:model` to send the value with the next action. Custom field views receive the field definition, form state, and page mode as `$field`, `$form`, and `$mode`. Keep bindings under `form.fields` so Aura can validate and sanitize their values when saving.

The optional title input in the package templates still uses the outdated `post.title` binding. Resource page components expose form and model properties, but no public post property. In a custom view, bind the title to `form.fields.<slug>` instead.

Declare resource actions in the `actions()` method or `$actions` property. The built-in action view runs them through `singleAction($action)`. Before calling the resource method, Aura checks that the action is declared, evaluates its conditions, and authorizes the resource when required. A successful action sends a notification.

```php
// app/Aura/Resources/Post.php
public function actions(): array
{
    return [
        'archive' => ['label' => 'Archive'],
    ];
}

public function archive(): void
{
    $this->update(['status' => 'archived']);
}
```

```blade
<x-aura::button wire:click="singleAction('archive')">
    Archive
</x-aura::button>
```

Do not expose a generic method name as an action. The component only invokes actions declared by the resource.

### Events used by the built-in components

Aura uses Livewire 4 events for component coordination. These are the main public contracts relevant to custom pages.

| Event | Sender or listener | Payload and effect |
| --- | --- | --- |
| `openModal` | Any component to `aura::modals` | A component name, arguments, and optional modal attributes. See the modal section. |
| `closeModal` | Create, edit, and other modal children | No argument closes all active modals. An id closes one entry. |
| `refreshTable` | Create, edit, media, and table actions | Tells `Table` to refresh its rows. |
| `refreshComponent` | Edit, View, and resource editor | Triggers the component's refresh listener. |
| `reload` | Custom page or client event to Edit/View | Reloads the model and form, then dispatches `refreshComponent`. |
| `saveModel` | Modal edit view | Calls `Edit::save()`. The built-in edit modal button dispatches this event to `aura::resource-edit`. |
| `updateField` | Media picker/uploader to a form owner | Sends a `data` payload with `slug` and `value`; `MediaFields` writes it to `form.fields`. |
| `selectedMediaUpdated` | Form owner to media uploader | Sends a `slug` and `value` payload so the uploader updates its selection. |
| `openSlideOver` | Resource editor and navigation | Sends `target` and `parameters`; the matching slide-over calls its `activate()` method. |
| `notify` | Any component | Sends named `message` and `type` parameters to the notification UI. |

Dispatch a media field update with the same shape as the package components:

```php
$this->dispatch('updateField', data: [
    'slug' => 'hero_image',
    'value' => ['42'],
]);
```

For a slide-over, use `target`, not `component`:

```php
$this->dispatch('openSlideOver',
    target: 'edit-field',
    parameters: ['fieldSlug' => $slug, 'field' => $field, 'model' => $resourceSlug],
);
```

The matching `<x-aura::slide-over key="edit-field">` calls `activate($parameters)` on its Livewire component. The component must expose a public `open` property if it uses the package slide-over view.

Every Livewire component receives Aura's `notify()` macro after the package boots:

```php
$this->notify('Saved successfully.');
$this->notify('The record could not be saved.', 'error');
```

## Modal components

The Aura application layout mounts `aura::modals` once. Open a modal by dispatching `openModal`. The container accepts both the object payload used by Blade and the positional PHP form:

```blade
<button
    type="button"
    wire:click="$dispatch('openModal', {
        component: 'aura::resource-edit-modal',
        arguments: { resource: {{ $record->getKey() }}, type: '{{ $record->getType() }}' },
        modalAttributes: { slideOver: true }
    })"
>
    Edit
</button>
```

```php
$this->dispatch(
    'openModal',
    'aura::resource-edit-modal',
    ['resource' => $record->getKey(), 'type' => $record->getType()],
    ['slideOver' => true],
);
```

`Modals::openModal()` computes a stable id from the component name and serialized arguments. It merges these defaults into the modal attributes:

```php
[
    'persistent' => false,
    'modalClasses' => 'max-w-4xl',
    'slideOver' => false,
]
```

If the modal component defines a static `modalClasses()` method, the container uses that value for the centered dialog. `MediaManager` returns `max-w-7xl`, `CreateResource` returns `max-w-xl`, and `ViewModal` returns `max-w-4xl`. `slideOver: true` selects the slide-over dialog view. `closeModal()` with no id clears all active modals.

### Resource modal arguments

The three resource modal aliases have different mount contracts.

| Alias | Mount parameters | What the class does |
| --- | --- | --- |
| `aura::resource-create-modal` | `$slug = null, $params = []` | Extends `Create`, sets `$inModal = true`, stores `params`, and renders the resource create view without the full-page layout. |
| `aura::resource-edit-modal` | `$id = null, $resource = null, $type = null` | Loads the record from `resource` and `type`, then its wrapper view mounts `aura::resource-edit` with `inModal: true`. |
| `aura::resource-view-modal` | `$id = null, $resource = null, $type = null, $modalAttributes = []` | Loads the record, forces `persistent` to true, and its wrapper view mounts `aura::resource-view` with `inModal: true`. |

Table row actions pass `resource` as the record id and `type` as the resource type. A create modal can pass `params['for']` to receive the `resourceCreated` event after a successful create.

The modal classes are not alternate resource routes. The `edit-modal.blade.php` and `view-modal.blade.php` files are wrapper views that mount the normal edit or view component inside the modal. A full-page route always uses the resource's `indexComponent()`, `createComponent()`, `editComponent()`, or `viewComponent()` hook.

## Table component

The resource index view mounts the table with the resource instance and its settings:

```blade
<livewire:aura::table
    :model="$resource"
    :settings="$resource->indexTableSettings()"
/>
```

The table supports bulk actions, field and query filters, Kanban, pagination, search, row selection, settings, sorting, and view switching through its traits. Tables embedded in a relationship or media field can also receive the field definition and parent record through `field` and `parent`.

The default settings include:

| Key | Default |
| --- | --- |
| `per_page` | `10` |
| `columns` | `$model->getTableHeaders()` |
| `sort` | `['column' => 'id', 'direction' => 'desc']` |
| `search` | `true` |
| `filters` | `true` |
| `global_filters` | `true` |
| `selectable` | `true` |
| `create` | `true` |
| `actions` | `true` |
| `bulk_actions` | `true` |
| `create_in_modal` | `false` |
| `edit_in_modal` | `false` |
| `view_in_modal` | `false` |
| `default_view` | `$model->defaultTableView()` |
| `columns_user_key` | `columns.<resource type>` |

The table calls a resource's optional `indexQuery($query, $table)` hook before applying its field relation query, search, filters, and sorting. It dispatches `tableMounted` during mount and listens for `refreshTable`, `refreshTableSelected`, `selectedRows`, `selectFieldRows`, `selectRowsRange`, and `media-uploaded`.

## Media components

`aura::media-manager` is the attachment picker. Open it through the modal container with a model class, field slug, and selected attachment ids:

```php
$this->dispatch('openModal', 'aura::media-manager', [
    'model' => App\Aura\Resources\Post::class,
    'slug' => 'hero_image',
    'selected' => $selectedIds,
]);
```

When the user confirms a selection, the picker authorizes the attachments and sends their values to the form through `updateField`. The event includes the field slug and value in its data payload. The surrounding dialog handles closing, so the picker does not dispatch `closeModal` itself.

The media uploader uses Livewire file uploads. You can configure it through the public options `button`, `upload`, `table`, `disabled`, `field`, `for`, `model`, and `selected`.

The uploader listens for selection changes through `selectedMediaUpdated`. After creating attachments, it sends their ids through `media-uploaded`. An uploader embedded in a field also dispatches `updateField` to update the owning form.

`aura::attachment-index` is the dedicated Media Library page at `aura.attachment.index`. It is not a generic resource index route.

See [Media Manager](/docs/media-manager) for attachment storage and field configuration.

## Navigation, search, and account components

`aura::navigation` renders the sidebar generated by `Aura::navigation()`. Add application entries through the `Navigation` helper:

```php
use Aura\Base\Navigation\Navigation;

Navigation::add([
    [
        'name' => 'Reports',
        'slug' => 'reports',
        'route' => 'reports.index',
        'group' => 'Reports',
        'sort' => 100,
        'icon' => "<x-aura::icon icon='chart' />",
    ],
]);
```

The optional second argument to `Navigation::add()` is an authorization callback evaluated when the entry is registered. Resource visibility and policy checks still apply when Aura builds the navigation.

Enable `aura.features.global_search` to include global search in the application layout. Pressing / or Cmd-K dispatches the `search` event. Search includes fields marked searchable on resources the user can access, provided the resource's `getGlobalSearch()` method returns true. It returns up to 15 results, grouped by resource type.

`aura::bookmark-page` accepts a required `site` array with at least a `url` value:

```blade
@livewire('aura::bookmark-page', [
    'site' => ['title' => 'Products', 'url' => request()->url()],
])
```

`aura::notifications` is a slide-over component with the target key `notifications`. Open it with `openSlideOver` and an empty `parameters` object. `aura::profile` and `aura::settings` are the full-page components for the corresponding routes. `aura::invite-user`, `aura::user-teams`, and the two-factor aliases are reusable account components.

## Resource editor and widgets

The resource editor is available in local and testing environments when `aura.features.resource_editor` is enabled. Its route, `aura.resource.editor`, requires a super admin. The editor refuses vendor resources and resources whose field definitions contain closures. It opens the field editor in a slide-over through the `openSlideOver` event.

The resource generator modal, `aura::create-resource`, requires a super admin and cannot run in production. The editor uses `aura::choose-template` to let the user pick a template. The plugins page and styleguide are separate admin pages with their own routes.

The `aura::widgets` container receives a resource's widget definitions and model:

```blade
@livewire('aura::widgets', [
    'widgets' => $resource->widgets(),
    'model' => $resource,
])
```

The individual value, sparkline, donut, pie, and bar aliases are used by the widget container. See [Widgets](/docs/widgets) for widget definitions and query configuration.

## Customizing components

### Customize a resource page

Use `aura:customize` when a single resource needs a custom page component or copied Blade view:

```bash
php artisan aura:customize Product view edit --mode=full
```

The command accepts `index`, `create`, `edit`, and `view` page types. Its modes are:

- `full`, which creates `app/Livewire/{Type}{Resource}.php`, copies the package view to `resources/views/aura/{slug}/{type}.blade.php`, and writes the resource's static component hook.
- `view`, which copies the Blade view and writes `{type}View()` on the resource. No Livewire subclass is generated.
- `component`, which creates the subclass and writes the static component hook. The subclass keeps the package view.

For example, the generated component for a view page extends `Aura\Base\Livewire\Resource\View` and calls the parent mount method:

```php
namespace App\Livewire;

use Aura\Base\Livewire\Resource\View as BaseView;

class ViewProduct extends BaseView
{
    public function mount($id, $slug = null)
    {
        parent::mount($id, $slug ?? 'product');
    }

    public function render()
    {
        return view('aura.product.view')->layout('aura::components.layout.app');
    }
}
```

The resource hook is the only routing change needed:

```php
public static function viewComponent(): string
{
    return \App\Livewire\ViewProduct::class;
}
```

The existing `/admin/product/{id}` URI and `aura.product.view` route name then serve `ViewProduct`. Do not add a second route for the same resource page. For package resources such as User or Team, the command first creates an app-level resource subclass and updates the matching `aura.resources.*` configuration entry.

See [Customizing views](/docs/customizing-views) for view-only overrides and the available resource view methods.

### Override config-driven components

You can replace the dashboard, profile, settings, and media manager components through `config/aura.php`. Set the corresponding key to an application subclass. Aura reads these values when it builds the component map:

```php
// config/aura.php
'components' => [
    'dashboard' => App\Livewire\CustomDashboard::class,
    'profile' => App\Livewire\CustomProfile::class,
    'settings' => App\Livewire\CustomSettings::class,
    'media-manager' => App\Livewire\CustomMediaManager::class,
],
```

Extend the matching Aura component and keep its mount and event contract when replacing it:

```php
namespace App\Livewire;

use Aura\Base\Livewire\Dashboard;

class CustomDashboard extends Dashboard
{
    public function render()
    {
        return view('livewire.custom-dashboard')
            ->layout('aura::components.layout.app');
    }
}
```

The `media-manager` replacement must accept `model`, `slug`, `selected`, and optional `modalAttributes` inputs and dispatch the `updateField` payload expected by the owning field. Other component classes are not config-driven. Customize them by extending and registering an application component or by using the resource page hooks where those hooks apply.

## Testing components

Use Pest and the Livewire test helper. Mount page components by class or by a current `aura::` alias:

```php
use Aura\Base\Livewire\Resource\Create;
use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs(createSuperAdmin()));

test('creates a resource', function () {
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'Test post')
        ->call('save')
        ->assertHasNoErrors();
});
```

Test full-page and modal creation separately. The page redirects after saving, while the modal dispatches `closeModal`. To test an index table, pass the resource and its settings:

```php
use Aura\Base\Livewire\Table\Table;

livewire(Table::class, [
    'model' => $resource,
    'settings' => $resource->indexTableSettings(),
])->assertSuccessful();
```

Focused package coverage for this page lives in `tests/Feature/Resource/ResourceComponentsTest.php`, `tests/Feature/Resource/RecordLayoutTest.php`, `tests/Feature/Livewire/ModalsTest.php`, `tests/Feature/Commands/CustomizeCommandTest.php`, and the resource page tests.

## Related documentation

- [Customizing views](/docs/customizing-views)
- [Record layouts](/docs/record-layouts)
- [Table](/docs/table)
- [Media Manager](/docs/media-manager)
- [Resource editor](/docs/resource-editor)
- [Widgets](/docs/widgets)
- [Testing](/docs/testing)
