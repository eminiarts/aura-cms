# Customizing views

Aura's resource pages are full-page Livewire components. Each page component asks the resource for a Blade view name. Resource view methods select the markup. Static resource component hooks select the Livewire class. These are separate extension points.

## Choose a customization point

Use the smallest extension point that fits the change:

- Override a resource view method when the page markup needs to change.
- Override a table view method or a table setting when only the listing changes.
- Register an injection point when you need to add content at a location that a core view already exposes.
- Replace a resource page component when you need new Livewire state, actions, lifecycle hooks, or a different layout.
- Publish package views when you need to edit a shared package view. Published files need maintenance when Aura is upgraded.

## View names and package paths

The paths below are relative to the Aura package root. A view name uses Laravel's dot notation.

| Purpose | Package file | Default view name or component hook |
| --- | --- | --- |
| Resource index | `resources/views/livewire/resource/index.blade.php` | `aura::livewire.resource.index`, returned by `indexView()` |
| Resource create page | `resources/views/livewire/resource/create.blade.php` | `aura::livewire.resource.create`, returned by `createView()` |
| Resource edit page | `resources/views/livewire/resource/edit.blade.php` | `aura::livewire.resource.edit`, returned by `editView()` |
| Resource detail page | `resources/views/livewire/resource/view.blade.php` | `aura::livewire.resource.view`, returned by `viewView()` |
| Edit page header | `resources/views/livewire/resource/edit-header.blade.php` | `aura::livewire.resource.edit-header`, returned by `editHeaderView()` |
| Detail page header | `resources/views/livewire/resource/view-header.blade.php` | `aura::livewire.resource.view-header`, returned by `viewHeaderView()` |
| Table Livewire wrapper | `resources/views/livewire/table.blade.php` | `aura::livewire.table`, returned by `tableComponentView()` |
| Standard table wrapper | `resources/views/components/table/index.blade.php` | `aura::components.table.index` |
| Standard list and row views | `resources/views/components/table/list-view.blade.php` and `row.blade.php` | `aura::components.table.list-view` and `aura::components.table.row` |
| Aura application layout | `resources/views/components/layout/app.blade.php` | `aura::layout.app` as a Blade component alias, `aura::components.layout.app` as a Livewire layout view |

The last row contains two different names for the same file. Blade's anonymous component resolver uses `aura::layout.app`. Livewire's `->layout()` method and `#[Layout]` attribute use the view name `aura::components.layout.app`.

## Overriding resource views

`AuraModelConfig` supplies these resource methods. Override only the method you need and return a view that exists in the host application or in the `aura::` namespace.

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public function indexView()
    {
        return 'aura.products.index';
    }

    public function createView()
    {
        return 'aura.products.create';
    }

    public function editView()
    {
        return 'aura.products.edit';
    }

    public function viewView()
    {
        return 'aura.products.view';
    }
}
```

The corresponding host files are `resources/views/aura/products/index.blade.php`, `create.blade.php`, `edit.blade.php`, and `view.blade.php`.

The index view receives `$resource` and `$slug`. Create and edit views receive `$model`, `$form`, and `$mode`. The edit view also reads the computed `$this->editFields` property. The create view uses `$this->createFields`. The detail view receives `$model`, `$form`, and `$recordLayout`.

Keep the field loop when you customize a form. It passes each field definition to the field's edit component and keeps the form bound to `form.fields.{slug}`:

```blade
{{-- resources/views/aura/products/edit.blade.php --}}
<div>
    @section('title', __('Edit :resource', ['resource' => __($model->singularName())]))

    @foreach ($this->editFields as $key => $field)
        @checkCondition($model, $field, $form)
            <x-dynamic-component
                :component="$field['field']->edit()"
                :field="$field"
                :form="$form"
                :mode="$mode"
                wire:key="resource-field-{{ $key }}" />
        @endcheckCondition
    @endforeach
