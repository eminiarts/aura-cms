# Livewire components

Aura CMS targets Livewire 4. The package uses Livewire components for the admin dashboard, resource pages, tables, forms, media picker, modals, and resource editor. This page documents the names and contracts that are present in the current package source.

## Component registration

`AuraServiceProvider::bootLivewireComponents()` registers a Livewire 4 missing-component resolver. The resolver maps Aura's component names to PHP classes. The package does not register its built-in components with a separate `Livewire::component()` call in the provider.

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

The provider also registers explicit dot-notation aliases. They include `aura.base.livewire.resource`, the resource page aliases under `aura.base.livewire.resource.*`, `aura.base.livewire.table.table`, `aura.base.livewire.attachment` and `.attachment.index`, and dot-notation aliases for the top-level components and widgets listed in `AuraServiceProvider`. These aliases are resolver entries, not route names.

The short names and the dot aliases are different from the Blade view names. For example, `resources/views/livewire/resource/view.blade.php` is the view rendered by the `View` class. It is not a second route component. The modal views in that directory are wrappers used by the modal classes. The routed full-page classes are `Index`, `Create`, `Edit`, and `View`.

## Routed admin pages

Aura registers admin routes inside the configured `aura.domain`, `aura.path`, and `aura-settings.middleware.aura-admin` group. The default path is `/admin`. Each registered resource supplies the component for each route through four static methods from the `AuraResourceComponents` concern.

| URL | Route name | Default component |
| --- | --- | --- |
| `/{slug}` | `aura.{slug}.index` | `Index` |
| `/{slug}/create` | `aura.{slug}.create` | `Create` |
| `/{slug}/{id}/edit` | `aura.{slug}.edit` | `Edit` |
| `/{slug}/{id}` | `aura.{slug}.view` | `View` |

The URL rows above are relative to `config('aura.path')`. The route action is `$resource::indexComponent()`, `$resource::createComponent()`, `$resource::editComponent()`, or `$resource::viewComponent()`. Overriding one of these methods changes the class served by the existing route and keeps the route name and generated resource URLs.

The Attachment resource is special. It does not receive the generic create, edit, and view routes. The package registers `Attachment\Index` at `aura.attachment.index`.

### Resource page lifecycle

`Index` resolves the resource from the slug, redirects to `aura.dashboard` when the resource is unknown or its `$indexViewEnabled` flag is false, and authorizes `viewAny`. Its `render()` method returns the resource's `indexView()` with the Aura application layout. The default view renders resource widgets and `aura::table`.

`Create` resolves the slug from the mount argument or the current route, authorizes `create`, and initializes the public `$form` array. A custom-table resource starts with a `fields` array. A posts-table resource also receives its base post fields. Field defaults, URL query values, and modal `params` are then applied. `rules()` maps the resource's validation rules to `form.fields.<slug>`.

`Create::save()` validates the form, keeps only declared input fields and explicitly supported setter fields, and assigns ownership and team values on the server. It creates the resource, dispatches `notify`, and then either closes and refreshes a modal or redirects to `aura.{slug}.edit`.

`Edit` mounts with an id and optional slug, resolves the resource record, authorizes `update`, copies the record attributes into `$form`, and hydrates field values. Its `save()` method validates, sanitizes the submitted field map, updates the resource, dispatches a success notification, refreshes the form, and dispatches `refreshComponent`. When `$inModal` is true it also dispatches `closeModal` and `refreshTable`.

`View` mounts with an id and optional slug, resolves the record, authorizes `view`, and copies the record attributes into `$form`. Its `render()` method returns the resource's `viewView()` and resolves the record layout before rendering. The component listens for `reload` and `refreshComponent` so a custom view can refresh the record after an action.

The page classes are full-page components when a route points at them. Do not point a resource route at `CreateModal`, `EditModal`, or `ViewModal`. Those classes exist for the modal container described below.

### Record view lifecycle and panels

When a `View` render has a registered record-layout panel, it resolves `RecordLayoutResolver` with the current resource. The resolver filters panels by `visible`, the optional policy ability, the optional boolean preference, and the existence of declared relationships. It eager-loads the remaining relationships once, then the view mounts each panel with:

