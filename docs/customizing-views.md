# Customizing Views in Aura CMS

Aura CMS provides extensive flexibility for customizing how your resources are displayed through its view system. You can customize views for resource listing, creation, editing, and more.

## Table of Contents

- [Overview](#overview)
- [Default View Locations](#default-view-locations)
- [Resource Views](#resource-views)
  - [Index View](#index-view)
  - [Create View](#create-view)
  - [Edit View](#edit-view)
  - [View/Show View](#viewshow-view)
- [Table Component Views](#table-component-views)
  - [Table Component Structure](#table-component-structure)
  - [Customizing Table Views](#customizing-table-views)
- [Customization Methods](#customization-methods)
  - [Method 1: Override View Methods](#method-1-override-view-methods)
  - [Method 2: View Publishing](#method-2-view-publishing)
- [View Injections](#view-injections)
  - [Available Injection Points](#available-injection-points)
  - [Custom Injection Points](#custom-injection-points)
- [View Configuration](#view-configuration)

## Overview

Aura CMS uses Laravel's Blade templating system for its views. Each resource type has several associated views that can be customized:
- Index view (listing)
- Create form
- Edit form
- Detail view
- Table components
- Dashboard widgets

## Default View Locations

The default views are located in the following directories:

```bash
vendor/eminiarts/aura/resources/views/
├── livewire/
│   ├── resource/
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   ├── index.blade.php
│   │   ├── view.blade.php
│   │   ├── edit-header.blade.php
│   │   └── view-header.blade.php
│   └── table/
│       ├── table.blade.php
│       ├── row.blade.php
│       └── components/
├── components/
└── layouts/
```

## Resource Views

### Index View

The index view is responsible for displaying the resource listing. You can customize it by overriding the `indexView()` method in your resource class:

```php
public function indexView()
{
    return 'resources.posts.index'; // Your custom view
}
```

Default location: `aura::livewire.resource.index`

### Create View

The create view displays the resource creation form. Customize it by overriding the `createView()` method:

```php
public function createView()
{
    return 'resources.posts.create';
}
```

Default location: `aura::livewire.resource.create`

### Edit View

The edit view shows the resource editing form. Override the `editView()` method:

```php
public function editView()
{
    return 'resources.posts.edit';
}
```

Default location: `aura::livewire.resource.edit`

### View/Show View

The view/show view displays the resource details. Override the `viewView()` method:

```php
public function viewView()
{
    return 'resources.posts.view';
}
```

Default location: `aura::livewire.resource.view`

## Table Component Views

### Table Component Structure

The table component is highly customizable and consists of several parts:

```php
public function tableComponentView()
{
    return 'aura::livewire.table';
}

public function rowView()
{
    return 'aura::components.table.row';
}
```

### Customizing Table Views

You can customize table settings in your resource class:

```php
public function indexTableSettings()
{
    return [
        'default_view' => 'grid',
        'views' => [
            'table' => 'custom.table.index',
            'list' => 'custom.table.list',
            'grid' => 'custom.table.grid',
            'kanban' => 'custom.table.kanban',
            'filter' => 'custom.table.filter',
            'header' => 'custom.table.header',
            'row' => 'custom.table.row',
            'bulkActions' => 'custom.table.bulkActions',
            'table_header' => 'custom.table.table-header',
            'table_footer' => 'custom.table.footer',
        ]
    ];
}
```

## Customization Methods

### Method 1: Override View Methods

The most straightforward way to customize views is by overriding the view methods in your resource class:

```php
use Aura\Base\Resource;

class Post extends Resource
{
    public function indexView()
    {
        return 'custom.posts.index';
    }

    public function createView()
    {
        return 'custom.posts.create';
    }

    public function editView()
    {
        return 'custom.posts.edit';
    }

    public function viewView()
    {
        return 'custom.posts.view';
    }

    public function editHeaderView()
    {
        return 'custom.posts.edit-header';
    }

    public function viewHeaderView()
    {
        return 'custom.posts.view-header';
    }
}
```

### Method 2: View Publishing

You can publish Aura's views to your application for customization:

```bash
php artisan vendor:publish --tag=aura-views
```

This will copy the views to `resources/views/vendor/aura/` where you can modify them.

## View Injections

View injections allow you to add custom content at specific points in Aura's views without modifying the original view files. This is particularly useful for adding functionality or content that needs to be consistent across multiple resources.

### Available Injection Points

Aura provides several predefined injection points:

```php
// In your service provider's boot method
use Aura\Base\Facades\Aura;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Before breadcrumbs in edit view
        Aura::injectView('post_edit_breadcrumbs_before', 'custom.components.before-breadcrumbs');

        // After breadcrumbs in edit view
        Aura::injectView('post_edit_breadcrumbs_after', 'custom.components.after-breadcrumbs');

        // Before the edit form
        Aura::injectView('post_edit_form_before', 'custom.components.before-form');

        // After the edit form
        Aura::injectView('post_edit_form_after', 'custom.components.after-form');

        // In the header section
        Aura::injectView('post_edit_header', 'custom.components.header-content');

        // In the footer section
        Aura::injectView('post_edit_footer', 'custom.components.footer-content');
    }
}
```

Example of a custom component being injected:

```php
// resources/views/custom/components/before-breadcrumbs.blade.php
<div class="mb-4">
    <x-custom.alert type="info">
        {{ __('Important information about this resource') }}
    </x-custom.alert>
</div>
```

### Custom Injection Points

You can also create your own injection points in your custom views:

```php
{{-- In your custom view --}}
<div class="custom-view">
    {{ app('aura')::injectView('custom_injection_point') }}

    {{-- Your view content --}}
</div>
```

Then inject content into your custom point:

```php
Aura::injectView('custom_injection_point', 'custom.components.injected-content');
```

## View Configuration

Configure global view settings in your `config/aura.php`:

```php
return [
    'views' => [
        'layout' => 'aura::layouts.app',
        'navigation' => 'aura::navigation',
        'dashboard' => 'aura::dashboard',
    ],
    'components' => [
        'dashboard' => \Aura\Base\Livewire\Dashboard::class,
        'profile' => \Aura\Base\Livewire\Profile::class,
        'settings' => \Aura\Base\Livewire\Settings::class,
    ],
];
```