</div>
```

A minimal index view can keep Aura's table and widgets while changing the surrounding markup:

```blade
{{-- resources/views/aura/products/index.blade.php --}}
<div>
    @section('title', __($resource->getPluralName()))

    {{ app('aura')::injectView('index_before') }}

    <h1 class="text-2xl font-bold">{{ __($resource->getPluralName()) }}</h1>

    {{ app('aura')::injectView('widgets_before') }}

    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif

    {{ app('aura')::injectView('widgets_after') }}

    <livewire:aura::table
        :model="$resource"
        :settings="$resource->indexTableSettings()" />
</div>
```

The edit and detail header views are independent of the main page views. Override `editHeaderView()` or `viewHeaderView()` when you need to replace them. The table header has a separate resource-specific convention described below.

## Customizing table views

`indexTableSettings()` configures the `aura::table` Livewire component. The resource defaults come from `InteractsWithTable`:

| Method | Default | Purpose |
| --- | --- | --- |
| `defaultPerPage()` | `10` | Initial page size |
| `defaultTableSort()` | `id` | Initial sort column |
| `defaultTableSortDirection()` | `desc` | Initial sort direction |
| `defaultTableView()` | `list` | Initial display mode |
| `tableView()` | `aura::components.table.list-view` | List view |
| `tableGridView()` | `false` | Grid view. Return a view name to enable it |
| `tableKanbanView()` | `false` | Legacy Kanban view. Return a view name to enable it |
| `rowView()` | `aura::components.table.row` | One row in list mode |
| `tableComponentView()` | `aura::livewire.table` | Outer table Livewire view |
| `showTableSettings()` | `true` | Column and view settings control |

Kanban can also be enabled through the resource's `kanbanSettings()`. When it is enabled without a custom view, the table uses `aura::components.table.kanban-view`.

For example:

```php
class Product extends Resource
{
    public function defaultTableView()
    {
        return 'grid';
    }

    public function tableGridView()
    {
        return 'aura.products.table.grid';
    }

