# Table Component

> 📹 **Video Placeholder**: Complete walkthrough of Aura CMS Table component features including filtering, searching, bulk actions, and view modes

The Table component is the powerhouse of data presentation in Aura CMS. Built on Livewire and Alpine.js, it provides a feature-rich, reactive interface for managing resource data with advanced capabilities like multi-column sorting, complex filtering, bulk operations, and multiple view modes—all without page refreshes.

## Table of Contents

- [Introduction](#introduction)
- [Component Architecture](#component-architecture)
- [Basic Usage](#basic-usage)
- [View Modes](#view-modes)
  - [List View](#list-view)
  - [Grid View](#grid-view)
  - [Kanban View](#kanban-view)
- [Configuration Options](#configuration-options)
- [Search Functionality](#search-functionality)
- [Filtering System](#filtering-system)
  - [Custom Filters](#custom-filters)
  - [Saved Filters](#saved-filters)
  - [Filter Groups](#filter-groups)
- [Sorting & Ordering](#sorting--ordering)
- [Selection & Bulk Actions](#selection--bulk-actions)
- [Column Management](#column-management)
- [Pagination](#pagination)
- [User Preferences](#user-preferences)
- [Performance Optimization](#performance-optimization)
- [Advanced Customization](#advanced-customization)
- [Real-world Examples](#real-world-examples)
- [Troubleshooting](#troubleshooting)

## Introduction

The Table component is a sophisticated data management interface that handles everything from simple lists to complex data grids with relationships. It's designed to be both powerful for developers and intuitive for end-users.

### Key Features

- **Three View Modes**: List (table), Grid (cards), and Kanban (drag-and-drop boards)
- **Advanced Filtering**: Build complex queries with AND/OR logic, save filters for reuse
- **Smart Selection**: Single, multi-select with Shift+Click, select all with pagination awareness
- **Real-time Search**: Instant results with debouncing, searches across regular and meta fields
- **Bulk Operations**: Perform actions on multiple records simultaneously
- **Column Control**: Drag-and-drop reordering, show/hide columns, persistent preferences
- **Performance Optimized**: Lazy loading, query optimization, efficient meta field handling

## Component Architecture

The Table component (`Aura\Base\Livewire\Table\Table`) uses a modular trait-based architecture:

```php
class Table extends Component
{
    use BulkActions;      // Handles bulk operations on selected rows
    use Filters;          // Complex filtering system
    use Kanban;          // Kanban board functionality
    use PerPagePagination; // Custom pagination handling
    use QueryFilters;     // Query building for filters
    use Search;          // Search functionality
    use Select;          // Row selection logic
    use Settings;        // Table configuration
    use Sorting;         // Column sorting
    use SwitchView;      // View mode switching
}
```

### Core Properties

| Property | Type | Description |
|----------|------|-------------|
| `$model` | Resource | The resource model instance |
| `$columns` | array | Active table columns configuration |
| `$settings` | array | Table behavior settings |
| `$currentView` | string | Active view mode (list/grid/kanban) |
| `$selected` | array | Currently selected row IDs |
| `$filters` | array | Active filter configuration |
| `$search` | string | Current search query |
| `$sortField` | string | Active sort column |
| `$sortDirection` | string | Sort direction (asc/desc) |

## Basic Usage

### Resource Configuration

Configure your resource for optimal table display:

```php
<?php

namespace App\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    // Define default table view
    public function defaultTableView()
    {
        return 'list'; // 'list', 'grid', or 'kanban'
    }
    
    // Set default items per page
    public function defaultPerPage()
    {
        return 25;
    }
    
    // Define default sorting
    public function defaultTableSort()
    {
        return 'published_at';
    }
    
    public function defaultTableSortDirection()
    {
        return 'desc';
    }
    
    // Configure table headers
    public function getTableHeaders()
    {
        return [
            'title' => ['label' => 'Title', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'author_name' => ['label' => 'Author', 'sortable' => false],
            'published_at' => ['label' => 'Published', 'sortable' => true],
            'views_count' => ['label' => 'Views', 'sortable' => true],
        ];
    }
    
    // Define searchable fields
    public function getSearchableFields()
    {
        return collect(['title', 'content', 'author_name']);
    }
}
```

### Blade Implementation

```blade
{{-- Basic table --}}
<livewire:aura::table :model="App\Resources\Article::class" />

{{-- Table with custom settings --}}
<livewire:aura::table 
    :model="App\Resources\Article::class"
    :settings="[
        'per_page' => 50,
        'default_view' => 'grid',
        'selectable' => true,
        'bulk_actions' => true,
        'search' => true,
        'filters' => true,
        'create_url' => route('articles.create'),
        'header' => true
    ]"
/>

{{-- Table for relationship (e.g., comments on a post) --}}
<livewire:aura::table 
    :model="App\Resources\Comment::class"
    :parent="$post"
    :field="['slug' => 'comments', 'type' => 'HasMany']"
/>

```

## View Modes

The Table component offers three distinct view modes, each optimized for different use cases:

### List View

The traditional table layout with sortable columns, ideal for data-heavy displays.

```php
// In your resource
public function tableView()
{
    return 'aura::components.table.list'; // Default view
}

// Custom list view
public function tableView()
{
    return 'resources.articles.table-list';
}
```

**Features:**
- Sortable column headers
- Inline actions
- Compact data display
- Optimal for scanning large datasets
- Checkbox selection

**Best for:** Administrative interfaces, data reports, content management

### Grid View

Card-based layout perfect for visual content and media-rich resources.

```php
public function tableGridView()
{
    return 'aura::components.table.grid';
}

// Configure grid columns
public function gridColumns()
{
    return 3; // Number of columns (responsive)
}
```

**Features:**
- Visual card layout
- Image previews
- Key information display
- Responsive grid system
- Hover actions

**Best for:** Media galleries, product catalogs, visual content

### Kanban View

Drag-and-drop board interface for workflow management.

```php
public function tableKanbanView()
{
    return 'aura::components.table.kanban';
}

// Configure Kanban settings
public function kanbanStatusField()
{
    return 'status'; // Field to group by
}

public function kanbanStatuses()
{
    return [
        'todo' => ['label' => 'To Do', 'color' => 'gray'],
        'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
        'review' => ['label' => 'Review', 'color' => 'yellow'],
        'done' => ['label' => 'Done', 'color' => 'green'],
    ];
}
```

**Features:**
- Drag-and-drop between columns
- Status-based grouping
- Visual workflow
- Quick status updates
- Card limits per column

**Best for:** Project management, content workflows, task tracking

### Switching Views

Users can switch between views using the view selector:

```php
// Allow specific views
public function allowedTableViews()
{
    return ['list', 'grid']; // Exclude 'kanban'
}

}
```

## Configuration Options

The Table component offers extensive configuration through settings array:

### Complete Settings Reference

```php
$settings = [
    // Display settings
    'per_page' => 25,                    // Items per page
    'default_view' => 'list',            // Default view mode
    'header' => true,                    // Show table header
    'create_url' => null,                // Custom create URL
    
    // Feature toggles
    'search' => true,                    // Enable search
    'filters' => true,                   // Enable filtering
    'selectable' => true,                // Enable row selection
    'bulk_actions' => true,              // Enable bulk actions
    'actions' => true,                   // Enable row actions
    'settings' => true,                  // Enable settings dropdown
    'sort_columns' => true,              // Enable column reordering
    
    // Column configuration
    'columns' => [],                     // Column definitions
    'columns_user_key' => 'columns.posts', // User preference key
    'columns_global_key' => null,        // Global settings key
    
    // View customization
    'views' => [
        'table' => 'aura::components.table.index',
        'list' => 'aura::components.table.list',
        'grid' => 'aura::components.table.grid',
        'kanban' => 'aura::components.table.kanban',
        'header' => 'aura::components.table.header',
        'filter' => 'aura::components.table.filter',
        'bulk_actions' => 'aura::components.table.bulk-actions',
    ],
];
```

### Resource Method Configuration

Configure defaults in your resource class:

```php
class Article extends Resource
{
    // Table display settings
    public function defaultTableView(): string
    {
        return 'list';
    }
    
    public function defaultPerPage(): int
    {
        return 25;
    }
    
    public function maxPerPage(): int
    {
        return 100;
    }
    
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }
    
    // Feature visibility
    public function showTableSearch(): bool
    {
        return true;
    }
    
    public function showTableFilters(): bool
    {
        return true;
    }
    
    public function showTableSettings(): bool
    {
        return auth()->user()->can('manage-table-settings');
    }
    
    // Selection limits
    public function maxTableSelection(): ?int
    {
        return 100; // null for unlimited
    }
}

```

## Search Functionality

The Table component includes powerful search capabilities that work across regular fields and meta fields.

### Basic Search Configuration

```php
class Article extends Resource
{
    public function getSearchableFields()
    {
        return collect([
            'title',          // Regular field
            'content',        // Regular field
            'author_name',    // Related field
            'tags',          // Meta field
            'seo_keywords',  // Meta field
        ]);
    }
}
```

### Custom Search Logic

Implement complex search queries:

```php
public function modifySearch($query, $search)
{
    return $query->where(function($q) use ($search) {
        // Search in title and content
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('content', 'like', "%{$search}%");
        
        // Search in author relationship
        $q->orWhereHas('author', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
        
        // Search in tags
        $q->orWhereHas('tags', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    });
}
```

### Search Features

- **Debounced Input**: 300ms delay prevents excessive queries
- **Meta Field Support**: Automatically searches in meta fields
- **Relationship Search**: Search through related models
- **Highlighted Results**: Optional search term highlighting
- **Search Persistence**: Search terms persist during session

## Filtering System

The filtering system allows building complex queries with a visual interface.

### Filter Configuration

Each field type provides its own filter operators:

```php
// Text field filters
public function filterOptions()
{
    return [
        'contains' => 'Contains',
        'not_contains' => 'Does not contain',
        'equals' => 'Equals',
        'not_equals' => 'Does not equal',
        'starts_with' => 'Starts with',
        'ends_with' => 'Ends with',
        'is_empty' => 'Is empty',
        'is_not_empty' => 'Is not empty',
    ];
}

// Number field filters
public function filterOptions()
{
    return [
        'equals' => 'Equals',
        'not_equals' => 'Not equals',
        'greater_than' => 'Greater than',
        'less_than' => 'Less than',
        'greater_or_equal' => 'Greater or equal',
        'less_or_equal' => 'Less or equal',
        'between' => 'Between',
        'is_empty' => 'Is empty',
        'is_not_empty' => 'Is not empty',
    ];
}
```

### Custom Filters

Users can create custom filters with:

1. **Simple Filters**: Single condition
2. **Filter Groups**: Multiple conditions with AND/OR logic
3. **Nested Groups**: Complex nested logic

```php
// Example filter structure
$filters = [
    'custom' => [
        [
            'name' => 'status',
            'operator' => 'equals',
            'value' => 'published',
            'main_operator' => 'and',
        ],
        [
            'filters' => [ // Filter group
                [
                    'name' => 'views_count',
                    'operator' => 'greater_than',
                    'value' => 1000,
                ],
                [
                    'name' => 'comments_count',
                    'operator' => 'greater_than',
                    'value' => 10,
                ],
            ],
            'main_operator' => 'or',
        ],
    ],
];
```

### Saved Filters

Filters can be saved for reuse:

```php
// Save filter for user
auth()->user()->updateOption('posts.filters.popular', [
    'name' => 'Popular Posts',
    'icon' => 'star',
    'custom' => [...], // Filter configuration
]);

// Save filter for team
auth()->user()->currentTeam->updateOption('posts.filters.draft', [
    'name' => 'Team Drafts',
    'global' => true,
    'custom' => [...],
]);
```

### Filter Groups

Create complex queries with filter groups:

```blade
{{-- Filter UI shows groups with visual nesting --}}
AND (
    status = 'published'
    OR (
        views > 1000
        AND comments > 10
    )
)
```

## Sorting & Ordering

The Table component supports multi-column sorting with custom sort methods.

### Basic Sorting

```php
class Article extends Resource
{
    // Default sort configuration
    public function defaultTableSort(): string
    {
        return 'published_at';
    }
    
    public function defaultTableSortDirection(): string
    {
        return 'desc'; // 'asc' or 'desc'
    }
    
    // Define sortable columns
    public function getTableHeaders()
    {
        return [
            'title' => ['label' => 'Title', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'published_at' => ['label' => 'Date', 'sortable' => true],
            'author.name' => ['label' => 'Author', 'sortable' => false],
        ];
    }
}
```

### Custom Sort Methods

Create custom sorting logic for complex scenarios:

```php
// Sort by popularity (views + comments)
public function sort_popularity($query, $direction)
{
    return $query->withCount(['views', 'comments'])
        ->orderByRaw('(views_count + comments_count) ' . $direction);
}

// Sort by author name (relationship)
public function sort_author($query, $direction)
{
    return $query->join('users', 'posts.user_id', '=', 'users.id')
        ->orderBy('users.name', $direction)
        ->select('posts.*');
}

// Sort by meta field
public function sort_priority($query, $direction)
{
    return $query->leftJoin('meta', function($join) {
            $join->on('posts.id', '=', 'meta.metable_id')
                 ->where('meta.metable_type', '=', Post::class)
                 ->where('meta.key', '=', 'priority');
        })
        ->orderBy('meta.value', $direction)
        ->select('posts.*');
}
```

### Secondary Sorting

```php
public function applySorting($query)
{
    // Primary sort
    $query = $query->orderBy($this->sortField, $this->sortDirection);
    
    // Always add secondary sort for consistency
    $query = $query->orderBy('id', 'desc');
    
    return $query;
}
```

## Selection & Bulk Actions

The Table component provides sophisticated selection handling and bulk operations.

### Selection Features

- **Single Selection**: Click checkbox or row (configurable)
- **Range Selection**: Shift+Click for selecting ranges
- **Select All on Page**: Checkbox in header
- **Select All Across Pages**: Option when all visible rows selected
- **Maximum Selection Limit**: Prevent selecting too many items

### Bulk Actions Configuration

```php
class Article extends Resource
{
    public function getBulkActions()
    {
        return [
            // Simple action
            'delete' => 'Delete Selected',
            
            // Action with modal
            'publish' => [
                'label' => 'Publish',
                'modal' => 'publish-modal',
                'confirm' => 'Publish selected articles?',
            ],
            
            // Action on collection
            'export' => [
                'label' => 'Export to CSV',
                'method' => 'collection',
            ],
            
            // Conditional action
            'archive' => [
                'label' => 'Archive',
                'show' => fn() => auth()->user()->can('archive-articles'),
            ],
            
            // Action with custom handler
            'assign_category' => [
                'label' => 'Assign Category',
                'modal' => 'assign-category-modal',
                'data' => ['categories' => Category::all()],
            ],
        ];
    }
    
    // Handle bulk action on each item
    public function publish()
    {
        $this->update(['status' => 'published', 'published_at' => now()]);
        
        event(new ArticlePublished($this));
    }
    
    // Handle bulk action on collection
    public function export($ids)
    {
        $articles = static::whereIn('id', $ids)->get();
        
        return Excel::download(
            new ArticlesExport($articles), 
            'articles.csv'
        );
    }
}
```

### Selection Limits

```php
// In your resource
public function maxTableSelection(): ?int
{
    return 100; // Maximum items that can be selected
}

// For relationship fields
$field = [
    'type' => 'HasMany',
    'max' => 5, // Maximum selections
];
```

### JavaScript Selection Logic

```javascript
// The table uses Alpine.js for selection handling
x-data="{
    selected: @entangle('selected'),
    selectPage: false,
    selectAll: @entangle('selectAll'),
    
    toggleRow(event, id) {
        if (event.shiftKey && this.lastSelectedId !== null) {
            // Range selection logic
            this.selectRange(this.lastSelectedId, id);
        } else {
            // Toggle single selection
            this.toggleSingle(id);
        }
        this.lastSelectedId = id;
    }
}"
```
## Column Management

The Table component provides drag-and-drop column management with persistent preferences.

### Column Configuration

```php
class Article extends Resource
{
    public function getTableHeaders()
    {
        return [
            'title' => [
                'label' => 'Title',
                'sortable' => true,
                'searchable' => true,
                'visible' => true,
                'width' => '40%',
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'component' => 'status-badge', // Custom component
            ],
            'author.name' => [
                'label' => 'Author',
                'sortable' => false,
                'relationship' => true,
            ],
            'published_at' => [
                'label' => 'Published',
                'sortable' => true,
                'format' => 'date:M d, Y',
            ],
            'actions' => [
                'label' => '',
                'sortable' => false,
                'exportable' => false,
            ],
        ];
    }
    
    // Default visible columns
    public function getDefaultColumns()
    {
        return ['title', 'status', 'author.name', 'published_at'];
    }
}
```

### Column Features

- **Drag & Drop Reordering**: Click and drag column headers
- **Show/Hide Columns**: Toggle visibility from settings menu
- **Persistent Preferences**: Column order and visibility saved per user
- **Team Preferences**: Optional team-wide column settings
- **Column Width**: Configurable column widths

### Column Preferences

```php
// User preferences stored as
auth()->user()->updateOption('columns.articles', [
    'title' => ['visible' => true, 'order' => 0],
    'status' => ['visible' => true, 'order' => 1],
    'author.name' => ['visible' => false, 'order' => 2],
]);

// Global preferences (admin-defined)
Aura::updateOption('columns.articles.global', [
    'title' => ['visible' => true, 'locked' => true],
    'status' => ['visible' => true, 'locked' => false],
]);
```

## Pagination

Advanced pagination with configurable options and persistence.

### Pagination Configuration

```php
class Article extends Resource
{
    public function defaultPerPage(): int
    {
        return 25;
    }
    
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100, 250];
    }
    
    public function maxPerPage(): int
    {
        return 250; // Prevent performance issues
    }
    
    // For infinite scroll
    public function supportsPagination(): bool
    {
        return true; // Set false for infinite scroll
    }
}
```

### Pagination Features

- **Dynamic Per-Page**: Users can change items per page
- **Persistent Settings**: Per-page preference saved per resource
- **Smart Loading**: Only loads visible items
- **Page Information**: Shows "1-25 of 248 results"
- **Keyboard Navigation**: Use arrow keys to navigate pages

## User Preferences

The Table component automatically saves user preferences for a personalized experience.

### Saved Preferences

| Preference | Scope | Storage Key |
|------------|-------|-------------|
| View Mode | Resource | `table_view.{resource}` |
| Columns | Resource | `columns.{resource}` |
| Per Page | Resource | `per_page.{resource}` |
| Sort Field | Resource | `sort.{resource}.field` |
| Sort Direction | Resource | `sort.{resource}.direction` |
| Filters | Resource | `{resource}.filters.*` |

### Managing Preferences

```php
// Get user preference
$viewMode = auth()->user()->getOption('table_view.articles', 'list');

// Set user preference
auth()->user()->updateOption('table_view.articles', 'grid');

// Clear preferences
auth()->user()->deleteOption('columns.articles');

// Clear all table preferences
auth()->user()->clearCachedOption('table_view.*');
auth()->user()->clearCachedOption('columns.*');
```

## Performance Optimization

The Table component is optimized for large datasets.

### Query Optimization

```php
class Article extends Resource
{
    // Eager load relationships
    public function indexQuery($query, $table)
    {
        return $query->with(['author', 'category', 'tags'])
            ->withCount(['comments', 'views']);
    }
    
    // Optimize meta loading
    public function eagerLoadMeta(): bool
    {
        return true; // Loads all meta in one query
    }
    
    // Limit query scope
    public function scopeForTable($query)
    {
        // Only load necessary data
        return $query->select('id', 'title', 'status', 'user_id', 'published_at');
    }
}
```

### Lazy Loading

```php
// Table loads on demand
<div wire:init="loadTable">
    <livewire:aura::table :model="Article::class" />
</div>

// In resource
public function lazyLoadTable(): bool
{
    return true; // Defer loading until visible
}
```

### Caching Strategies

```php
// Cache expensive operations
public function getCachedFilterOptions($field)
{
    return Cache::remember(
        "filter_options.{$this->getType()}.{$field}", 
        3600, 
        fn() => $this->getFilterOptions($field)
    );
}

// Cache query results
public function cacheTableQuery(): bool
{
    return true; // Caches results for 60 seconds
}
```

## Advanced Customization

### Custom Table Views

Create completely custom table layouts while maintaining all functionality.

#### Custom List View

```blade
{{-- resources/views/articles/table-list.blade.php --}}
<div class="overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                @if($this->settings['selectable'])
                    <th class="w-4 p-4">
                        <input type="checkbox" 
                               wire:model="selectPage"
                               @change="selectCurrentPage">
                    </th>
                @endif
                
                <th class="px-6 py-3 text-left">
                    <button wire:click="sortBy('title')" class="flex items-center">
                        Title
                        @if($sortField === 'title')
                            <x-aura::icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} />
                        @endif
                    </button>
                </th>
                
                <th class="px-6 py-3">Featured Image</th>
                <th class="px-6 py-3">Stats</th>
                <th class="px-6 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($rows as $article)
                <tr class="hover:bg-gray-50">
                    @if($this->settings['selectable'])
                        <td class="w-4 p-4">
                            <input type="checkbox" 
                                   value="{{ $article->id }}"
                                   x-model="selected"
                                   @click="toggleRow($event, {{ $article->id }})">
                        </td>
                    @endif
                    
                    <td class="px-6 py-4">
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ $article->title }}
                            </div>
                            <div class="text-sm text-gray-500">
                                by {{ $article->author->name }} • {{ $article->published_at->format('M d, Y') }}
                            </div>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4">
                        @if($article->featured_image)
                            <img src="{{ $article->featured_image }}" 
                                 class="h-10 w-10 rounded-lg object-cover">
                        @endif
                    </td>
                    
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center space-x-4">
                            <span class="flex items-center">
                                <x-aura::icon.eye class="w-4 h-4 mr-1" />
                                {{ number_format($article->views_count) }}
                            </span>
                            <span class="flex items-center">
                                <x-aura::icon.chat class="w-4 h-4 mr-1" />
                                {{ $article->comments_count }}
                            </span>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4">
                        <x-aura::dropdown>
                            <x-slot name="trigger">
                                <x-aura::button.icon>
                                    <x-aura::icon.dots />
                                </x-aura::button.icon>
                            </x-slot>
                            
                            <x-aura::dropdown.item 
                                :href="route('articles.edit', $article)">
                                Edit
                            </x-aura::dropdown.item>
                            
                            <x-aura::dropdown.item 
                                wire:click="duplicate({{ $article->id }})">
                                Duplicate
                            </x-aura::dropdown.item>
                            
                            <x-aura::dropdown.item 
                                wire:click="delete({{ $article->id }})"
                                class="text-red-600">
                                Delete
                            </x-aura::dropdown.item>
                        </x-aura::dropdown>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="px-6 py-4">
        {{ $rows->links() }}
    </div>
</div>
```

#### Custom Grid View

```blade
{{-- resources/views/articles/table-grid.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($rows as $article)
        <div class="bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow"
             wire:key="grid-{{ $article->id }}">
            
            {{-- Selection checkbox --}}
            @if($this->settings['selectable'])
                <div class="absolute top-4 left-4 z-10">
                    <input type="checkbox" 
                           value="{{ $article->id }}"
                           x-model="selected"
                           class="rounded border-gray-300">
                </div>
            @endif
            
            {{-- Featured image --}}
            <div class="aspect-w-16 aspect-h-9">
                <img src="{{ $article->featured_image ?: '/placeholder.jpg' }}" 
                     class="object-cover rounded-t-lg">
            </div>
            
            {{-- Content --}}
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $article->title }}
                    </h3>
                    <x-aura::status-badge :status="$article->status" />
                </div>
                
                <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                    {{ $article->excerpt }}
                </p>
                
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <img src="{{ $article->author->avatar }}" 
                             class="w-6 h-6 rounded-full">
                        <span>{{ $article->author->name }}</span>
                    </div>
                    
                    <time class="text-sm text-gray-500">
                        {{ $article->published_at->format('M d') }}
                    </time>
                </div>
                
                {{-- Actions --}}
                <div class="mt-4 flex space-x-2">
                    <x-aura::button.secondary 
                        size="sm"
                        wire:click="edit({{ $article->id }})">
                        Edit
                    </x-aura::button.secondary>
                    
                    <x-aura::button.secondary 
                        size="sm"
                        :href="route('articles.show', $article)">
                        View
                    </x-aura::button.secondary>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Load more --}}
<div class="mt-8">
    {{ $rows->links() }}
</div>
```

### Custom Query Scopes

```php
class Article extends Resource 
{
    // Modify base query
    public function indexQuery($query, $table)
    {
        // Add role-based filtering
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id())
                  ->orWhereHas('collaborators', function($q) {
                      $q->where('user_id', auth()->id());
                  });
        }
        
        // Add default scopes
        $query->published()
              ->withTrashed()
              ->with(['author', 'category', 'tags']);
        
        return $query;
    }
    
    // Custom filter scopes
    public function scopePopular($query)
    {
        return $query->where('views_count', '>', 1000)
                     ->where('comments_count', '>', 10);
    }
    
    // Dynamic scopes from filters
    public function applyTableFilters($query, $filters)
    {
        foreach ($filters as $filter) {
            $method = 'filter' . Str::studly($filter['name']);
            
            if (method_exists($this, $method)) {
                $query = $this->$method($query, $filter['value']);
            }
        }
        
        return $query;
    }
}
```

### Custom Actions

```php
class Article extends Resource
{
    public function getTableActions()
    {
        return [
            'view' => [
                'label' => 'View',
                'icon' => 'eye',
                'url' => fn($model) => route('articles.show', $model),
                'target' => '_blank',
            ],
            
            'edit' => [
                'label' => 'Edit',
                'icon' => 'pencil',
                'can' => fn($model) => auth()->user()->can('update', $model),
            ],
            
            'publish' => [
                'label' => 'Publish',
                'icon' => 'check',
                'action' => 'publish',
                'confirm' => 'Are you sure you want to publish this article?',
                'show' => fn($model) => $model->status === 'draft',
            ],
            
            'delete' => [
                'label' => 'Delete',
                'icon' => 'trash',
                'action' => 'delete',
                'confirm' => true,
                'class' => 'text-red-600 hover:text-red-900',
            ],
        ];
    }
    
    // Handle custom actions
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // Trigger events, notifications, etc.
        event(new ArticlePublished($this));
        
        session()->flash('message', 'Article published successfully!');
    }
}
```

### Table Component Extension

Create a custom table component:

```php
<?php

namespace App\Livewire\Tables;

use Aura\Base\Livewire\Table\Table;

class ArticlesTable extends Table
{
    // Custom properties
    public $showStats = true;
    public $dateRange = '30';
    
    // Override default settings
    public function mount()
    {
        parent::mount();
        
        $this->settings['per_page'] = 50;
        $this->settings['default_view'] = 'grid';
    }
    
    // Add custom query modifications
    protected function query()
    {
        $query = parent::query();
        
        // Add date range filter
        if ($this->dateRange) {
            $query->where('created_at', '>=', 
                now()->subDays($this->dateRange)
            );
        }
        
        return $query;
    }
    
    // Custom computed properties
    public function getStatsProperty()
    {
        return [
            'total' => $this->query()->count(),
            'published' => $this->query()->published()->count(),
            'draft' => $this->query()->draft()->count(),
        ];
    }
    
    // Override render method
    public function render()
    {
        return view('livewire.tables.articles-table', [
            'rows' => $this->rows(),
            'stats' => $this->stats,
        ]);
    }
}
```

## Real-world Examples

### E-commerce Product Table

```php
class Product extends Resource
{
    public function getTableHeaders()
    {
        return [
            'image' => ['label' => '', 'sortable' => false],
            'name' => ['label' => 'Product', 'sortable' => true],
            'sku' => ['label' => 'SKU', 'sortable' => true],
            'price' => ['label' => 'Price', 'sortable' => true],
            'stock' => ['label' => 'Stock', 'sortable' => true],
            'status' => ['label' => 'Status', 'sortable' => true],
            'sales' => ['label' => 'Sales', 'sortable' => true],
        ];
    }
    
    public function getBulkActions()
    {
        return [
            'update_prices' => [
                'label' => 'Update Prices',
                'modal' => 'bulk-price-update',
            ],
            'update_stock' => [
                'label' => 'Update Stock',
                'modal' => 'bulk-stock-update',
            ],
            'export' => [
                'label' => 'Export to CSV',
                'method' => 'collection',
            ],
        ];
    }
    
    public function kanbanStatuses()
    {
        return [
            'draft' => ['label' => 'Draft', 'color' => 'gray'],
            'active' => ['label' => 'Active', 'color' => 'green'],
            'out_of_stock' => ['label' => 'Out of Stock', 'color' => 'red'],
            'discontinued' => ['label' => 'Discontinued', 'color' => 'yellow'],
        ];
    }
}
```

### CRM Contact Table

```php
class Contact extends Resource
{
    public function modifySearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhereHas('company', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }
    
    public function getTableFilters()
    {
        return [
            'status' => [
                'type' => 'select',
                'options' => Contact::getStatuses(),
            ],
            'source' => [
                'type' => 'select',
                'options' => Contact::getSources(),
            ],
            'assigned_to' => [
                'type' => 'user',
                'multiple' => true,
            ],
            'created_at' => [
                'type' => 'date_range',
            ],
            'value' => [
                'type' => 'number_range',
                'label' => 'Deal Value',
            ],
        ];
    }
}
```

## Troubleshooting

### Common Issues

**Table not loading:**
```php
// Check if model is properly registered
Aura::resources([
    Article::class,
]);

// Verify permissions
public function canViewAny()
{
    return true; // or your logic
}
```

**Filters not working:**
```php
// Ensure field slug matches filter name
public function getFields()
{
    return [
        [
            'slug' => 'status', // Must match filter
            'type' => 'Select',
            // ...
        ]
    ];
}
```

**Performance issues:**
```php
// Use query optimization
public function indexQuery($query)
{
    return $query
        ->select('id', 'title', 'status', 'user_id') // Only needed columns
        ->with(['author:id,name']) // Specific relation fields
        ->withCount(['comments', 'views']); // Count instead of loading
}
```

### Debugging Tips

```php
// Enable query logging
DB::enableQueryLog();
$table = Livewire::test(Table::class, ['model' => Article::class]);
dd(DB::getQueryLog());

// Debug current state
public function updatedSearch()
{
    logger('Search query', [
        'search' => $this->search,
        'filters' => $this->filters,
        'selected' => $this->selected,
    ]);
}
```

## Summary

The Table component is a powerful, flexible system that handles:

- **Multiple view modes** with seamless switching
- **Advanced filtering** with save/share capabilities  
- **Smart selection** with bulk operations
- **Performance optimization** for large datasets
- **Complete customization** while maintaining functionality
- **User preferences** for personalized experience

Whether you're building a simple content list or a complex data management interface, the Table component provides the tools and flexibility you need.

> 📹 **Video Placeholder**: Building a custom table view with advanced filtering and bulk actions

For more information on resources that work with tables, see the [Resources Documentation](resources.md).
