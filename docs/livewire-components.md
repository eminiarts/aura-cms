# Livewire Components

Aura CMS leverages Livewire 3 to create dynamic, reactive user interfaces without writing JavaScript. This guide covers all built-in Livewire components, how to use them, customize them, and create your own.

## Table of Contents

1. [Introduction](#introduction)
2. [Component Registration](#component-registration)
3. [Core Components](#core-components)
4. [Resource Components](#resource-components)
5. [Table Component](#table-component)
6. [Media Components](#media-components)
7. [Modal & Overlay System](#modal--overlay-system)
8. [Form Components](#form-components)
9. [Navigation & UI Components](#navigation--ui-components)
10. [Resource Editor Components](#resource-editor-components)
11. [Team Components](#team-components)
12. [Authentication Components](#authentication-components)
13. [Plugin Management](#plugin-management)
14. [Widget Components](#widget-components)
15. [Component Communication](#component-communication)
16. [Creating Custom Components](#creating-custom-components)
17. [Performance Optimization](#performance-optimization)
18. [Testing Components](#testing-components)
19. [Best Practices](#best-practices)
20. [Component Quick Reference](#component-quick-reference)

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

## Component Registration

All Aura CMS Livewire components are registered with the `aura::` prefix. The component names use **kebab-case** (hyphens, not dots). Here's how components are registered in `AuraServiceProvider`:

```php
// Resource components
Livewire::component('aura::resource-index', Index::class);
Livewire::component('aura::resource-create', Create::class);
Livewire::component('aura::resource-edit', Edit::class);
Livewire::component('aura::resource-view', View::class);

// Modal variants
Livewire::component('aura::resource-create-modal', CreateModal::class);
Livewire::component('aura::resource-edit-modal', EditModal::class);
Livewire::component('aura::resource-view-modal', ViewModal::class);

// Core components
Livewire::component('aura::dashboard', Dashboard::class);
Livewire::component('aura::navigation', Navigation::class);
Livewire::component('aura::global-search', GlobalSearch::class);
Livewire::component('aura::table', Table::class);
```

## Core Components

### Dashboard Component

The main dashboard that users see after login. The component class is configurable via `config/aura.php`.

```php
use Aura\Base\Livewire\Dashboard;

// In your view
@livewire('aura::dashboard')
```

**Features:**
- Widget display
- Customizable layout via config

**Customization:**

You can customize the dashboard by creating your own component and updating the config:

```php
// config/aura.php
'components' => [
    'dashboard' => \App\Livewire\CustomDashboard::class,
],
```

```php
// app/Livewire/CustomDashboard.php
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

### Global Search

Provides instant search across all resources with keyboard shortcut (⇧⌘K). The component is enabled via `config('aura.features.global_search')`.

```php
@livewire('aura::global-search')
```

**Configuration:**
```php
// In your Resource
public static function getGlobalSearch(): bool
{
    return true; // Include in global search (default: true)
}

// Define searchable fields
public function getSearchableFields()
{
    return collect($this->inputFields())
        ->filter(fn($field) => $field['searchable'] ?? false);
}
```

**Features:**
- Searches across all resources where `getGlobalSearch()` returns `true`
- Searches in user names and emails
- Displays bookmarked pages for quick access
- Results are grouped by resource type
- Limited to 15 results for performance

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

Lists resources with the Table component. Uses the `AuthorizesRequests` trait for permission checks.

```php
@livewire('aura::resource-index', ['slug' => 'posts'])
```

**Features:**
- Automatic authorization via `viewAny` policy
- Respects `$indexViewEnabled` static property on resources
- Customizable views via `indexView()` method on resource

**Key Properties:**
- `$slug` - The resource slug
- `$resource` - The resource instance

### Resource Create

Form component for creating new resources. Includes traits for field handling, media uploads, and repeater fields.

```php
@livewire('aura::resource-create', ['slug' => 'posts'])
```

**Traits Used:**
- `AuthorizesRequests` - Permission checks
- `InteractsWithFields` - Field interaction handling
- `MediaFields` - Media field support
- `RepeaterFields` - Repeater field support
- `WithFileUploads` - File upload support

**Key Properties:**
- `$form` - Form data array with `fields` key
- `$model` - The resource model
- `$slug` - Resource slug
- `$inModal` - Whether rendered in modal
- `$showSaveButton` - Control save button visibility

**Customization:**
```php
use Aura\Base\Livewire\Resource\Create;

class PostCreate extends Create
{
    public function save()
    {
        $this->validate();
        
        // Pre-save hook
        $this->form['fields']['published_at'] = now();
        
        parent::save();
    }
}
```

### Resource Edit

Form component for editing existing resources.

```php
@livewire('aura::resource-edit', ['slug' => 'posts', 'id' => $post->id])
```

**Additional Traits:**
- `HasActions` - Resource action support

**Key Methods:**
- `save()` - Validates and updates the resource
- `reload()` - Refreshes the model from database

**Events Listened:**
- `saveModel` - Triggers save
- `refreshComponent` - Refreshes component
- `reload` - Reloads model data

### Resource View

Display resource in read-only mode.

```php
@livewire('aura::resource-view', ['slug' => 'posts', 'id' => $post->id])
```

**Key Properties:**
- `$mode` = 'view' - Indicates view mode

### Modal Variants

All resource components have modal variants for use in popups:

```php
// Create in modal
@livewire('aura::resource-create-modal', ['slug' => 'posts', 'params' => [...]])

// Edit in modal
@livewire('aura::resource-edit-modal', ['slug' => 'posts', 'id' => $id])

// View in modal
@livewire('aura::resource-view-modal', ['slug' => 'posts', 'id' => $id])
```

Modal variants extend their parent components and set `$inModal = true`.

## Table Component

The most powerful component in Aura CMS, providing advanced data display and manipulation.

### Basic Usage

```php
use Aura\Base\Livewire\Table\Table;

@livewire('aura::table', [
    'model' => $model,
    'settings' => [...],
])
```

### Key Properties

```php
public $columns = [];      // Table columns
public $model;             // Resource model
public $parent;            // Parent model (for relations)
public $field;             // Field configuration
public $settings;          // Table settings
public $loaded = false;    // Lazy loading state
```

### Key Methods

```php
// Get table rows with pagination
protected function rows()

// Build the query with filters, search, sorting
public function rowsQuery()

// Execute actions on rows
public function action($data)

// Reorder columns
public function reorder($slugs)
```

### Table Traits

The table component uses modular traits for functionality:

```php
use BulkActions;          // Bulk operations on rows
use Filters;              // Advanced filtering
use Kanban;               // Kanban board view
use Search;               // Search functionality
use Select;               // Row selection
use Settings;             // User preferences (column visibility, saved views)
use Sorting;              // Column sorting
use SwitchView;           // Toggle between table/grid/kanban views
use PerPagePagination;    // Items per page control
use QueryFilters;         // Custom query filters
use CachedRows;           // Row caching for performance
```

### Events

The table dispatches and listens to these events:

```php
// Dispatched
$this->dispatch('tableMounted');
$this->dispatch('rowIdsUpdated', $rowIds);

// Listened
#[On('refreshTable')]
#[On('refreshTableSelected')]
#[On('selectedRows')]
#[On('selectFieldRows')]
#[On('selectRowsRange')]
```

### Customizing the Table

```php
use Aura\Base\Livewire\Table\Table;

class CustomTable extends Table
{
    protected function query()
    {
        return parent::query()
            ->where('status', 'published');
    }
}
```


## Media Components

### Media Manager

Modal-based media library interface for selecting attachments.

```php
@livewire('aura::media-manager', [
    'slug' => 'field-slug',
    'selected' => [1, 2, 3],  // Pre-selected attachment IDs
    'model' => $resourceClass,
    'modalAttributes' => [...],
])
```

**Key Properties:**
- `$selected` - Array of selected attachment IDs
- `$fieldSlug` - The field this manager is for
- `$field` - Field configuration

**Events:**
```php
// Dispatched when selection is confirmed
$this->dispatch('updateField', [
    'slug' => $this->fieldSlug,
    'value' => $this->selected,
]);
$this->dispatch('media-manager-selected');
$this->dispatch('closeModal');

// Listened
#[On('selectedRows')]
#[On('tableMounted')]
#[On('updateField')]
```

**Usage Example:**
```php
<div x-data="{ showMediaManager: false }">
    <button @click="showMediaManager = true">Select Image</button>
    
    <div x-show="showMediaManager">
        @livewire('aura::media-manager', [
            'slug' => 'featured_image',
            'selected' => $selectedIds,
            'model' => \App\Resources\Post::class,
        ])
    </div>
</div>
```

### Media Uploader

Drag-and-drop file upload component with automatic attachment creation.

```php
@livewire('aura::media-uploader', [
    'field' => $fieldConfig,
    'selected' => $selectedIds,
    'button' => false,      // Show upload button
    'table' => true,        // Show table of uploads
    'disabled' => false,    // Disable uploads
])
```

**Key Features:**
- Uses `WithFileUploads` trait
- Automatic attachment creation in database
- Dispatches `updateField` and `refreshTable` events
- Max file size: 100MB

**How It Works:**
```php
public function updatedMedia()
{
    $this->validate([
        'media.*' => 'required|max:102400', // 100MB Max
    ]);

    foreach ($this->media as $media) {
        $url = $media->store('media', 'public');
        
        $attachment = Attachment::create([
            'url' => $url,
            'name' => $media->getClientOriginalName(),
            'title' => $media->getClientOriginalName(),
            'size' => $media->getSize(),
            'mime_type' => $media->getMimeType(),
        ]);
    }
    
    $this->dispatch('updateField', [...]);
    $this->dispatch('refreshTable');
}
```

### Attachment Index

Lists all attachments with table functionality.

```php
@livewire('aura::attachment-index')
```

## Modal & Overlay System

### Modals Component

The main modals container that handles opening and closing modals.

```php
@livewire('aura::modals')
```

### Modal Component

Individual modal component for dialogs.

```php
// The Modal component
use Aura\Base\Livewire\Modal;

class Modal extends Component
{
    public $id;
    public $params;
    
    #[On('modalOpened')]
    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }
}
```

**Opening Modals:**
```php
// From a Livewire component
$this->dispatch('openModal', 'component-name', ['param' => 'value']);

// From Blade
<button wire:click="$dispatch('openModal', 'user-form', { userId: {{ $user->id }} })">
    Edit User
</button>
```

**Modal Components can define their size:**
```php
class CreateResource extends Component
{
    public static function modalClasses(): string
    {
        return 'max-w-xl';  // or 'max-w-7xl' for full width
    }
}
```

### Slide-Over Panel

Side panel for forms and details, activated via events.

```php
@livewire('aura::slide-over')
```

**Opening a Slide-Over:**
```php
$this->dispatch('openSlideOver', 
    component: 'edit-field', 
    parameters: ['fieldSlug' => $slug, 'model' => $model]
);
```

**The SlideOver Component:**
```php
class SlideOver extends Component
{
    #[On('slideOverOpened')]
    public function activate($id, $params)
    {
        $this->mount($id, $params);
    }
}
```

## Form Components

### Resource Form

The `ResourceForm` class in `Aura\Base\Livewire\Forms\ResourceForm` provides form object functionality.

### Field Components

Each field type has its own Livewire handling. Fields communicate via the `updateField` event:

```php
// Listening for field updates
#[On('updateField')]
public function updateField($field, $value)
{
    // Handle field update
}

// Dispatching field updates
$this->dispatch('updateField', [
    'slug' => $fieldSlug,
    'value' => $newValue,
]);
```

**Text field with real-time validation:**
```html
<div>
    <x-aura::input.text 
        wire:model.live="form.fields.title"
        wire:key="field-title"
        label="Title"
        :error="$errors->first('form.fields.title')"
    />
</div>
```

**Select field with dynamic options:**
```html
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

### Navigation

Dynamic sidebar navigation that responds to theme settings and user preferences.

```php
@livewire('aura::navigation')
```

**Key Features:**
- Collapsible sidebar groups
- Persists toggle state per user
- Respects theme settings (compact, dark mode, sidebar type)

**Computed Properties:**
```php
#[Computed]
public function compact(): string;        // Sidebar size
#[Computed]
public function sidebarType(): string;    // primary/light/dark
#[Computed]
public function darkmodeType(): string;   // auto/light/dark
#[Computed]
public function sidebarToggled();         // Toggle state
```

### Bookmarks

Allow users to bookmark frequently accessed pages.

```php
@livewire('aura::bookmark-page', [
    'site' => [
        'title' => 'Product List',
        'url' => request()->url(),
    ],
])
```

**Key Methods:**
```php
public function toggleBookmark();           // Add/remove bookmark
public function getIsBookmarkedProperty(); // Check if bookmarked
```

### Notifications

Notification center with tabbed view (unread/read).

```php
@livewire('aura::notifications')
```

**Features:**
- Displays unread and read notifications in tabs
- Mark all as read functionality
- Uses Laravel's notification system

**Key Properties:**
```php
public function getUnreadNotificationsProperty();  // Unread notifications
public function getNotificationsProperty();        // Read notifications
```

**Sending Notifications:**
```php
// The notify() macro is added to all Livewire components
$this->notify('Task completed successfully!', 'success');
$this->notify('Error processing request', 'error');
```

### Settings

System-wide settings management (super admin only).

```php
@livewire('aura::settings')
```

**Features:**
- Logo configuration (light/dark mode)
- Sidebar settings (size, type, colors)
- Theme color palette selection
- Custom color configuration
- Team-aware settings storage

**Available Settings:**
- `logo` / `logo-darkmode` - Logo images
- `sidebar-size` - standard/compact
- `sidebar-type` - primary/light/dark
- `darkmode-type` - auto/light/dark
- `color-palette` - Theme color (aura, red, blue, etc.)
- `gray-color-palette` - Gray scale colors

### User Settings

User-specific settings component.

```php
@livewire('aura::user-settings')
```

## Resource Editor Components

### Resource Editor

Visual editor for building resource fields. Only available for app resources (not vendor).

```php
@livewire('aura::resource-editor', ['slug' => 'post'])
```

**Features:**
- Drag and drop field reordering
- Add/edit/delete fields
- Tab management
- Template support
- Migration generation

**Key Properties:**
```php
public $fields = [];         // Current fields
public $fieldsArray = [];    // Flat fields array
public $globalTabs = [];     // Global tab configuration
public $hasGlobalTabs = false;
public $model;               // Resource model
public $slug;                // Resource slug
```

**Key Methods:**
```php
public function addField($id, $slug, $type, $children, $model);
public function addNewTab();
public function deleteField($data);
public function duplicateField($id, $slug, $model);
public function reorder($ids);
public function save();
public function generateMigration();  // Generate database migration
```

**Events:**
```php
#[On('deleteField')]
#[On('saveField')]
#[On('saveNewField')]
#[On('savedField')]
#[On('refreshComponent')]
#[On('finishedSavingFields')]
```

### Edit Resource Field

Slide-over component for editing individual field properties.

```php
// Opened via event
$this->dispatch('openSlideOver', 
    component: 'edit-field', 
    parameters: ['fieldSlug' => $slug, 'field' => $field, 'model' => $model]
);
```

**Features:**
- Field type selection
- Validation rules
- Conditional logic
- Display options (on_index, on_forms, on_view)
- Field-specific settings

### Create Resource

Modal for creating new resources (super admin only, non-production).

```php
@livewire('aura::create-resource')
```

**Features:**
- Creates new resource class file
- Runs `aura:resource` artisan command
- Redirects to resource editor

### Choose Template

Template selection component for resource editor.

```php
@livewire('aura::choose-template')
```

## Team Components

### Invite User

Modal for inviting users to a team.

```php
@livewire('aura::invite-user')
```

**Features:**
- Email and role selection
- Sends invitation email
- Validates unique email per team

## Authentication Components

### Profile

User profile management component.

```php
@livewire('aura::profile')
```

**Features:**
- Edit profile fields
- Change password
- Logout other browser sessions
- Delete account

**Key Methods:**
```php
public function save();
public function deleteUser(Request $request);
public function logoutOtherBrowserSessions();
```

### Two Factor Authentication Form

Manage 2FA settings (requires Laravel Fortify).

```php
@livewire('aura::two-factor-authentication-form')
```

**Features:**
- Enable/disable 2FA
- Display QR code for setup
- Show recovery codes
- Regenerate recovery codes

## Plugin Management

### Plugins Page

View and manage installed Composer packages.

```php
@livewire('aura::plugins-page')
```

**Features:**
- List installed packages with versions
- Check for updates via Packagist
- Update individual packages

## Widget Components

Aura CMS includes several widget components for dashboards:

```php
@livewire('aura::widgets')                    // Widget container
@livewire('aura::widgets.value-widget')       // Simple value display
@livewire('aura::widgets.sparkline-area')     // Area sparkline chart
@livewire('aura::widgets.sparkline-bar')      // Bar sparkline chart
@livewire('aura::widgets.donut')              // Donut chart
@livewire('aura::widgets.pie')                // Pie chart
@livewire('aura::widgets.bar')                // Bar chart
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
namespace App\Livewire;

use Livewire\Component;

class CustomComponent extends Component
{
    public $form = [
        'fields' => [],
    ];
    
    public function mount($parameters = [])
    {
        // Initialization
    }
    
    public function save()
    {
        $this->validate();
        
        // Save logic
        
        // notify() is available on all Livewire components
        $this->notify('Saved successfully!');
    }
    
    public function render()
    {
        return view('livewire.custom-component');
    }
}
```

### Using Aura Traits

Aura provides several traits for common functionality:

```php
use Aura\Base\Traits\InputFields;      // Field handling
use Aura\Base\Traits\MediaFields;      // Media field support
use Aura\Base\Traits\RepeaterFields;   // Repeater field support
use Aura\Base\Traits\InteractsWithFields; // Field interaction
use Aura\Base\Traits\HasActions;       // Resource actions

class CustomComponent extends Component
{
    use InputFields;
    use MediaFields;
    
    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'validation' => 'required',
            ],
        ];
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
            ->where('status', 'active');
    }
}
```

```php
use Aura\Base\Livewire\Resource\Create;

class CustomCreate extends Create
{
    public function save()
    {
        // Pre-save logic
        $this->form['fields']['created_by'] = auth()->id();
        
        parent::save();
        
        // Post-save logic
    }
}
```

### Configurable Components

Dashboard, Settings, and Profile components are configurable via `config/aura.php`:

```php
// config/aura.php
'components' => [
    'dashboard' => \Aura\Base\Livewire\Dashboard::class,
    'settings' => \Aura\Base\Livewire\Settings::class,
    'profile' => \Aura\Base\Livewire\Profile::class,
],
```

Replace with your own classes to customize behavior.


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

Aura CMS uses Pest for testing. The `tests/Pest.php` file provides helper functions.

### Basic Component Testing

```php
use Aura\Base\Livewire\Resource\Create;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('can create resource', function () {
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'Test Post')
        ->call('save')
        ->assertHasNoErrors();
});
```

### Testing Validation

```php
use Aura\Base\Livewire\Resource\Create;
use function Pest\Livewire\livewire;

test('validates required fields', function () {
    $this->actingAs(createSuperAdmin());
    
    livewire(Create::class, ['slug' => 'post'])
        ->call('save')
        ->assertHasErrors(['form.fields.title']);
});
```

### Testing Events

```php
test('dispatches event on save', function () {
    $this->actingAs(createSuperAdmin());
    
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'Test')
        ->call('save')
        ->assertDispatched('closeModal');
});
```

### Testing Table Component

```php
use Aura\Base\Livewire\Table\Table;
use function Pest\Livewire\livewire;

test('table displays resources', function () {
    $this->actingAs(createSuperAdmin());
    
    $resource = Aura::findResourceBySlug('post');
    
    livewire(Table::class, [
        'model' => $resource,
        'settings' => $resource->indexTableSettings(),
    ])
        ->assertSuccessful();
});
```

### Helper Functions

Available in `tests/Pest.php`:

```php
createSuperAdmin();           // Creates super admin with team
createSuperAdminWithoutTeam(); // Super admin without team
createAdmin();                // Admin with limited permissions
createPost();                 // Creates a test post
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
// Notifications (available on all Livewire components via macro)
$this->notify('Success!', 'success');
$this->notify('Error occurred', 'error');

// Refresh components
$this->dispatch('refreshTable');
$this->dispatch('refreshComponent');

// Close modals
$this->dispatch('closeModal');
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

## Common Pitfalls

1. **Forgetting wire:key** - Always use wire:key in loops
2. **Not validating in mount()** - Validate initial data too
3. **Overusing computed properties** - They run on every request
4. **Not handling loading states** - Users need feedback
5. **Ignoring authorization** - Always check permissions with `$this->authorize()`
6. **Not using eager loading** - Causes N+1 queries
7. **Storing sensitive data in public properties** - Use private/protected
8. **Using wrong component names** - Remember: `aura::resource-index` not `aura::resource.index`

## Component Quick Reference

| Component | Name | Description |
|-----------|------|-------------|
| Dashboard | `aura::dashboard` | Main dashboard |
| Navigation | `aura::navigation` | Sidebar navigation |
| Global Search | `aura::global-search` | Search across resources |
| Table | `aura::table` | Data table with filters |
| Resource Index | `aura::resource-index` | Resource listing |
| Resource Create | `aura::resource-create` | Create resource form |
| Resource Edit | `aura::resource-edit` | Edit resource form |
| Resource View | `aura::resource-view` | View resource details |
| Media Manager | `aura::media-manager` | Media selection modal |
| Media Uploader | `aura::media-uploader` | File upload |
| Settings | `aura::settings` | System settings |
| Profile | `aura::profile` | User profile |
| Notifications | `aura::notifications` | Notification center |
| Bookmarks | `aura::bookmark-page` | Page bookmarking |
| Resource Editor | `aura::resource-editor` | Visual field editor |
| Modals | `aura::modals` | Modal container |

## Conclusion

Aura CMS's Livewire components provide a powerful foundation for building dynamic applications. By understanding these components and following best practices, you can create responsive, secure, and maintainable interfaces.

For more advanced topics, see the [API Reference](api-reference.md) and [Performance Optimization](performance.md) guides.