    public function rowView()
    {
        return 'aura.products.table.row';
    }
}
```

The table merges `indexTableSettings()` into its defaults. Return only the keys you want to change:

```php
public function indexTableSettings()
{
    return [
        'per_page' => 25,
        'default_view' => 'grid',
        'search' => false,
        'views' => [
            'grid' => 'aura.products.table.grid',
            'row' => 'aura.products.table.row',
            'bulk_actions' => 'aura.products.table.bulk-actions',
        ],
    ];
}
```

Inside the table component, the `views` map uses these slots:

```php
'views' => [
    'table' => 'aura::components.table.index',
    'list' => $this->model->tableView(),
    'grid' => $this->model->tableGridView(),
    'kanban' => $this->model->tableKanbanView(),
    'filter' => 'aura::components.table.filter',
    'header' => 'aura::components.table.header',
    'row' => $this->model->rowView(),
    'bulk_actions' => 'aura::components.table.bulk-actions',
    'table_header' => 'aura::components.table.table-header',
    'table_footer' => 'aura::components.table.footer',
    'filter_tabs' => 'aura::components.table.filter-tabs',
],
```

The table resolves the Kanban entry through `kanbanSettings()`, so an enabled Kanban configuration can use the package default view even when `tableKanbanView()` returns `false`.

The list and grid views receive `$rows`, `$model`, and the table component as `$this`. A row view receives `$row` and the table component as `$this`. The outer `tableComponentView()` receives the table data, including `$rows`, `$rowIds`, `$parent`, and `$kanban`.

### Resource-specific table headers

The standard table header checks for an application view before it renders the package header. For a resource whose type is `Post`, create:

```text
resources/views/aura/post/header.blade.php
```

The lookup lowercases `getType()`. The custom header receives `$model` and the table component as `$this`. The `header_before` and `header_after` injection points still surround the header when the table settings enable them.

## Custom field components

Each field class declares an edit component in `$edit` and a display component in `$view`. Built-in `Text` uses `aura::fields.text` for editing and `aura::fields.view-value` for display.

Generate an application field with:

```bash
php artisan aura:field Price
```

The command creates `app/Aura/Fields/Price.php`, `resources/views/components/fields/price.blade.php`, and `resources/views/components/fields/price-view.blade.php`:

```php
namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Price extends Field
{
    public $edit = 'fields.price';

    public $view = 'fields.price-view';
}
```

The edit view should bind to the form field path and normally use the Aura field wrapper:

```blade
{{-- resources/views/components/fields/price.blade.php --}}
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        prefix="$"
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        id="resource-field-{{ optional($field)['slug'] }}" />
</x-aura::fields.wrapper>
```

The display view can delegate formatting to the resource:

```blade
{{-- resources/views/components/fields/price-view.blade.php --}}
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
```

For field-specific conversion, use the field hooks provided by `Field`, including `display($field, $value, $model)` and `value($value)`. There is no `displayValue()` hook.

## View injection points

Core views call `app('aura')::injectView('name')` at named points. Register a callback from a service provider with `Aura::registerInjectView()`:

```php
use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Aura::registerInjectView('table_before', fn (): string =>
        Blade::render('<div class="mb-4">Product data is updated hourly.</div>')
    );
}
```

The callback is called through Laravel's container. Return a rendered string or a view. Aura concatenates the registered callbacks for the same name.

These are the injection points currently rendered by the package views:

| Name | Location |
| --- | --- |
| `index_before` | Before the resource index content |
| `widgets_before`, `widgets_after` | Around index-page widgets |
| `table_before`, `table_after` | Around the table body |
| `table_before_{Type}`, `table_after_{Type}` | Around one resource type, such as `table_before_Post` |
| `header_before`, `header_after` | Around the table header |
| `breadcrumbs_before`, `breadcrumbs_after` | Around the breadcrumb slot |
| `post_edit_breadcrumbs_before`, `post_edit_breadcrumbs_after` | Around edit-page breadcrumbs |
| `post_edit_title_before`, `post_edit_title_after` | Around the edit-page title |
| `profile_before_header`, `profile_after_header` | Around the profile header |

The `{Type}` part is the value returned by the resource's `getType()` method. The table-specific points render only when the matching `table_before` or `table_after` setting is truthy. Both settings default to `true`.

Custom views can define their own injection names by calling `injectView()` at the desired location. A name has no effect until a view renders it.

## Publishing package views and assets

Aura's package service provider registers these publish tags:

```bash
php artisan vendor:publish --tag=aura-views
php artisan vendor:publish --tag=aura-config
php artisan vendor:publish --tag=aura-assets
```

`aura-views` copies the package views to `resources/views/vendor/aura`. Those files override the matching `aura::` views. `aura-config` publishes both `config/aura.php` and `config/aura-settings.php`. `aura-assets` copies the package assets to `public/vendor/aura`.

The `aura:publish` command updates the compiled Aura assets and its bundled libraries:

```bash
php artisan aura:publish
```

It has no `--force` option. Run it after a package update when the installed public assets need to match the package version. Publishing views is a separate operation.

## Replacing Livewire components

The top-level component settings live in `config/aura.php`:

```php
'components' => [
    'dashboard' => App\Livewire\CustomDashboard::class,
    'profile' => App\Livewire\CustomProfile::class,
    'settings' => App\Livewire\CustomSettings::class,
    'media-manager' => App\Livewire\CustomMediaManager::class,
],
```

The first three entries back the `/admin`, `/admin/profile`, and `/admin/settings` routes. `media-manager` is used by the `aura::media-manager` modal component.

Aura registers these Livewire aliases:

| Alias | Class or source |
| --- | --- |
| `aura::resource-index` | `Aura\\Base\\Livewire\\Resource\\Index` |
| `aura::resource-create` | `Aura\\Base\\Livewire\\Resource\\Create` |
| `aura::resource-edit` | `Aura\\Base\\Livewire\\Resource\\Edit` |
| `aura::resource-view` | `Aura\\Base\\Livewire\\Resource\\View` |
| `aura::table` | `Aura\\Base\\Livewire\\Table\\Table` |
| `aura::widgets` | `Aura\\Base\\Widgets\\Widgets` |
| `aura::modals` | `Aura\\Base\\Livewire\\Modals` |
| `aura::media-manager` | The class in `config('aura.components.media-manager')` |
| `aura::global-search` | `Aura\\Base\\Livewire\\GlobalSearch` |
| `aura::notifications` | `Aura\\Base\\Livewire\\Notifications` |

Resource routes use four static hooks. Each hook returns the page component class for the existing route:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;
use App\Livewire\EditProduct;

class Product extends Resource
{
    public static function editComponent(): string
    {
        return EditProduct::class;
    }
}
```

