# Livewire Components

Aura CMS leverages Livewire 3 to create dynamic, reactive user interfaces without writing JavaScript. This guide covers all built-in Livewire components, how to use them, customize them, and create your own.

## Table of Contents

1. [Introduction](#introduction)
2. [Core Components](#core-components)
3. [Resource Components](#resource-components)
4. [Table Component](#table-component)
5. [Media Components](#media-components)
6. [Modal & Overlay System](#modal--overlay-system)
7. [Form Components](#form-components)
8. [Navigation & UI Components](#navigation--ui-components)
9. [Component Communication](#component-communication)
10. [Creating Custom Components](#creating-custom-components)
11. [Performance Optimization](#performance-optimization)
12. [Testing Components](#testing-components)
13. [Best Practices](#best-practices)

## Introduction

Aura CMS's Livewire components provide a complete set of UI building blocks that handle complex interactions server-side while maintaining a reactive, SPA-like experience.

### Key Features

- **Server-side Reactivity**: All logic runs on the server, keeping your frontend simple
- **Automatic DOM Diffing**: Only changed parts of the UI update
- **Built-in Validation**: Laravel validation rules work seamlessly
- **Real-time Updates**: Components can communicate via events
- **Team-aware**: All components respect team context when enabled
- **Permission Integration**: Authorization checks are built-in

### Component Architecture

```php
namespace App\Http\Livewire;

use Aura\Base\Traits\WithLivewireHelpers;
use Livewire\Component;

class MyComponent extends Component
{
    use WithLivewireHelpers; // Aura's helper methods
    
    public $property;
    
    public function mount($parameter)
    {
        // Initialization logic
    }
    
    public function render()
    {
        return view('livewire.my-component');
    }
}
```

## Core Components

### Dashboard Component

The main dashboard that users see after login.

```php
use Aura\Base\Livewire\Dashboard;

// In your view
@livewire('aura::dashboard')
```

**Features:**
- Widget display
- Quick actions
- Recent activity
- Customizable layout

**Customization:**
```php
// Extend the dashboard
class CustomDashboard extends Dashboard
{
    public function getWidgets()
    {
        return [
            new ValueWidget('Total Users', User::count()),
            new SparklineWidget('Sales', $this->getSalesData()),
        ];
    }
}
```

### Global Search

Provides instant search across all resources with keyboard shortcut (⇧⌘K).

```php
@livewire('aura::global-search')
```

**Configuration:**
```php
// In your Resource
public static function searchable(): array
{
    return ['title', 'content', 'author'];
}

public static function getGlobalSearch(): bool
{
    return true; // Include in global search
}
```

**Usage in Blade:**
```html
<div x-data="{ open: false }" 
     @keydown.window.cmd.shift.k="open = true"
     @keydown.window.escape="open = false">
    <div x-show="open">
        @livewire('aura::global-search')
    </div>
</div>
```

### Navigation Component

Dynamic navigation that responds to permissions and team context.

```php
@livewire('aura::navigation')
```

**Customizing Navigation:**
```php
// In AuraServiceProvider
use Aura\Base\Facades\Aura;

public function boot()
{
    Aura::navigation(function ($items) {
        $items->add('Custom Page', '/custom', 'icon-name')
              ->after('Dashboard');
        
        $items->group('Admin', function ($group) {
            $group->add('Settings', '/settings');
            $group->add('Users', '/users');
        })->requirePermission('admin.access');
    });
}
```


## Resource Components

### Resource Index

Lists resources in table, grid, or kanban view.

```php
@livewire('aura::resource.index', ['slug' => 'posts'])
```

**Features:**
- Multiple view modes (table, grid, kanban)
- Sorting and filtering
- Bulk actions
- Search
- Pagination

**Customization:**
```php
class PostIndex extends \Aura\Base\Livewire\Resource\Index
{
    public function query()
    {
        return parent::query()
            ->where('status', 'published')
            ->with('author');
    }
    
    public function bulkActions()
    {
        return [
            'publish' => 'Publish Selected',
            'archive' => 'Archive Selected',
        ];
    }
}
```

### Resource Create/Edit

Form components for creating and editing resources.

```php
// Create new resource
@livewire('aura::resource.create', ['slug' => 'posts'])

// Edit existing resource
@livewire('aura::resource.edit', ['slug' => 'posts', 'id' => $post->id])
```

**Field Handling:**
```php
class PostCreate extends \Aura\Base\Livewire\Resource\Create
{
    public function save()
    {
        // Custom save logic
        $this->validate();
        
        // Pre-save hook
        $this->form['published_at'] = now();
        
        parent::save();
        
        // Post-save hook
        event(new PostCreated($this->model));
    }
}
```

### Resource View

Display resource in read-only mode.

```php
@livewire('aura::resource.view', ['slug' => 'posts', 'id' => $post->id])
```

## Table Component

The most powerful component in Aura CMS, providing advanced data display and manipulation.

### Basic Usage

```php
use Aura\Base\Livewire\Table\Table;

@livewire('aura::table', [
    'model' => Post::class,
    'columns' => ['title', 'author', 'created_at'],
])
```

### Advanced Configuration

```php
class PostTable extends Table
{
    public function columns()
    {
        return [
            'title' => [
                'label' => 'Post Title',
                'sortable' => true,
                'searchable' => true,
            ],
            'author.name' => [
                'label' => 'Author',
                'sortable' => true,
            ],
            'status' => [
                'label' => 'Status',
                'component' => 'status-badge',
            ],
            'actions' => [
                'label' => '',
                'component' => 'table-actions',
            ],
        ];
    }
    
    public function filters()
    {
        return [
            'status' => [
                'type' => 'select',
                'options' => ['draft', 'published', 'archived'],
            ],
            'created_at' => [
                'type' => 'date-range',
            ],
        ];
    }
    
    public function bulkActions()
    {
        return [
            'delete' => [
                'label' => 'Delete Selected',
                'confirm' => 'Are you sure?',
                'action' => 'deleteSelected',
            ],
        ];
    }
}
```

### Table Traits

The table component uses modular traits for functionality:

```php
use BulkActions;    // Bulk operations on rows
use Filters;       // Advanced filtering
use Kanban;        // Kanban board view
use Search;        // Search functionality
use Select;        // Row selection
use Settings;      // User preferences
use Sorting;       // Column sorting
use SwitchView;    // Toggle views
```


## Media Components

### Media Manager

Full-featured media library interface.

```php
@livewire('aura::media-manager')
```

**Integration Example:**
```php
<div x-data="{ showMediaManager: false }">
    <button @click="showMediaManager = true">Select Image</button>
    
    <div x-show="showMediaManager">
        @livewire('aura::media-manager', [
            'mode' => 'picker',
            'accept' => 'image/*',
            'multiple' => false,
        ])
    </div>
</div>

@script
<script>
    $wire.on('media-selected', (media) => {
        // Handle selected media
        console.log('Selected:', media);
    });
</script>
@endscript
```

### Media Uploader

Drag-and-drop file upload component.

```php
@livewire('aura::media-uploader', [
    'field' => 'featured_image',
    'accept' => 'image/*',
    'maxSize' => 5, // MB
])
```

**Handling Uploads:**
```php
class ProductForm extends Component
{
    use WithFileUploads;
    
    public $images = [];
    
    public function updatedImages()
    {
        $this->validate([
            'images.*' => 'image|max:5120', // 5MB max
        ]);
        
        foreach ($this->images as $image) {
            $path = $image->store('products', 'public');
            
            Attachment::create([
                'path' => $path,
                'type' => 'product-image',
                'attachable_type' => Product::class,
                'attachable_id' => $this->product->id,
            ]);
        }
        
        $this->notify('Images uploaded successfully!');
    }
}
```

### Image Upload

Specialized component for single image uploads with preview.

```php
@livewire('aura::image-upload', [
    'model' => $user,
    'field' => 'avatar',
    'disk' => 'public',
])
```

## Modal & Overlay System

### Modal Component

Base modal system for dialogs and forms.

```php
// Trigger modal
<button wire:click="$dispatch('openModal', { component: 'user-form', arguments: { userId: {{ $user->id }} } })">
    Edit User
</button>

// Modal component
class UserForm extends Component implements ModalInterface
{
    public $user;
    
    public function mount($userId = null)
    {
        $this->user = $userId ? User::find($userId) : new User;
    }
    
    public function save()
    {
        $this->validate();
        $this->user->save();
        
        $this->closeModal();
        $this->dispatch('user-saved');
    }
}
```

### Slide-Over Panel

Side panel for forms and details.

```php
@livewire('aura::slide-over', [
    'component' => 'resource-details',
    'params' => ['id' => $resource->id],
])
```

**Creating Slide-Over Components:**
```php
class ResourceDetails extends Component
{
    public $resource;
    
    public function mount($id)
    {
        $this->resource = Resource::findOrFail($id);
    }
    
    public function render()
    {
        return view('livewire.resource-details')
            ->layout('aura::components.slide-over');
    }
}
```

## Form Components

### Resource Form

Base form handling for resources.

```php
use Aura\Base\Livewire\Forms\ResourceForm;

class PostForm extends ResourceForm
{
    public function rules()
    {
        return [
            'form.fields.title' => 'required|min:3',
            'form.fields.content' => 'required',
            'form.fields.published_at' => 'nullable|date',
        ];
    }
    
    public function save()
    {
        $this->validate();
        
        // Custom logic before save
        if ($this->form['fields']['status'] === 'published' && !$this->form['fields']['published_at']) {
            $this->form['fields']['published_at'] = now();
        }
        
        parent::save();
    }
}
```

### Field Components

Each field type has its own Livewire handling:

```php
// Text field with real-time validation
<div>
    <x-aura::input.text 
        wire:model.live="form.fields.title"
        wire:key="field-title"
        label="Title"
        :error="$errors->first('form.fields.title')"
    />
</div>

// Select field with dynamic options
<div>
    <x-aura::input.select
        wire:model="form.fields.category_id"
        wire:change="updateSubcategories"
        :options="$categories"
        label="Category"
    />
</div>
```

## Navigation & UI Components

### Bookmarks

Allow users to bookmark frequently accessed pages.

```php
@livewire('aura::bookmark-page', [
    'title' => 'Product List',
    'url' => request()->url(),
])
```

### Notifications

Real-time notification center.

```php
@livewire('aura::notifications')

// Send notification from any component
$this->notify('Task completed successfully!', 'success');
$this->notify('Error processing request', 'error');
```

**Custom Notifications:**
```php
use Aura\Base\Notifications\CustomNotification;

class OrderShipped extends CustomNotification
{
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }
    
    public function toArray($notifiable)
    {
        return [
            'title' => 'Order Shipped',
            'message' => "Order #{$this->order->id} has been shipped",
            'action' => route('orders.view', $this->order),
        ];
    }
}
```

### Settings

System-wide settings management.

```php
@livewire('aura::settings', [
    'group' => 'general', // general, email, api, etc.
])
```

## Component Communication

### Events

Components communicate through Livewire events:

```php
// Dispatch event from component
$this->dispatch('resource-saved', id: $resource->id);

// Listen in another component
protected $listeners = ['resource-saved' => 'handleResourceSaved'];

public function handleResourceSaved($id)
{
    $this->resource = Resource::find($id);
    $this->refreshData();
}
```

### Direct Component Calls

```php
// Call method on another component
$this->dispatch('refreshComponent')->to('resource-table');

// Self-referencing
$this->dispatch('$refresh');
```

### JavaScript Integration

```blade
@script
<script>
    // Listen for Livewire events in Alpine
    Alpine.data('resourceManager', () => ({
        resources: [],
        
        init() {
            Livewire.on('resource-updated', (data) => {
                this.refreshResources();
            });
        },
        
        refreshResources() {
            // Update UI
        }
    }));
</script>
@endscript
```

## Creating Custom Components

### Basic Structure

```bash
php artisan make:livewire CustomComponent
```

```php
namespace App\Http\Livewire;

use Aura\Base\Traits\WithLivewireHelpers;
use Livewire\Component;

class CustomComponent extends Component
{
    use WithLivewireHelpers;
    
    public $data = [];
    
    protected $rules = [
        'data.name' => 'required|string',
        'data.email' => 'required|email',
    ];
    
    public function mount($parameters = [])
    {
        $this->data = $parameters;
    }
    
    public function save()
    {
        $this->validate();
        
        // Save logic
        
        $this->notify('Saved successfully!');
    }
    
    public function render()
    {
        return view('livewire.custom-component');
    }
}
```

### Extending Aura Components

```php
use Aura\Base\Livewire\Table\Table;

class CustomTable extends Table
{
    protected function query()
    {
        return parent::query()
            ->where('custom_field', true);
    }
    
    public function columns()
    {
        $columns = parent::columns();
        
        // Add custom column
        $columns['custom_field'] = [
            'label' => 'Custom',
            'sortable' => true,
            'component' => 'custom-cell',
        ];
        
        return $columns;
    }
}
```

### Component Traits

Create reusable functionality with traits:

```php
trait WithExport
{
    public function exportCsv()
    {
        $data = $this->getData();
        
        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($handle, array_keys($data[0]));
            
            // Add data
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        }, 'export.csv');
    }
}
```


## Performance Optimization

### Lazy Loading

```php
class HeavyComponent extends Component
{
    public $readyToLoad = false;
    
    public function loadData()
    {
        $this->readyToLoad = true;
    }
    
    public function render()
    {
        return view('livewire.heavy-component', [
            'data' => $this->readyToLoad 
                ? $this->getExpensiveData() 
                : [],
        ]);
    }
}
```

In your view:
```blade
<div wire:init="loadData">
    @if($readyToLoad)
        <!-- Heavy content -->
    @else
        <x-aura::loading />
    @endif
</div>
```

### Pagination

```php
use Livewire\WithPagination;

class ResourceList extends Component
{
    use WithPagination;
    
    public function render()
    {
        return view('livewire.resource-list', [
            'resources' => Resource::paginate(10),
        ]);
    }
}
```

### Debouncing

```blade
<!-- Debounce search input -->
<input wire:model.live.debounce.500ms="search" type="search">

<!-- Lazy update on blur -->
<input wire:model.blur="email" type="email">
```

### Computed Properties

```php
use Livewire\Attributes\Computed;

class StatsComponent extends Component
{
    #[Computed]
    public function totalUsers()
    {
        return Cache::remember('total_users', 3600, function () {
            return User::count();
        });
    }
    
    public function render()
    {
        return view('livewire.stats', [
            'total' => $this->totalUsers,
        ]);
    }
}
```

## Testing Components

### Basic Component Testing

```php
use Livewire\Livewire;

test('can create resource', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(Create::class, ['slug' => 'posts'])
        ->assertSee('Create Post')
        ->set('form.fields.title', 'Test Post')
        ->set('form.fields.content', 'Test content')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('aura.posts.index'));
    
    expect(Post::where('title', 'Test Post')->exists())->toBeTrue();
});
```

### Testing Validation

```php
test('validates required fields', function () {
    Livewire::test(Create::class, ['slug' => 'posts'])
        ->call('save')
        ->assertHasErrors(['form.fields.title' => 'required'])
        ->set('form.fields.title', 'ab') // Too short
        ->call('save')
        ->assertHasErrors(['form.fields.title' => 'min']);
});
```

### Testing Events

```php
test('dispatches event on save', function () {
    Livewire::test(Create::class, ['slug' => 'posts'])
        ->set('form.fields.title', 'Test')
        ->call('save')
        ->assertDispatched('resource-saved');
});
```

### Testing Component Communication

```php
test('components communicate via events', function () {
    $component1 = Livewire::test(Component1::class);
    $component2 = Livewire::test(Component2::class);
    
    $component1->call('triggerEvent')
        ->assertDispatched('custom-event');
    
    $component2->dispatch('custom-event', ['data' => 'test'])
        ->assertSet('receivedData', 'test');
});
```

## Best Practices

### 1. Use Computed Properties

```php
// Bad - Runs query on every render
public function render()
{
    return view('livewire.users', [
        'users' => User::with('posts')->get(),
    ]);
}

// Good - Caches result
#[Computed]
public function users()
{
    return User::with('posts')->get();
}
```

### 2. Validate Early

```php
// Real-time validation
protected $rules = [
    'email' => 'required|email',
];

public function updated($propertyName)
{
    $this->validateOnly($propertyName);
}
```

### 3. Use Wire Keys

```blade
<!-- Prevent DOM diffing issues -->
@foreach($items as $item)
    <div wire:key="item-{{ $item->id }}">
        @livewire('item-component', ['item' => $item], key($item->id))
    </div>
@endforeach
```

### 4. Optimize Queries

```php
// Eager load relationships
public function mount()
{
    $this->posts = Post::with(['author', 'category', 'tags'])->get();
}
```

### 5. Handle Loading States

```blade
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>
```

### 6. Security First

```php
public function deleteResource($id)
{
    $resource = Resource::findOrFail($id);
    
    // Always authorize
    $this->authorize('delete', $resource);
    
    $resource->delete();
}
```

### 7. Use Aura Helpers

```php
// Notifications
$this->notify('Success!', 'success');

// Refresh components
$this->dispatch('$refresh')->to('resource-table');

// Close modals
$this->closeModal();
```

## Pro Tips

**1. Custom Validation Messages**
```php
protected $messages = [
    'form.fields.email.required' => 'We need your email address.',
    'form.fields.email.email' => 'That doesn\'t look like a valid email.',
];
```

**2. Dynamic Components**
```blade
@livewire($componentName, $componentParams)
```

**3. Lifecycle Hooks**
```php
public function booted()
{
    // Runs after component is fully booted
}

public function updated($property)
{
    // Runs after any property is updated
}

public function updatedFormFieldsTitle($value)
{
    // Runs when specific nested property updates
    $this->form['fields']['slug'] = Str::slug($value);
}
```

**4. File Downloads**
```php
public function downloadReport()
{
    return response()->download(
        storage_path('reports/monthly.pdf'),
        'monthly-report.pdf'
    );
}
```

**5. Temporary URLs**
```php
public function getDownloadUrl()
{
    return Storage::temporaryUrl(
        'reports/confidential.pdf',
        now()->addMinutes(5)
    );
}
```

> 📹 **Video Placeholder**: [Show creating a custom Livewire component from scratch, including traits, events, and testing]

## Common Pitfalls

1. **Forgetting wire:key** - Always use wire:key in loops
2. **Not validating in mount()** - Validate initial data too
3. **Overusing computed properties** - They run on every request
4. **Not handling loading states** - Users need feedback
5. **Ignoring authorization** - Always check permissions
6. **Not using eager loading** - Causes N+1 queries
7. **Storing sensitive data in public properties** - Use private/protected

## Conclusion

Aura CMS's Livewire components provide a powerful foundation for building dynamic applications. By understanding these components and following best practices, you can create responsive, secure, and maintainable interfaces that delight your users.

For more advanced topics, see the [API Reference](api-reference.md) and [Performance Optimization](performance.md) guides.