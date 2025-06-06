# The Table Component in Aura CMS

The Table component is a powerful and flexible Livewire component designed to display and manage data records efficiently. It combines the power of Laravel's backend with a reactive frontend powered by Alpine.js and Tailwind CSS, offering features like pagination, sorting, searching, filtering, bulk actions, and multiple view modes.

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Component Structure](#component-structure)
- [View Modes](#view-modes)
- [Configuration](#configuration)
- [User Preferences](#user-preferences)
- [Advanced Features](#advanced-features)
- [Customization](#customization)
- [Advanced Examples](#advanced-examples)

<a name="overview"></a>
## Overview

The Table component serves as a central interface for displaying and managing resource data in Aura CMS. It's built using Livewire for real-time interactivity and Alpine.js for frontend functionality, providing a seamless user experience.

### Basic Usage

In your resource class (e.g., `app/Resources/Post.php`):

```php
use Aura\Base\Resource;

class Post extends Resource
{
    public function defaultTableView()
    {
        return 'list';  // or 'grid', 'kanban'
    }

    public function defaultPerPage()
    {
        return 25;
    }

    public function getBulkActions()
    {
        return [
            'publish' => [
                'label' => 'Publish Selected',
                'modal' => 'publish-modal'
            ],
            'export' => [
                'label' => 'Export Selected',
                'method' => 'collection'
            ],
            'delete' => 'Delete Selected'
        ];
    }

    public function defaultTableSort()
    {
        return 'created_at';
    }

    public function defaultTableSortDirection()
    {
        return 'desc';
    }
}
```

In your Blade view:

```php
<livewire:aura.base.livewire.table.table
    :model="App\Resources\Post::class"
    :settings="[
        'per_page' => 20,
        'default_view' => 'list',
        'selectable' => true,
        'bulk_actions' => true,
        'search' => true,
        'filters' => true
    ]"
/>
```

<a name="key-features"></a>
## Key Features

### 1. Multiple View Modes
- **List View**: Traditional table layout with sortable columns
- **Grid View**: Card-based layout ideal for visual content
- **Kanban View**: Drag-and-drop interface for status-based workflows

### 2. Advanced Selection
- Single row selection
- Multi-row selection with Shift+Click support
- Select all rows on current page
- Select all rows across pages
- Maximum selection limit configuration

### 3. Dynamic Filtering
- Custom filter builder
- Saved filters (user and team-specific)
- Advanced filter operators
- Filter groups with AND/OR conditions

### 4. Real-time Search
- Global search across configured fields
- Debounced search input
- Customizable search scope

### 5. Column Management
- Drag-and-drop column reordering
- Column visibility toggle
- User-specific column preferences
- Global column settings

<a name="component-structure"></a>
## Component Structure

The Table component uses a trait-based architecture for modularity:

```php
class Table extends Component
{
    use BulkActions;
    use Filters;
    use Kanban;
    use PerPagePagination;
    use QueryFilters;
    use Search;
    use Select;
    use Settings;
    use Sorting;
    use SwitchView;
}
```

### Core Properties

```php
public $columns = [];           // List of table columns
public $settings = [];          // Table configuration
public $currentView;           // Current view mode
public $selected = [];         // Selected row IDs
public $filters = [];          // Active filters
public $search;               // Search query
```

<a name="view-modes"></a>
## View Modes

### List View
The default table view with sortable columns and row actions.

```php
public function tableView()
{
    return 'aura::components.table.list';
}
```

### Grid View
Card-based layout ideal for media and visual content.

```php
public function tableGridView()
{
    return 'aura::components.table.grid';
}
```

### Kanban View
Status-based view with drag-and-drop functionality.

```php
public function tableKanbanView()
{
    return 'aura::components.table.kanban';
}
```

<a name="configuration"></a>
## Configuration

### Default Settings

```php
public function defaultSettings()
{
    return [
        'per_page' => 10,
        'columns' => $this->model->getTableHeaders(),
        'search' => true,
        'filters' => true,
        'selectable' => true,
        'default_view' => 'list',
        'actions' => true,
        'bulk_actions' => true,
        'header' => true,
        'settings' => true,
        'sort_columns' => true,
        'columns_user_key' => 'columns.'.$this->model->getType(),
        'views' => [
            'table' => 'aura::components.table.index',
            'list' => $this->model->tableView(),
            'grid' => $this->model->tableGridView(),
            'kanban' => $this->model->tableKanbanView(),
        ],
    ];
}
```

### Resource-specific Configuration

```php
class Post extends Resource
{
    public function defaultTableView()
    {
        return 'grid';  // Set default view mode
    }

    public function defaultPerPage()
    {
        return 25;  // Set default pagination
    }
}
```

<a name="user-preferences"></a>
## User Preferences

The Table component automatically saves and restores user preferences for:

- View mode
- Column order and visibility
- Filters
- Pagination settings
- Sort order

### Preference Storage

```php
protected function saveViewPreference()
{
    auth()->user()->updateOption(
        'table_view.'.$this->model()->getType(),
        $this->currentView
    );
}
```

<a name="advanced-features"></a>
## Advanced Features

### 1. Bulk Actions

```php
public function getBulkActions()
{
    return [
        'exportSelected' => 'Export Selected',
        'deleteSelected' => 'Delete Selected',
        'publishSelected' => [
            'label' => 'Publish',
            'modal' => 'publish-modal'
        ]
    ];
}
```

### 2. Custom Filters

```php
public function addFilter()
{
    $this->filters['custom'][] = [
        'name' => $this->fieldsForFilter->keys()->first(),
        'operator' => 'contains',
        'value' => null,
        'main_operator' => 'and'
    ];
}
```

### 3. Row Selection

```javascript
toggleRow(event, id) {
    if (event.shiftKey && this.lastSelectedId !== null) {
        // Handle shift-select range
        const currentIndex = this.rows.indexOf(id);
        const lastIndex = this.rows.indexOf(this.lastSelectedId);
        // ... range selection logic
    } else {
        // Regular single row toggle
        // ... toggle logic
    }
}
```
<a name="customization"></a>
## Customization

### Custom Views

In your resource class:

```php
class Post extends Resource
{
    public function tableView()
    {
        return 'resources.views.posts.table-list';
    }

    public function tableGridView()
    {
        return 'resources.views.posts.table-grid';
    }

    public function tableKanbanView()
    {
        return 'resources.views.posts.table-kanban';
    }

    public function rowView()
    {
        return 'resources.views.posts.table-row';
    }
}
```

### Custom Query Logic

In your resource class:

```php
class Post extends Resource
{
    public function modifySearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhereHas('author', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    public function sort_custom($query, $direction)
    {
        return $query->orderBy('custom_field', $direction)
                    ->orderBy('id', 'desc');
    }
}
```

### Custom Filters

In your resource class:

```php
class Post extends Resource
{
    public function getSearchableFields()
    {
        return collect([
            'title',
            'content',
            'author_name',
            'status'
        ]);
    }

    public function fieldBySlug($slug)
    {
        return [
            'status' => [
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft', 'color' => 'gray'],
                    ['key' => 'published', 'value' => 'Published', 'color' => 'green'],
                    ['key' => 'archived', 'value' => 'Archived', 'color' => 'red']
                ]
            ]
        ][$slug] ?? [];
    }
}
```

### Custom Bulk Actions

In your resource class:

```php
class Post extends Resource
{
    public function getBulkActions()
    {
        return [
            'publish' => [
                'label' => 'Publish',
                'modal' => 'publish-modal',
                'method' => 'collection'
            ],
            'archive' => [
                'label' => 'Archive',
                'modal' => 'archive-modal'
            ],
            'export' => [
                'label' => 'Export',
                'method' => 'collection'
            ]
        ];
    }

    public function publish($ids)
    {
        // Handle publishing logic for collection of IDs
        Post::whereIn('id', $ids)->update(['status' => 'published']);
    }

    public function archive()
    {
        // Handle archiving logic for single item
        $this->status = 'archived';
        $this->save();
    }
}
```

### Kanban Configuration

In your resource class:

```php
class Post extends Resource
{
    public function kanbanQuery($query)
    {
        return $query->with(['author', 'category'])
                    ->orderBy('position');
    }

    public function kanbanPagination()
    {
        return 50; // Items per status column
    }
}
```

<a name="advanced-examples"></a>
## Advanced Examples

### Custom Field Filters

In your resource class:

```php
class Post extends Resource
{
    public function getFields()
    {
        return [
            'status' => [
                'type' => \Aura\Base\Fields\Select::class,
                'name' => 'Status',
                'slug' => 'status',
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft'],
                    ['key' => 'published', 'value' => 'Published']
                ]
            ],
            'category' => [
                'type' => \Aura\Base\Fields\AdvancedSelect::class,
                'name' => 'Category',
                'slug' => 'category',
                'resource' => \App\Resources\Category::class
            ],
            'tags' => [
                'type' => \Aura\Base\Fields\Tags::class,
                'name' => 'Tags',
                'slug' => 'tags',
                'resource' => \App\Resources\Tag::class
            ]
        ];
    }
}
```

### Custom Table Views

Create a custom list view (`resources/views/posts/table-list.blade.php`):

```php
<x-aura::table.wrapper>
    <x-slot name="header">
        <x-aura::table.th sortable="title">Title</x-aura::table.th>
        <x-aura::table.th sortable="status">Status</x-aura::table.th>
        <x-aura::table.th sortable="created_at">Created</x-aura::table.th>
        <x-aura::table.th>Actions</x-aura::table.th>
    </x-slot>

    @foreach($rows as $row)
        <tr>
            <x-aura::table.td>{{ $row->title }}</x-aura::table.td>
            <x-aura::table.td>
                <x-aura::badge :color="$row->status_color">
                    {{ $row->status }}
                </x-aura::badge>
            </x-aura::table.td>
            <x-aura::table.td>{{ $row->created_at->diffForHumans() }}</x-aura::table.td>
            <x-aura::table.td>
                <x-aura::dropdown>
                    <x-slot name="trigger">
                        <x-aura::button.transparent size="xs">
                            Actions
                        </x-aura::button.transparent>
                    </x-slot>

                    <x-aura::dropdown.item wire:click="edit({{ $row->id }})">
                        Edit
                    </x-aura::dropdown.item>
                    <x-aura::dropdown.item wire:click="delete({{ $row->id }})">
                        Delete
                    </x-aura::dropdown.item>
                </x-aura::dropdown>
            </x-aura::table.td>
        </tr>
    @endforeach
</x-aura::table.wrapper>
```

Create a custom grid view (`resources/views/posts/table-grid.blade.php`):

```php
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($rows as $row)
        <div class="flex relative flex-col p-6 bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-medium">{{ $row->title }}</h3>
                <x-aura::badge :color="$row->status_color">
                    {{ $row->status }}
                </x-aura::badge>
            </div>

            <div class="mt-4 text-sm text-gray-500">
                {{ Str::limit($row->description, 100) }}
            </div>

            <div class="flex justify-end items-center pt-4 mt-4 space-x-2 border-t">
                <x-aura::button.light wire:click="edit({{ $row->id }})">
                    Edit
                </x-aura::button.light>
                <x-aura::button.danger wire:click="delete({{ $row->id }})">
                    Delete
                </x-aura::button.danger>
            </div>
        </div>
    @endforeach
</div>
```

### Custom Row Actions

In your resource class:

```php
class Post extends Resource
{
    public function getActions()
    {
        return [
            'preview' => [
                'label' => 'Preview',
                'icon' => 'eye',
                'url' => fn($row) => route('posts.preview', $row)
            ],
            'duplicate' => [
                'label' => 'Duplicate',
                'icon' => 'duplicate',
                'action' => 'duplicate',
                'confirm' => 'Are you sure you want to duplicate this post?'
            ],
            'export' => [
                'label' => 'Export',
                'icon' => 'download',
                'modal' => 'export-modal'
            ]
        ];
    }

    public function duplicate()
    {
        $clone = $this->replicate();
        $clone->title = $this->title . ' (Copy)';
        $clone->save();

        return $clone;
    }
}
```

### Advanced Sorting

In your resource class:

```php
class Post extends Resource
{
    public function sort_popularity($query, $direction)
    {
        return $query->withCount('views')
                    ->orderBy('views_count', $direction);
    }

    public function sort_engagement($query, $direction)
    {
        return $query->withCount(['comments', 'likes'])
                    ->orderByRaw('(comments_count + likes_count) ' . $direction);
    }
}
```

<a name="events"></a>
## Events

The Table component emits several events that you can listen to:

- `tableMounted`: When the table is mounted
- `selectedRows`: When rows are selected/deselected
- `rowIdsUpdated`: When the available row IDs change
- `refreshTable`: To refresh the table data
- `refreshTableSelected`: To refresh selected rows