The other hooks are `indexComponent()`, `createComponent()`, and `viewComponent()`. Aura keeps the existing URI and `aura.{slug}.*` route name. No route file changes are required.

### Use `aura:customize`

The command copies the package page view and can generate a component subclass:

```bash
php artisan aura:customize Product edit --mode=full
```

The command accepts `index`, `create`, `edit`, and `view` page types. Its modes are:

- `full` copies the Blade view, creates `app/Livewire/EditProduct.php`, and adds `editComponent()` to the resource.
- `view` copies only the Blade view and adds `editView()` to the resource.
- `component` creates only the Livewire subclass and leaves the package view in place.

For a package resource such as User or Team, the command creates an application subclass under `app/Aura/Resources` before adding the hook.

## Layouts for full-page components

The resource `Index`, `Create`, `Edit`, and `View` components call `->layout('aura::components.layout.app')` in their `render()` methods. The dashboard, profile, settings, attachment index, resource editor, and other routed package components use the same full-page layout pattern. Changing `config('aura.views.layout')` does not change these component-level layouts.

Livewire 4 supports both a `#[Layout]` attribute and the `->layout()` view method for full-page components. Aura's generated component uses the method because it also selects the custom page view:

```php
namespace App\Livewire;

use Aura\Base\Livewire\Resource\Edit as BaseEdit;

class EditProduct extends BaseEdit
{
    public function render()
    {
        return view('aura.product.edit')
            ->layout('layouts.admin');
    }
}
```

Here `layouts.admin` is a normal application view at `resources/views/layouts/admin.blade.php`. If the class keeps the inherited view selection, Livewire 4 also supports a class attribute:

```php
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class EditProduct extends BaseEdit
{
}
```

Use `aura::components.layout.app` when referring to Aura's package layout from `->layout()` or `#[Layout]`. Use `aura::layout.app` only where Blade expects the anonymous component alias, such as:

```blade
<x-dynamic-component :component="config('aura.views.layout')">
    {{ $slot }}
</x-dynamic-component>
```

The intended package value for `config('aura.views.layout')` is `aura::layout.app`. `aura::layouts.app` does not resolve to a package component. `aura::components.layout.app` is the Livewire view name and is not the dynamic component alias.

The config-driven Blade wrappers use `aura.views.layout`. The routed Livewire pages use their own `render()` methods. This distinction also applies to custom dashboard, profile, and settings components selected through `config/aura.php`.

### Requirements for a replacement layout

The package layout at `resources/views/components/layout/app.blade.php` includes the assets and containers that Aura's admin UI expects. A replacement layout should provide the same integration points:

```blade
<head>
    @livewireStyles
    @auraStyles
</head>
<body>
    {{ $slot }}

    @stack('modals')
    <x-aura::notification />

    @if (config('aura.features.notifications'))
        <livewire:aura::notifications />
    @endif

    @livewire('aura::modals')

    @auraScripts
    @livewireScripts
</body>
```

Keep the global-search component when `aura.features.global_search` is enabled. Keep the navigation and authentication elements that your application needs. The layout's `$slot` receives the full-page resource view. The existing resource views use `@section('title', ...)`; preserve that section if the replacement layout reads the title with `yieldContent('title')`.

## Related guides

- [Creating fields](/docs/creating-fields) explains field classes and their edit and display components.
- [Table](/docs/table) documents columns, filters, sorting, and display modes.
- [Livewire components](/docs/livewire-components) lists the component extension points.
- [Hooks and events](/docs/hooks-events) covers non-view extension hooks.
- [Themes](/docs/themes) covers colors, dark mode, and published assets.
