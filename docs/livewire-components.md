# Livewire Components Reference

Aura CMS is built on Laravel Livewire, providing dynamic, reactive components without writing JavaScript. This comprehensive guide covers all Livewire components, their customization, and how to build your own.

## Table of Contents

- [Overview](#overview)
- [Component Architecture](#component-architecture)
- [Core Components](#core-components)
- [Resource Components](#resource-components)
- [Table Component](#table-component)
- [Form Components](#form-components)
- [Media Components](#media-components)
- [UI Components](#ui-components)
- [Component Communication](#component-communication)
- [Creating Custom Components](#creating-custom-components)
- [Real-time Features](#real-time-features)
- [Performance Optimization](#performance-optimization)
- [Testing Components](#testing-components)
- [Best Practices](#best-practices)

## Overview

Aura CMS leverages Livewire to create a seamless, SPA-like experience while maintaining the simplicity of server-side rendering. Components are reactive, performant, and deeply integrated with the Resource system.

### Key Features
- **Zero JavaScript Required**: Build interactive UIs with PHP
- **Reactive Properties**: Automatic DOM updates on property changes
- **Event System**: Component-to-component communication
- **File Uploads**: Built-in support for direct uploads
- **Validation**: Real-time validation with Laravel rules
- **Authorization**: Integrated policy checks
- **Testing**: First-class testing support

> 📹 **Video Placeholder**: [Overview of Aura CMS Livewire components showing reactivity and interactions]

## Component Architecture

### Base Component Structure

```php
namespace Aura\Base\Livewire;

use Livewire\Component;

class ExampleComponent extends Component
{
    // Public properties are reactive
    public $search = '';
    public $filters = [];
    
    // Protected properties for internal state
    protected $queryString = ['search'];
    
    // Lifecycle hooks
    public function mount($param = null)
    {
        // Initialize component
    }
    
    public function render()
    {
        return view('aura::livewire.example-component');
    }
}
```

### Component Traits

Aura uses traits to share common functionality:

```php
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\WithLivewireHelpers;

class ResourceForm extends Component
{
    use InteractsWithFields;
    use MediaFields;
    use WithLivewireHelpers;
}
```

### Component Registration

Components are auto-discovered by Livewire. Custom namespace registration:

```php
// In service provider
Livewire::component('custom-component', CustomComponent::class);
```

## Core Components

### Dashboard Component

The main dashboard entry point:

```php
// src/Livewire/Dashboard.php
class Dashboard extends Component
{
    public function render()
    {
        $widgets = $this->getWidgets();
        
        return view('aura::livewire.dashboard', [
            'widgets' => $widgets
        ])->layout('aura::components.layout.app');
    }
}
```

Usage in views:
```blade
<livewire:dashboard />
```

### Global Search

Provides instant search across all resources:

```php
// src/Livewire/GlobalSearch.php
class GlobalSearch extends Component
{
    public $search = '';
    
    public function getSearchResultsProperty()
    {
        if (!$this->search) {
            return [];
        }
        
        $resources = Aura::getResources();
        $results = collect();
        
        foreach ($resources as $resource) {
            // Search logic
        }
        
        return $results->groupBy('type');
    }
}
```

Features:
- Real-time search as you type
- Groups results by resource type
- Searches meta fields
- Respects permissions
- Keyboard navigation support

### Navigation Component

Dynamic navigation based on user permissions:

```php
class Navigation extends Component
{
    public $navigation;
    
    public function mount()
    {
        $this->navigation = Aura::navigation()
            ->filter(fn($item) => Gate::allows($item->gate));
    }
}
```

### Notifications

Real-time notification management:

```php
class Notifications extends Component
{
    public $tab = 'unread';
    
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->notify('All notifications marked as read');
    }
    
    public function render()
    {
        return view('aura::livewire.notifications', [
            'unread' => auth()->user()->unreadNotifications,
            'read' => auth()->user()->readNotifications
        ]);
    }
}
```

## Resource Components

### Create Component

Handles resource creation with field validation:

```php
// src/Livewire/Resource/Create.php
class Create extends Component
{
    use InteractsWithFields;
    
    public $form;
    public $model;
    
    public function mount($slug)
    {
        $this->model = Aura::findResourceBySlug($slug);
        $this->authorize('create', $this->model);
        
        $this->form = $this->model->toArray();
        $this->initializeFieldsWithDefaults();
    }
    
    public function save()
    {
        $this->validate();
        
        $model = $this->model->create($this->form['fields']);
        
        $this->notify('Successfully created.');
        
        return redirect()->route("aura.{$this->slug}.edit", $model->id);
    }
}
```

### Edit Component

Resource editing with live validation:

```php
class Edit extends Component
{
    public $form;
    public $post;
    
    public function mount($slug, $id)
    {
        $this->post = $this->model::find($id);
        $this->authorize('update', $this->post);
        
        $this->form = $this->post->toArray();
    }
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    public function save()
    {
        $this->validate();
        $this->post->update($this->form['fields']);
        $this->notify('Successfully updated.');
    }
}
```

### Index Component

Lists resources with table component:

```php
class Index extends Component
{
    public function render()
    {
        return view('aura::livewire.resource.index', [
            'resource' => $this->model
        ])->layout('aura::components.layout.app');
    }
}
```

### View Component

Display single resource:

```php
class View extends Component
{
    public $post;
    
    public function mount($slug, $id)
    {
        $this->post = $this->model::with($this->model->with())->find($id);
        $this->authorize('view', $this->post);
    }
}
```

### Modal Variants

Each resource component has a modal variant:

```php
class CreateModal extends Create
{
    public $inModal = true;
    
    public function save()
    {
        // Parent save logic
        parent::save();
        
        // Modal-specific behavior
        $this->dispatch('closeModal');
        $this->dispatch('refreshTable');
    }
}
```

## Table Component

The most complex component with multiple traits:

### Architecture

```php
// src/Livewire/Table/Table.php
class Table extends Component
{
    use BulkActions;
    use Filters;
    use Kanban;
    use PerPagePagination;
    use QueryFilters;
    use Search;
    use Settings;
    use Sorting;
    use SwitchView;
}
```

### Basic Usage

```blade
<livewire:table :resource="$resource" />
```

### Advanced Configuration

```php
// In your resource
public function table()
{
    return [
        'columns' => $this->indexFields(),
        'filters' => $this->filters(),
        'bulkActions' => $this->bulkActions(),
        'defaultSort' => '-created_at',
        'defaultPerPage' => 25,
        'views' => ['table', 'grid', 'kanban'],
    ];
}
```

### Table Traits

#### BulkActions Trait
```php
trait BulkActions
{
    public $selectedRows = [];
    public $selectAll = false;
    
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedRows = $this->rows->pluck('id')->toArray();
        } else {
            $this->selectedRows = [];
        }
    }
    
    public function bulkDelete()
    {
        $this->authorize('delete', $this->model);
        
        $this->model::whereIn('id', $this->selectedRows)->delete();
        
        $this->selectedRows = [];
        $this->notify('Successfully deleted.');
    }
}
```

#### Filters Trait
```php
trait Filters
{
    public $filters = [
        'status' => '',
        'category' => null,
        'date_from' => '',
        'date_to' => '',
    ];
    
    public function applyFilters($query)
    {
        return $query
            ->when($this->filters['status'], fn($q, $status) => 
                $q->where('status', $status)
            )
            ->when($this->filters['category'], fn($q, $category) => 
                $q->where('category_id', $category)
            );
    }
}
```

#### Search Trait
```php
trait Search
{
    public $search = '';
    
    protected $queryString = ['search'];
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function applySearch($query)
    {
        if (!$this->search) {
            return $query;
        }
        
        $searchableFields = $this->model->getSearchableFields();
        
        return $query->where(function ($q) use ($searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field->slug, 'like', "%{$this->search}%");
            }
        });
    }
}
```

## Form Components

### ResourceForm

Base form handling for all resources:

```php
// src/Livewire/Forms/ResourceForm.php
class ResourceForm extends Form
{
    public $fields = [];
    
    public function setPost($post)
    {
        $this->fields = $post->fields;
    }
    
    public function store()
    {
        $this->validate();
        
        return $this->model->create($this->all());
    }
    
    public function update($post)
    {
        $this->validate();
        
        $post->update($this->all());
    }
}
```

### Field Interactions

Dynamic field updates:

```php
// In component
public function updateField($slug, $value)
{
    $this->form['fields'][$slug] = $value;
    
    // Trigger dependent field updates
    $this->dispatch('fieldUpdated', [
        'slug' => $slug,
        'value' => $value
    ]);
}

// Listen for field updates
protected $listeners = ['fieldUpdated' => 'handleFieldUpdate'];

public function handleFieldUpdate($data)
{
    // React to field changes
}
```

## Media Components

### MediaManager

Central media selection interface:

```php
class MediaManager extends Component
{
    public $selected = [];
    public $multiple = false;
    public $field;
    
    public function selectMedia($mediaId)
    {
        if ($this->multiple) {
            $this->selected[] = $mediaId;
        } else {
            $this->selected = [$mediaId];
        }
        
        $this->dispatch('mediaSelected', [
            'field' => $this->field,
            'media' => $this->selected
        ]);
    }
}
```

### MediaUploader

Handle file uploads:

```php
class MediaUploader extends Component
{
    use WithFileUploads;
    
    public $files = [];
    
    public function updatedFiles()
    {
        foreach ($this->files as $file) {
            $attachment = Attachment::create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('media'),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            
            $this->dispatch('fileUploaded', $attachment);
        }
    }
}
```

### ImageUpload

Specialized image handling:

```php
class ImageUpload extends Component
{
    public $image;
    public $preview;
    
    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:2048'
        ]);
        
        $this->preview = $this->image->temporaryUrl();
    }
}
```

## UI Components

### Modal System

```php
class Modal extends Component
{
    public $show = false;
    public $component;
    public $params = [];
    
    protected $listeners = ['openModal'];
    
    public function openModal($component, $params = [])
    {
        $this->component = $component;
        $this->params = $params;
        $this->show = true;
    }
    
    public function closeModal()
    {
        $this->show = false;
        $this->component = null;
        $this->params = [];
    }
}
```

Usage:
```blade
<!-- Trigger modal -->
<button wire:click="$dispatch('openModal', { 
    component: 'resource.create-modal',
    params: { slug: 'products' }
})">
    Create Product
</button>

<!-- Modal container -->
<livewire:modal />
```

### SlideOver Panel

```php
class SlideOver extends Component
{
    public $open = false;
    public $component;
    
    protected $listeners = ['openSlideOver', 'closeSlideOver'];
    
    public function openSlideOver($component, $params = [])
    {
        $this->component = $component;
        $this->open = true;
    }
}
```

### BookmarkPage

User page bookmarking:

```php
class BookmarkPage extends Component
{
    public $url;
    public $bookmarked = false;
    
    public function mount()
    {
        $this->url = request()->url();
        $this->bookmarked = auth()->user()
            ->bookmarks()
            ->where('url', $this->url)
            ->exists();
    }
    
    public function toggle()
    {
        if ($this->bookmarked) {
            auth()->user()->bookmarks()
                ->where('url', $this->url)
                ->delete();
        } else {
            auth()->user()->bookmarks()->create([
                'url' => $this->url,
                'title' => $this->getPageTitle(),
            ]);
        }
        
        $this->bookmarked = !$this->bookmarked;
    }
}
```

## Component Communication

### Events & Listeners

```php
// Dispatching events
$this->dispatch('userCreated', ['userId' => $user->id]);

// Global event
$this->dispatch('notify', [
    'type' => 'success',
    'message' => 'User created successfully'
]);

// To specific component
$this->dispatch('refreshTable')->to('table');

// Self-dispatching
$this->dispatch('$refresh');
```

### Listening to Events

```php
class UserList extends Component
{
    protected $listeners = [
        'userCreated' => 'refreshList',
        'userDeleted' => '$refresh', // Magic refresh
        'echo:users,UserUpdated' => 'handleUserUpdate' // Laravel Echo
    ];
    
    public function refreshList($data)
    {
        // Handle the event
    }
}
```

### Parent-Child Communication

```php
// Parent component
class ParentForm extends Component
{
    public function handleChildUpdate($field, $value)
    {
        $this->form[$field] = $value;
    }
}

// Child component
class FieldComponent extends Component
{
    public function updateValue($value)
    {
        $this->dispatch('fieldUpdated', [
            'field' => $this->field,
            'value' => $value
        ])->up(); // Dispatch to parent
    }
}
```

## Creating Custom Components

### Basic Component

```bash
php artisan make:livewire CustomComponent
```

Creates:
- `app/Livewire/CustomComponent.php`
- `resources/views/livewire/custom-component.blade.php`

### Component with Traits

```php
namespace App\Livewire;

use Aura\Base\Traits\InteractsWithFields;
use Livewire\Component;

class CustomResourceForm extends Component
{
    use InteractsWithFields;
    
    public $resource;
    public $form = [];
    
    public function mount($resourceSlug)
    {
        $this->resource = Aura::findResourceBySlug($resourceSlug);
        $this->initializeFields();
    }
    
    public function save()
    {
        $this->validate($this->resource->validationRules());
        
        $model = $this->resource->create($this->form);
        
        $this->notify('Created successfully!');
        
        return redirect()->route('custom.success');
    }
    
    public function render()
    {
        return view('livewire.custom-resource-form');
    }
}
```

### Component View

```blade
<div>
    <form wire:submit="save">
        @foreach($resource->getFields() as $field)
            <x-dynamic-component 
                :component="$field->editComponent()"
                :field="$field"
                :form="$form"
                wire:model="form.fields.{{ $field->slug }}"
            />
        @endforeach
        
        <button type="submit">Save</button>
    </form>
</div>
```

### Integrating with Aura

Register in resource:

```php
class Product extends Resource
{
    public function indexComponent()
    {
        return CustomProductList::class;
    }
    
    public function createComponent()
    {
        return CustomProductForm::class;
    }
}
```

## Real-time Features

### Live Validation

```php
class LiveForm extends Component
{
    public $email = '';
    
    protected $rules = [
        'email' => 'required|email|unique:users'
    ];
    
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
}
```

View:
```blade
<input wire:model.live="email" type="email">
@error('email') <span class="error">{{ $message }}</span> @enderror
```

### Polling

```php
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'stats' => $this->getStats()
        ]);
    }
}
```

View:
```blade
<div wire:poll.5s>
    <!-- Refreshes every 5 seconds -->
    {{ $stats }}
</div>
```

### Lazy Loading

```php
class HeavyComponent extends Component
{
    public $data;
    
    public function mount()
    {
        // Heavy computation
        sleep(2);
        $this->data = $this->fetchData();
    }
}
```

View:
```blade
<div wire:init="loadData">
    <div wire:loading>
        Loading...
    </div>
    
    <div wire:loading.remove>
        {{ $data }}
    </div>
</div>
```

### Deferred Loading

```php
class DeferredComponent extends Component
{
    public $readyToLoad = false;
    
    public function loadData()
    {
        $this->readyToLoad = true;
    }
    
    public function getData()
    {
        if (!$this->readyToLoad) {
            return [];
        }
        
        return ExpensiveModel::all();
    }
}
```

## Performance Optimization

### 1. Computed Properties

Cache expensive operations:

```php
use Livewire\Attributes\Computed;

class ProductList extends Component
{
    #[Computed]
    public function products()
    {
        return Product::with(['category', 'tags'])
            ->filter($this->filters)
            ->paginate($this->perPage);
    }
    
    #[Computed(cache: true)]
    public function categories()
    {
        return Category::pluck('name', 'id');
    }
}
```

### 2. Lazy Loading

```blade
<div>
    @if($readyToLoad)
        @foreach($this->products as $product)
            <!-- Product list -->
        @endforeach
    @else
        <div wire:init="loadProducts">
            Loading products...
        </div>
    @endif
</div>
```

### 3. Debouncing

```blade
<!-- Debounce search input -->
<input wire:model.live.debounce.300ms="search" type="search">

<!-- Debounce method calls -->
<button wire:click.debounce.500ms="save">Save</button>
```

### 4. Pagination

```php
use Livewire\WithPagination;

class ProductTable extends Component
{
    use WithPagination;
    
    public function render()
    {
        return view('livewire.product-table', [
            'products' => Product::paginate(20)
        ]);
    }
}
```

### 5. Wire:key

```blade
@foreach($items as $item)
    <div wire:key="item-{{ $item->id }}">
        <!-- Helps Livewire track DOM elements -->
    </div>
@endforeach
```

## Testing Components

### Basic Test

```php
use Livewire\Livewire;

test('can create product', function () {
    $this->actingAs($user = User::factory()->create());
    
    Livewire::test(Create::class, ['slug' => 'products'])
        ->set('form.fields.name', 'Test Product')
        ->set('form.fields.price', 99.99)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect('/products/1/edit');
    
    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'price' => 99.99
    ]);
});
```

### Testing Validation

```php
test('validates required fields', function () {
    Livewire::test(Create::class, ['slug' => 'products'])
        ->call('save')
        ->assertHasErrors(['form.fields.name' => 'required'])
        ->assertHasErrors(['form.fields.price' => 'required']);
});
```

### Testing Events

```php
test('dispatches event on save', function () {
    Livewire::test(CreateModal::class, ['slug' => 'products'])
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertDispatched('productCreated')
        ->assertDispatched('closeModal');
});
```

### Testing Authorization

```php
test('unauthorized users cannot access', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(Settings::class)
        ->assertForbidden();
});
```

### Testing File Uploads

```php
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

test('can upload image', function () {
    Storage::fake('media');
    
    $file = TemporaryUploadedFile::fake()->image('product.jpg');
    
    Livewire::test(MediaUploader::class)
        ->set('files', [$file])
        ->assertDispatched('fileUploaded');
    
    Storage::disk('media')->assertExists('product.jpg');
});
```

## Best Practices

### 1. Component Organization

```php
// Keep components focused
class ProductList extends Component // Good
class ProductListEditDeleteSearchFilter extends Component // Bad

// Use traits for shared functionality
trait WithProductFilters
{
    public $categoryFilter;
    public $priceRange;
}
```

### 2. State Management

```php
// Use form objects for complex forms
class ProductForm extends Form
{
    public $name;
    public $price;
    public $category_id;
    
    protected $rules = [
        'name' => 'required|min:3',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id'
    ];
}
```

### 3. Security

```php
// Always authorize actions
public function delete($id)
{
    $product = Product::findOrFail($id);
    
    $this->authorize('delete', $product);
    
    $product->delete();
}

// Validate all input
public function updateStatus($status)
{
    $this->validate([
        'status' => ['required', Rule::in(['active', 'inactive'])]
    ]);
}
```

### 4. Performance

```php
// Use computed properties
#[Computed]
public function filteredProducts()
{
    return $this->products->filter(/* ... */);
}

// Avoid N+1 queries
public function mount()
{
    $this->products = Product::with(['category', 'tags'])->get();
}
```

### 5. User Experience

```php
// Provide loading states
public function save()
{
    $this->validate();
    
    // Show loading indicator
    $this->dispatch('saving');
    
    // Perform save
    $this->product->save();
    
    // Show success message
    $this->notify('Saved successfully!');
}
```

View:
```blade
<div wire:loading wire:target="save">
    Saving...
</div>
```

### 6. Error Handling

```php
public function process()
{
    try {
        $this->processData();
        $this->notify('Success!');
    } catch (\Exception $e) {
        $this->notify('Error: ' . $e->getMessage(), 'error');
        Log::error('Processing failed', [
            'error' => $e->getMessage(),
            'user' => auth()->id()
        ]);
    }
}
```

> 📹 **Video Placeholder**: [Building custom Livewire components in Aura CMS - from basic to advanced]

### Pro Tips

1. **Use Alpine.js**: Combine with Alpine for client-side interactions
2. **Optimize Queries**: Use eager loading and query scopes
3. **Cache Computed Properties**: For expensive calculations
4. **Debounce User Input**: Prevent excessive server requests
5. **Test Everything**: Livewire has excellent testing support
6. **Use Wire:key**: For dynamic lists to maintain state
7. **Batch Updates**: Group multiple property updates
8. **Profile Performance**: Use Laravel Debugbar
9. **Handle Errors Gracefully**: Always provide user feedback
10. **Document Components**: Add PHPDoc blocks for clarity

The Livewire component system in Aura CMS provides a powerful foundation for building reactive, modern admin interfaces while keeping the development experience simple and Laravel-centric.