- `model`, the canonical resource record
- `inModal`, the current page or modal context

Panel components must accept both values as public properties or `mount()` parameters. State-changing methods must authorize again on each Livewire request. A resource can declare panels by implementing `DefinesRecordLayoutPanels`, or a plugin can register them through `Aura::registerRecordLayoutPanels()`. See [Record layouts](/docs/record-layouts) for the complete panel contract.

With no visible panels, `View` renders the default record view. With panels, Aura keeps the standard header and fields and adds the registered regions. The same resolver runs for a view shown in a modal, with `inModal` set to `true`.

## Form state, field bindings, and actions

Create and edit components expose a public `$form` array. Aura's field Blade views bind declared field values to `form.fields.<slug>`:

```blade
<input
    type="text"
    wire:model="form.fields.{{ $field['slug'] }}"
    aria-label="{{ $field['name'] }}"
>
```

Use `wire:model.live` when the field must update the component immediately. Use plain `wire:model` when the value can be sent with the next action. A custom field view receives `$field`, `$form`, and `$mode` from the resource page. Keep its binding under `form.fields` so `rules()` and the save sanitizer can process it.

The optional title input in the package resource templates still contains a `post.title` binding. `Create`, `Edit`, and `View` expose `$form` and `$model`, not a public `$post` property. When writing a custom view, use the `form.fields.<slug>` contract and do not copy that binding.

Resource actions are declared by the resource's `actions()` method or `$actions` property. The built-in action view calls `singleAction($action)`. `HasActions::singleAction()` checks that the action is declared, evaluates conditional logic, authorizes the resource when required, invokes the resource method, and reports a successful action with `notify`.

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

`Table` composes the `BulkActions`, `Filters`, `Kanban`, `PerPagePagination`, `QueryFilters`, `Search`, `Select`, `Settings`, `Sorting`, and `SwitchView` traits. A table for a relation or media field can also receive `field` and `parent`.

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

Its `select()` method authorizes the selected attachments and dispatches `updateField` with `data['slug']` and `data['value']`. The picker does not close itself by dispatching `closeModal`; the surrounding dialog handles closing.

`aura::media-uploader` uses Livewire file uploads. Its public options are `button`, `upload`, `table`, `disabled`, `field`, `for`, `model`, and `selected`. It listens for `selectedMediaUpdated`. After creating attachments it dispatches `media-uploaded` with their ids. An inline field uploader also dispatches `updateField` so the owning form can update `form.fields.<slug>`.

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

`aura::global-search` is included by the application layout when `aura.features.global_search` is enabled. The layout dispatches `search` for `/` and `Cmd-K`. The component searches allowed resources whose `getGlobalSearch()` returns true, includes searchable fields, limits the result set to 15, and groups the links by type.

`aura::bookmark-page` accepts a required `site` array with at least a `url` value:

```blade
@livewire('aura::bookmark-page', [
    'site' => ['title' => 'Products', 'url' => request()->url()],
])
```

`aura::notifications` is a slide-over component with the target key `notifications`. Open it with `openSlideOver` and an empty `parameters` object. `aura::profile` and `aura::settings` are the full-page components for the corresponding routes. `aura::invite-user`, `aura::user-teams`, and the two-factor aliases are reusable account components.

## Resource editor and widgets

`aura::resource-editor` is available at `aura.resource.editor` for local or testing environments when `aura.features.resource_editor` is enabled. The route requires a Super Admin. The component also refuses vendor resources and resources whose field definitions contain closures. The editor opens `aura::edit-resource-field` through the `openSlideOver` contract.

`aura::create-resource` is the local development modal for generating a resource. It requires a Super Admin and refuses production. `aura::choose-template` is the template-picker component used by the editor. `aura::plugins-page` and `aura::styleguide` are registered admin pages with their own routes.

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

The `dashboard`, `profile`, `settings`, and `media-manager` classes are read from `config/aura.php` when the provider builds the component map. Point a key to an application subclass:

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

A page `Create` component redirects after save. A `CreateModal` component dispatches `closeModal` instead. Test those cases separately. For an index table, pass the resource and its settings:

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
