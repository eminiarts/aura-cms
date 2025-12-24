# Customizing Views

Aura CMS provides a flexible view system built on Laravel's Blade templating engine and Livewire components. You can customize every aspect of how your resources are displayed, from simple view overrides to complete custom layouts.

## Table of Contents

- [Overview](#overview)
- [View Architecture](#view-architecture)
- [Resource Views](#resource-views)
- [Table Views](#table-views)
- [Field Views](#field-views)
- [Customization Methods](#customization-methods)
- [View Injection System](#view-injection-system)
- [Blade Components](#blade-components)
- [Livewire Components](#livewire-components)
- [Layout Customization](#layout-customization)
- [Resource Templates](#resource-templates)
- [Advanced Techniques](#advanced-techniques)
- [Best Practices](#best-practices)

## Overview

Aura's view system provides:
- **Resource Views**: Customizable views for each resource operation
- **Table Views**: Multiple display modes (table, grid, kanban)
- **Field Views**: Custom display for individual fields
- **View Injection**: Add content without modifying core files
- **Component System**: Reusable Blade and Livewire components
- **Layout Control**: Full control over page structure

> 📹 **Video Placeholder**: [Overview of Aura's view customization system showing different customization methods and live examples]

## View Architecture

### Package View Structure

Aura CMS views are organized within the package at `vendor/eminiarts/aura-cms/resources/views/`:

```
resources/views/
├── livewire/                 # Livewire component views
│   ├── resource/
│   │   ├── index.blade.php       # Resource listing page
│   │   ├── create.blade.php      # Create form
│   │   ├── create-modal.blade.php
│   │   ├── edit.blade.php        # Edit form
│   │   ├── edit-modal.blade.php
│   │   ├── edit-header.blade.php # Edit page header
│   │   ├── view.blade.php        # Detail view
│   │   ├── view-modal.blade.php
│   │   ├── view-header.blade.php # Detail page header
│   │   └── actions.blade.php     # Resource actions
│   ├── table.blade.php           # Main table component
│   ├── dashboard.blade.php
│   └── ...
├── components/               # Blade components
│   ├── table/
│   │   ├── index.blade.php       # Table wrapper
│   │   ├── list-view.blade.php   # List/table view
│   │   ├── kanban-view.blade.php # Kanban board view
│   │   ├── row.blade.php         # Table row
│   │   ├── cell.blade.php        # Table cell
│   │   ├── header.blade.php      # Table header
│   │   ├── footer.blade.php      # Table footer
│   │   ├── filter.blade.php      # Filter component
│   │   └── bulk-actions.blade.php
│   └── ...
├── auth/                     # Authentication views
├── navigation/               # Navigation components
└── aura/                     # Resource-specific views
    ├── User/header.blade.php
    └── Attachment/header.blade.php
```

### Your Application's View Structure

When customizing views, create them in your application:

```
resources/views/
├── vendor/aura/              # Published Aura views (optional)
├── aura/                     # Your Aura customizations
│   ├── resources/            # Resource-specific views
│   │   └── products/
│   │       ├── index.blade.php
│   │       ├── create.blade.php
│   │       ├── edit.blade.php
│   │       └── view.blade.php
│   ├── fields/               # Field customizations
│   └── components/           # Override components
└── livewire/                 # Livewire component views
```

### View Resolution Order

1. Custom resource view (if defined)
2. Published vendor view (if exists)
3. Package default view

## Resource Views

### Available Resource Views

Each resource has multiple customizable views:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    // Index page (listing)
    public function indexView()
    {
        return 'aura.resources.products.index';
    }
    
    // Create form
    public function createView()
    {
        return 'aura.resources.products.create';
    }
    
    // Edit form
    public function editView()
    {
        return 'aura.resources.products.edit';
    }
    
    // Detail view
    public function viewView()
    {
        return 'aura.resources.products.view';
    }
    
    // Header views for edit and detail pages
    public function editHeaderView()
    {
        return 'aura.resources.products.edit-header';
    }
    
    public function viewHeaderView()
    {
        return 'aura.resources.products.view-header';
    }
    
    // Table row view for listings
    public function rowView()
    {
        return 'aura.resources.products.row';
    }
    
    // Table component view
    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }
}
```

### Custom Index View

```blade
{{-- resources/views/aura/resources/products/index.blade.php --}}
<div>
    @section('title', __($resource->getPluralName()))

    {{-- Injection point for content before index --}}
    {{ app('aura')::injectView('index_before') }}

    {{-- Breadcrumbs --}}
    <div class="flex justify-between items-start">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
            <x-aura::breadcrumbs.li :title="__($resource->getPluralName())" />
        </x-aura::breadcrumbs>
    </div>

    {{-- Custom header --}}
    <header class="mb-6">
        <h1 class="text-2xl font-bold">{{ __('Products Catalog') }}</h1>
        <p class="text-gray-600">{{ __('Manage your product inventory') }}</p>
    </header>
    
    {{-- Widgets injection point --}}
    {{ app('aura')::injectView('widgets_before') }}
    
    @if ($widgets = $resource->widgets())
        @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
    @endif
    
    {{ app('aura')::injectView('widgets_after') }}
    
    {{-- Table component with settings from resource --}}
    <livewire:aura::table :model="$resource" :settings="$resource->indexTableSettings()" />
    
    {{-- Custom footer --}}
    <footer class="mt-6">
        @include('aura.resources.products.partials.footer')
    </footer>
</div>
```

### Custom Create/Edit View

```blade
{{-- resources/views/aura/resources/products/edit.blade.php --}}
<div class="max-w-4xl mx-auto">
    {{-- Custom header --}}
    <div class="mb-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold">
            {{ $model->exists ? 'Edit Product' : 'Create Product' }}
        </h2>
        @if($model->exists)
            <p class="text-sm text-gray-500">
                Last updated: {{ $model->updated_at->diffForHumans() }}
            </p>
        @endif
    </div>
    
    {{-- Error display --}}
    @if($errors->any())
        <x-aura::alert type="error" class="mb-4">
            {{ __('Please correct the errors below') }}
        </x-aura::alert>
    @endif
    
    {{-- Form fields --}}
    <form wire:submit.prevent="save">
        {{-- Group fields in sections --}}
        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($fields->where('group', 'basic') as $field)
                        <x-aura::field :field="$field" :model="$model" />
                    @endforeach
                </div>
            </div>
            
            {{-- Pricing --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Pricing</h3>
                @foreach($fields->where('group', 'pricing') as $field)
                    <x-aura::field :field="$field" :model="$model" />
                @endforeach
            </div>
        </div>
        
        {{-- Actions --}}
        <div class="mt-6 flex justify-end space-x-3">
            <x-aura::button type="button" variant="secondary" wire:click="cancel">
                {{ __('Cancel') }}
            </x-aura::button>
            <x-aura::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Save Product') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </x-aura::button>
        </div>
    </form>
</div>
```

### Custom Detail View

```blade
{{-- resources/views/aura/resources/products/view.blade.php --}}
<div class="max-w-6xl mx-auto">
    {{-- Product header --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        @if($model->featured_image)
            <img 
                src="{{ $model->featured_image->thumbnail('lg') }}" 
                alt="{{ $model->name }}"
                class="w-full h-64 object-cover"
            >
        @endif
        
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold">{{ $model->name }}</h1>
                    <p class="text-gray-600 mt-2">{{ $model->category->name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-primary-600">
                        ${{ number_format($model->price, 2) }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Stock: {{ $model->stock }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tabs for additional information --}}
    <div class="mt-6" x-data="{ activeTab: 'details' }">
        {{-- Tab navigation --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button
                    @click="activeTab = 'details'"
                    :class="activeTab === 'details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'"
                    class="py-2 px-1 border-b-2 font-medium"
                >
                    Details
                </button>
                <button
                    @click="activeTab = 'specifications'"
                    :class="activeTab === 'specifications' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'"
                    class="py-2 px-1 border-b-2 font-medium"
                >
                    Specifications
                </button>
                <button
                    @click="activeTab = 'reviews'"
                    :class="activeTab === 'reviews' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'"
                    class="py-2 px-1 border-b-2 font-medium"
                >
                    Reviews
                </button>
            </nav>
        </div>
        
        {{-- Tab content --}}
        <div class="mt-6">
            <div x-show="activeTab === 'details'">
                @include('custom.products.tabs.details')
            </div>
            <div x-show="activeTab === 'specifications'" x-cloak>
                @include('custom.products.tabs.specifications')
            </div>
            <div x-show="activeTab === 'reviews'" x-cloak>
                @include('custom.products.tabs.reviews')
            </div>
        </div>
    </div>
</div>
```

> 📹 **Video Placeholder**: [Creating custom resource views with different layouts and components]

## Table Views

### Table Configuration Methods

Configure table behavior by overriding these methods in your resource:

```php
class Product extends Resource
{
    // Default number of items per page
    public function defaultPerPage()
    {
        return 25; // Default is 10
    }
    
    // Default sort column
    public function defaultTableSort()
    {
        return 'created_at'; // Default is 'id'
    }
    
    // Default sort direction
    public function defaultTableSortDirection()
    {
        return 'desc'; // 'asc' or 'desc'
    }
    
    // Default view mode: 'list', 'grid', or 'kanban'
    public function defaultTableView()
    {
        return 'grid'; // Default is 'list'
    }
    
    // Show/hide table settings button
    public function showTableSettings()
    {
        return true;
    }
}
```

### Custom Table Views

Override specific table view components:

```php
class Product extends Resource
{
    // Main list/table view
    public function tableView()
    {
        return 'aura.resources.products.table.list';
    }
    
    // Grid view (set to blade view path or false to disable)
    public function tableGridView()
    {
        return 'aura.resources.products.table.grid';
    }
    
    // Kanban view (set to blade view path or false to disable)
    public function tableKanbanView()
    {
        return 'aura.resources.products.table.kanban';
    }
    
    // Table row view
    public function rowView()
    {
        return 'aura.resources.products.table.row';
    }
    
    // Table component wrapper
    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }
}
```

### Index Table Settings

For more granular control, use `indexTableSettings()`:

```php
public function indexTableSettings()
{
    return [
        'per_page' => 25,
        'search' => true,
        'filters' => true,
        'selectable' => true,
        'create' => true,
        'actions' => true,
        'bulk_actions' => true,
        'header' => true,
        'edit_in_modal' => false,
        'create_in_modal' => false,
        'view_in_modal' => false,
        'views' => [
            'table' => 'aura::components.table.index',
            'list' => 'aura.resources.products.table.list',
            'grid' => 'aura.resources.products.table.grid',
            'kanban' => 'aura.resources.products.table.kanban',
            'filter' => 'aura::components.table.filter',
            'header' => 'aura::components.table.header',
            'row' => 'aura.resources.products.table.row',
            'bulk_actions' => 'aura::components.table.bulk-actions',
        ],
    ];
}
```

### Custom Grid View

```blade
{{-- resources/views/aura/resources/products/table/grid.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($rows as $row)
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
            {{-- Product image --}}
            <div class="aspect-w-1 aspect-h-1">
                @if($row->featured_image)
                    <img 
                        src="{{ $row->featured_image->thumbnail('md') }}" 
                        alt="{{ $row->name }}"
                        class="w-full h-full object-cover"
                    >
                @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <x-aura::icon name="photograph" class="w-12 h-12 text-gray-400" />
                    </div>
                @endif
            </div>
            
            {{-- Product details --}}
            <div class="p-4">
                <h3 class="font-semibold text-lg mb-1">
                    <a href="{{ route('aura.product.view', $row) }}" class="hover:text-primary-600">
                        {{ $row->name }}
                    </a>
                </h3>
                
                <p class="text-gray-600 text-sm mb-2">{{ $row->category->name }}</p>
                
                <div class="flex justify-between items-center">
                    <span class="text-xl font-bold text-primary-600">
                        ${{ number_format($row->price, 2) }}
                    </span>
                    
                    <span class="text-sm {{ $row->stock > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $row->stock > 0 ? 'In Stock' : 'Out of Stock' }}
                    </span>
                </div>
                
                {{-- Quick actions --}}
                <div class="mt-4 flex space-x-2">
                    <x-aura::button size="sm" variant="primary" class="flex-1">
                        {{ __('Edit') }}
                    </x-aura::button>
                    <x-aura::button size="sm" variant="secondary" class="flex-1">
                        {{ __('View') }}
                    </x-aura::button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            @include('aura.resources.products.table.empty')
        </div>
    @endforelse
</div>
```

### Custom Table Row

```blade
{{-- resources/views/aura/resources/products/table/row.blade.php --}}
<tr class="hover:bg-gray-50 transition-colors">
    {{-- Checkbox --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <input 
            type="checkbox" 
            wire:model="selectedRows"
            value="{{ $row->id }}"
            class="rounded border-gray-300"
        >
    </td>
    
    {{-- Product image and name --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-10 w-10">
                @if($row->featured_image)
                    <img 
                        src="{{ $row->featured_image->thumbnail('xs') }}" 
                        alt="{{ $row->name }}"
                        class="h-10 w-10 rounded-full object-cover"
                    >
                @else
                    <div class="h-10 w-10 rounded-full bg-gray-200"></div>
                @endif
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">
                    {{ $row->name }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $row->sku }}
                </div>
            </div>
        </div>
    </td>
    
    {{-- Price --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm text-gray-900">${{ number_format($row->price, 2) }}</div>
    </td>
    
    {{-- Stock --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
            {{ $row->stock > 10 ? 'bg-green-100 text-green-800' : 
               ($row->stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
            {{ $row->stock }} units
        </span>
    </td>
    
    {{-- Actions --}}
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
        <div class="flex items-center justify-end space-x-2">
            <a 
                href="{{ route('aura.product.edit', $row) }}" 
                class="text-indigo-600 hover:text-indigo-900"
            >
                {{ __('Edit') }}
            </a>
            <a 
                href="{{ route('aura.product.view', $row) }}" 
                class="text-gray-600 hover:text-gray-900"
            >
                {{ __('View') }}
            </a>
        </div>
    </td>
</tr>
```

## Field Views

### Custom Field Display

```php
// In your field class
class PriceField extends Field
{
    public $view = 'custom.fields.price-view';
    public $edit = 'custom.fields.price-edit';
    
    public function displayValue($value, $model)
    {
        return '$' . number_format($value, 2);
    }
}
```

### Field View Template

```blade
{{-- resources/views/aura/fields/price-view.blade.php --}}
<div class="price-display">
    <span class="currency">$</span>
    <span class="amount">{{ number_format($value, 2) }}</span>
    
    @if($model->compare_at_price)
        <span class="compare-price line-through text-gray-500">
            ${{ number_format($model->compare_at_price, 2) }}
        </span>
        <span class="discount text-green-600">
            {{ round((1 - $value / $model->compare_at_price) * 100) }}% off
        </span>
    @endif
</div>
```

### Field Edit Template

```blade
{{-- resources/views/aura/fields/price-edit.blade.php --}}
<div class="price-input" x-data="priceField(@entangle($field['slug']))">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['name'] }}
        @if($field['required'] ?? false)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <div class="relative">
        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
            $
        </span>
        <input 
            type="number"
            x-model="value"
            @input="updateValue"
            step="0.01"
            min="0"
            class="pl-8 w-full rounded-md border-gray-300 shadow-sm"
            placeholder="0.00"
        >
    </div>
    
    @if($field['instructions'] ?? false)
        <p class="mt-1 text-sm text-gray-500">{{ $field['instructions'] }}</p>
    @endif
    
    @error($field['slug'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
function priceField(value) {
    return {
        value: value || 0,
        updateValue() {
            this.$wire.set('{{ $field['slug'] }}', parseFloat(this.value) || 0);
        }
    }
}
</script>
```

## Customization Methods

### Method 1: Override in Resource

Override individual view methods in your resource class:

```php
class Product extends Resource
{
    // Override the index/listing view
    public function indexView()
    {
        return 'aura.resources.products.index';
    }
    
    // Override the create form view
    public function createView()
    {
        return 'aura.resources.products.create';
    }
    
    // Override the edit form view
    public function editView()
    {
        return 'aura.resources.products.edit';
    }
    
    // Override the detail/show view
    public function viewView()
    {
        return 'aura.resources.products.view';
    }
    
    // Override header views
    public function editHeaderView()
    {
        return 'aura.resources.products.edit-header';
    }
    
    public function viewHeaderView()
    {
        return 'aura.resources.products.view-header';
    }
}
```

### Method 2: Publish Views

```bash
# Publish all Aura views to resources/views/vendor/aura/
php artisan vendor:publish --tag=aura-views

# Force overwrite existing published views
php artisan vendor:publish --tag=aura-views --force

# Publish Aura assets (CSS, JS, images)
php artisan vendor:publish --tag=aura-assets

# Or use the convenience command to publish assets
php artisan aura:publish
```

Published views are placed in `resources/views/vendor/aura/` and will automatically override the package views.

### Method 3: View Composer

```php
// In AppServiceProvider
public function boot()
{
    View::composer('aura::livewire.resource.*', function ($view) {
        $view->with('customData', $this->getCustomData());
    });
}
```

### Method 4: Extend Components

```php
namespace App\View\Components;

use Aura\Base\View\Components\Field as BaseField;

class Field extends BaseField
{
    public function render()
    {
        // Custom logic
        return view('custom.components.field');
    }
}
```

## View Injection System

### Understanding Injection Points

View injection allows adding content at specific points without modifying core files. Aura uses `app('aura')::injectView()` to render content at injection points.

### Using Injection Points in Views

In Blade templates, injection points are rendered like this:

```blade
{{-- Example from the index view --}}
{{ app('aura')::injectView('index_before') }}

{{-- Widgets injection points --}}
{{ app('aura')::injectView('widgets_before') }}
@if ($widgets = $resource->widgets())
    @livewire('aura::widgets', ['widgets' => $widgets, 'model' => $resource])
@endif
{{ app('aura')::injectView('widgets_after') }}
```

### Available Injection Points

Common injection points used in Aura views:

```php
// Index/listing page
'index_before'
'index_after'
'widgets_before'
'widgets_after'

// Create/edit forms
'create_before'
'create_after'
'edit_before'
'edit_after'

// Detail view
'view_before'
'view_after'
```

### Creating Custom Injection Points

In your custom views, add injection points:

```blade
{{-- In your custom view --}}
<div class="custom-section">
    {{ app('aura')::injectView('custom.section.before') }}
    
    <div class="content">
        {{-- Your content --}}
    </div>
    
    {{ app('aura')::injectView('custom.section.after') }}
</div>
```

### Registering Content for Injection Points

Register content for injection points in a service provider:

```php
use Aura\Base\Facades\Aura;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register a view for an injection point
        app('aura')::registerInjectView('index_before', function () {
            return view('custom.products.alerts');
        });
        
        // Register with conditional logic
        app('aura')::registerInjectView('edit_before', function () {
            if (request()->route('slug') === 'product') {
                return view('custom.products.edit-notice');
            }
        });
    }
}
```

> 📹 **Video Placeholder**: [Using the view injection system to add custom content and functionality]

## Blade Components

### Using Aura Components

```blade
{{-- Buttons --}}
<x-aura::button variant="primary" size="lg">
    {{ __('Save Changes') }}
</x-aura::button>

{{-- Alerts --}}
<x-aura::alert type="success" dismissible>
    {{ __('Product saved successfully!') }}
</x-aura::alert>

{{-- Cards --}}
<x-aura::card>
    <x-slot name="header">
        <h3 class="text-lg font-semibold">Product Details</h3>
    </x-slot>
    
    <x-slot name="content">
        {{-- Card content --}}
    </x-slot>
    
    <x-slot name="footer">
        <x-aura::button>Save</x-aura::button>
    </x-slot>
</x-aura::card>

{{-- Form inputs --}}
<x-aura::input 
    label="Product Name"
    name="name"
    wire:model="name"
    required
/>

{{-- Modals --}}
<x-aura::modal wire:model="showModal">
    <x-slot name="title">Confirm Delete</x-slot>
    <x-slot name="content">
        Are you sure you want to delete this product?
    </x-slot>
    <x-slot name="footer">
        <x-aura::button wire:click="delete">Delete</x-aura::button>
        <x-aura::button variant="secondary" wire:click="$set('showModal', false)">
            Cancel
        </x-aura::button>
    </x-slot>
</x-aura::modal>
```

### Creating Custom Components

```php
// app/View/Components/ProductCard.php
namespace App\View\Components;

use Illuminate\View\Component;

class ProductCard extends Component
{
    public function __construct(
        public $product,
        public $showActions = true
    ) {}
    
    public function render()
    {
        return view('components.product-card');
    }
}
```

```blade
{{-- resources/views/components/product-card.blade.php --}}
<div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
    @if($product->featured_image)
        <img 
            src="{{ $product->featured_image->thumbnail('md') }}" 
            alt="{{ $product->name }}"
            class="w-full h-48 object-cover"
        >
    @endif
    
    <div class="p-4">
        <h3 class="font-semibold text-lg">{{ $product->name }}</h3>
        <p class="text-gray-600">${{ number_format($product->price, 2) }}</p>
        
        @if($showActions)
            <div class="mt-4 flex space-x-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
```

## Livewire Components

### Configuring Core Components

Aura's main Livewire components can be replaced via `config/aura.php`:

```php
// config/aura.php
return [
    'components' => [
        // Replace the dashboard component
        'dashboard' => App\Livewire\CustomDashboard::class,
        
        // Replace the profile component
        'profile' => App\Livewire\CustomProfile::class,
        
        // Replace the settings component
        'settings' => App\Livewire\CustomSettings::class,
    ],
];
```

### Registered Livewire Components

Aura registers these Livewire components that you can reference:

| Component | Tag | Description |
|-----------|-----|-------------|
| `aura::resource-index` | `<livewire:aura::resource-index />` | Resource listing page |
| `aura::resource-create` | `<livewire:aura::resource-create />` | Create form |
| `aura::resource-edit` | `<livewire:aura::resource-edit />` | Edit form |
| `aura::resource-view` | `<livewire:aura::resource-view />` | Detail view |
| `aura::table` | `<livewire:aura::table />` | Table component |
| `aura::navigation` | `<livewire:aura::navigation />` | Navigation menu |
| `aura::global-search` | `<livewire:aura::global-search />` | Global search |
| `aura::media-manager` | `<livewire:aura::media-manager />` | Media library |
| `aura::widgets` | `<livewire:aura::widgets />` | Dashboard widgets |
| `aura::modals` | `<livewire:aura::modals />` | Modal container |

### Extending Aura Components

```php
namespace App\Http\Livewire;

use Aura\Base\Livewire\Resource\Edit as BaseEdit;

class ProductEdit extends BaseEdit
{
    protected $listeners = [
        'refreshComponent' => '$refresh',
        'priceUpdated' => 'calculateTotals',
    ];
    
    public function mount($id = null)
    {
        parent::mount($id);
        
        // Custom initialization
        $this->calculateTotals();
    }
    
    public function calculateTotals()
    {
        $this->total = $this->model->price * (1 + $this->model->tax_rate);
    }
    
    public function save()
    {
        // Custom validation
        $this->validate([
            'model.price' => 'required|numeric|min:0',
            'model.cost' => 'required|numeric|min:0|lt:model.price',
        ]);
        
        // Custom logic before save
        $this->model->profit_margin = 
            ($this->model->price - $this->model->cost) / $this->model->price;
        
        parent::save();
        
        // Custom logic after save
        $this->emit('productSaved', $this->model->id);
    }
    
    public function render()
    {
        return view('livewire.product-edit', [
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::active()->get(),
        ]);
    }
}
```

### Custom Livewire View

```blade
{{-- resources/views/livewire/product-edit.blade.php --}}
<div>
    <form wire:submit.prevent="save">
        {{-- Real-time calculations --}}
        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-600">Profit Margin</p>
            <p class="text-2xl font-bold text-primary-600">
                {{ number_format($model->profit_margin * 100, 1) }}%
            </p>
        </div>
        
        {{-- Dynamic form sections --}}
        @foreach($this->formSections as $section)
            <div 
                wire:key="section-{{ $section['key'] }}"
                class="mb-6"
                x-data="{ open: @entangle('sections.' . $section['key'] . '.open') }"
            >
                <h3 
                    @click="open = !open"
                    class="cursor-pointer flex items-center justify-between"
                >
                    {{ $section['title'] }}
                    <x-aura::icon 
                        name="chevron-down" 
                        class="w-5 h-5 transition-transform"
                        x-bind:class="open ? 'rotate-180' : ''"
                    />
                </h3>
                
                <div x-show="open" x-collapse>
                    @include($section['view'])
                </div>
            </div>
        @endforeach
        
        {{-- Actions with loading states --}}
        <div class="flex justify-end space-x-3">
            <x-aura::button 
                type="button" 
                variant="secondary"
                wire:click="cancel"
                wire:loading.attr="disabled"
            >
                Cancel
            </x-aura::button>
            
            <x-aura::button 
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">Save Product</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-aura::button>
        </div>
    </form>
</div>
```

## Layout Customization

### Custom App Layout

```blade
{{-- resources/views/layouts/custom-app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme['darkmode-type'] ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Aura CMS') }} - @yield('title', 'Dashboard')</title>
    
    {{-- Custom fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Styles --}}
    @vite(['resources/css/app.css'])
    @livewireStyles
    
    {{-- Custom styles --}}
    @stack('styles')
    
    {{-- Theme colors --}}
    <x-aura::layout.colors />
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex">
        {{-- Custom sidebar --}}
        <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
            @include('layouts.partials.sidebar')
        </aside>
        
        {{-- Main content --}}
        <div class="flex-1 flex flex-col">
            {{-- Custom header --}}
            <header class="bg-white dark:bg-gray-800 shadow-sm">
                @include('layouts.partials.header')
            </header>
            
            {{-- Page content --}}
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
            
            {{-- Custom footer --}}
            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                @include('layouts.partials.footer')
            </footer>
        </div>
    </div>
    
    {{-- Modals --}}
    @livewire('aura::modals')
    
    {{-- Scripts --}}
    @livewireScripts
    @vite(['resources/js/app.js'])
    
    {{-- Custom scripts --}}
    @stack('scripts')
    
    {{-- Notifications --}}
    <x-aura::notifications />
</body>
</html>
```

### Using Custom Layout

Configure the layout in `config/aura.php`:

```php
// In config/aura.php
return [
    'views' => [
        // Main application layout
        'layout' => 'layouts.custom-app',
        
        // Login page layout
        'login-layout' => 'layouts.login',
        
        // Dashboard view
        'dashboard' => 'custom.dashboard',
        
        // Navigation component
        'navigation' => 'custom.navigation',
        
        // Application logo
        'logo' => 'custom.logo',
    ],
];
```

You can also customize layouts in Livewire components:

```php
// In specific Livewire components
class ProductIndex extends Component
{
    public function render()
    {
        return view('livewire.product-index')
            ->layout('layouts.custom-app');
    }
}
```

## Resource Templates

Aura CMS includes predefined field layout templates to help you quickly structure your resource forms. These templates are located in `src/Templates/`.

### Available Templates

| Template | Description |
|----------|-------------|
| `Plain` | Simple flat layout without grouping |
| `Tabs` | Fields organized in tab groups |
| `PanelWithTabs` | Panel container with tabbed content |
| `PanelWithSidebar` | Panel with a sidebar layout |
| `TabsWithPanels` | Tabs containing panel groups |

### Using Templates

When creating a resource with the Resource Editor or programmatically, you can choose a template:

```php
use Aura\Base\Templates\PanelWithTabs;

class Product extends Resource
{
    public static function getFields()
    {
        // Start with a template structure
        return (new PanelWithTabs)->getFields();
    }
}
```

### Template Structure Example

Here's what the `PanelWithTabs` template provides:

```php
// PanelWithTabs template structure
[
    [
        'name' => 'Panel 1',
        'type' => 'Aura\\Base\\Fields\\Panel',
        'slug' => 'panel-1',
    ],
    [
        'name' => 'Tab 1',
        'type' => 'Aura\\Base\\Fields\\Tab',
        'slug' => 'tab-1',
    ],
    [
        'name' => 'Text 1',
        'type' => 'Aura\\Base\\Fields\\Text',
        'on_index' => true,
        'slug' => 'text-1',
        'style' => ['width' => '100'],
    ],
    [
        'name' => 'Tab 2',
        'type' => 'Aura\\Base\\Fields\\Tab',
        'slug' => 'tab-2',
    ],
    // ... more fields
]
```

## Advanced Techniques

### Dynamic View Selection

```php
class Product extends Resource
{
    public function indexView()
    {
        // Different views based on user role
        if (auth()->user()->hasRole('vendor')) {
            return 'vendor.products.index';
        }
        
        if (auth()->user()->hasRole('customer')) {
            return 'shop.products.catalog';
        }
        
        return 'admin.products.index';
    }
    
    public function editView()
    {
        // Different views based on product type
        return match($this->model->type) {
            'digital' => 'products.edit.digital',
            'physical' => 'products.edit.physical',
            'subscription' => 'products.edit.subscription',
            default => 'products.edit.default',
        };
    }
}
```

### View Caching

```php
// Cache expensive view data
class ProductController
{
    public function index()
    {
        $viewData = Cache::remember('products.index', 3600, function () {
            return [
                'featuredProducts' => Product::featured()->get(),
                'categories' => Category::withCount('products')->get(),
                'brands' => Brand::popular()->get(),
            ];
        });
        
        return view('products.index', $viewData);
    }
}
```

### View Macros

```php
// In AppServiceProvider
use Illuminate\Support\Facades\Blade;

public function boot()
{
    // Custom Blade directive
    Blade::directive('price', function ($expression) {
        return "<?php echo '$' . number_format($expression, 2); ?>";
    });
    
    // Custom if directive
    Blade::if('admin', function () {
        return auth()->user()?->hasRole('admin');
    });
}
```

Usage:
```blade
{{-- Price formatting --}}
@price($product->price)

{{-- Conditional content --}}
@admin
    <x-aura::button wire:click="delete">Delete Product</x-aura::button>
@endadmin
```

### View Composers for Global Data

```php
// In ViewServiceProvider
public function boot()
{
    // Share data with all views
    View::composer('*', function ($view) {
        $view->with('currentUser', auth()->user());
        $view->with('notifications', auth()->user()?->unreadNotifications);
    });
    
    // Share with specific views
    View::composer(['products.*', 'categories.*'], function ($view) {
        $view->with('productCount', Product::count());
    });
}
```

## Best Practices

### 1. Organization

```
resources/views/
├── aura/                         # Aura customizations
│   ├── resources/                # Resource-specific views
│   │   └── products/
│   │       ├── index.blade.php
│   │       ├── create.blade.php
│   │       ├── edit.blade.php
│   │       ├── view.blade.php
│   │       └── table/
│   │           ├── row.blade.php
│   │           └── grid.blade.php
│   ├── fields/                   # Field view customizations
│   └── components/               # Override Aura components
├── vendor/aura/                  # Published Aura views (optional)
├── livewire/                     # Livewire components
├── components/                   # Blade components
├── layouts/                      # Layout files
└── partials/                     # Reusable partials
```

### 2. Naming Conventions

```php
// Resource views: aura.resources.{resource}.{action}
'aura.resources.products.index'
'aura.resources.products.create'
'aura.resources.products.edit'
'aura.resources.products.view'

// Table views: aura.resources.{resource}.table.{view}
'aura.resources.products.table.row'
'aura.resources.products.table.grid'

// Field views: aura.fields.{field-name}
'aura.fields.price-view'
'aura.fields.price-edit'

// Components: descriptive names
'components.product-card'
'components.price-display'

// Partials: nested under resource
'aura.resources.products.partials.sidebar'
'aura.resources.products.partials.header'
```

### 3. Performance

```blade
{{-- Use lazy loading for heavy components --}}
<div wire:init="loadProducts">
    <div wire:loading>
        <x-aura::spinner />
    </div>
    
    <div wire:loading.remove>
        @forelse($products as $product)
            {{-- Content --}}
        @empty
            {{-- Empty state --}}
        @endforelse
    </div>
</div>

{{-- Cache expensive operations --}}
@cache('products.featured', 3600)
    @foreach($featuredProducts as $product)
        <x-product-card :product="$product" />
    @endforeach
@endcache
```

### 4. Maintainability

```php
// Use view includes for repeated sections
@include('products.partials.filters', [
    'categories' => $categories,
    'brands' => $brands,
])

// Use components for reusable UI
<x-aura::data-table 
    :columns="$columns"
    :rows="$products"
    :sortable="true"
/>

// Use slots for flexibility
<x-layout.section>
    <x-slot name="header">
        <h2>Products</h2>
    </x-slot>
    
    {{ $content }}
</x-layout.section>
```

### 5. Accessibility

```blade
{{-- Proper labeling --}}
<label for="product-name" class="sr-only">Product Name</label>
<input 
    id="product-name"
    type="text" 
    wire:model="name"
    aria-describedby="name-error"
>

{{-- Error announcements --}}
<div role="alert" aria-live="polite">
    @error('name')
        <p id="name-error" class="text-red-600">{{ $message }}</p>
    @enderror
</div>

{{-- Loading states --}}
<button 
    wire:click="save"
    wire:loading.attr="disabled"
    aria-busy="true"
    wire:loading
>
    <span aria-live="polite">
        <span wire:loading.remove>Save</span>
        <span wire:loading>Saving...</span>
    </span>
</button>
```

> 📹 **Video Placeholder**: [Best practices for view customization including performance optimization and maintainability]

### Pro Tips

1. **Start Small**: Override only the views you need to customize
2. **Use Partials**: Break complex views into manageable partials
3. **Leverage Components**: Create reusable components for common UI patterns
4. **Follow Conventions**: Maintain Aura's naming conventions for consistency
5. **Test Views**: Write tests for complex view logic
6. **Document Changes**: Comment why you've overridden specific views
7. **Version Control**: Track view changes carefully in git
8. **Performance Monitor**: Use Laravel Debugbar to identify slow views

The view system provides complete flexibility while maintaining the structure and consistency that makes Aura CMS powerful and easy to use.
