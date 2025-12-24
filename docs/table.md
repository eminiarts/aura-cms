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
    use BulkActions;       // Handles bulk operations on selected rows
    use Filters;           // Complex filtering system with save/load
    use Kanban;            // Kanban board functionality
    use PerPagePagination; // Custom pagination handling with session persistence
    use QueryFilters;      // Query building for filters (table/meta fields)
    use Search;            // Search functionality with meta field support
    use Select;            // Row selection logic (single, page, all)
    use Settings;          // Table configuration and defaults
    use Sorting;           // Multi-column sorting with custom sort methods
    use SwitchView;        // View mode switching with user preference persistence
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
| `$selectPage` | bool | Whether all rows on current page are selected |
| `$selectAll` | bool | Whether all rows across pages are selected |
| `$filters` | array | Active filter configuration with `custom` key |
| `$selectedFilter` | string | Currently selected saved filter slug |
| `$search` | string | Current search query |
| `$sorts` | array | Active sort columns with directions (multi-column) |
| `$perPage` | int | Items per page |
| `$loaded` | bool | Whether table has been loaded (for lazy loading) |

## Basic Usage

### Resource Configuration

Configure your resource for optimal table display using the `InteractsWithTable` trait (included in `Resource`):

```php
<?php

namespace App\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';
    protected static ?string $slug = 'article';
    
    // Define default table view mode
    public function defaultTableView()
    {
        return 'list'; // 'list', 'grid', or 'kanban'
    }
    
    // Set default items per page (default: 10)
    public function defaultPerPage()
    {
        return 10;
    }
    
    // Define default sort column (default: 'id')
    public function defaultTableSort()
    {
        return 'published_at';
    }
    
    // Define default sort direction (default: 'desc')
    public function defaultTableSortDirection()
    {
        return 'desc';
    }
    
    // Show/hide table settings dropdown (default: true)
    public function showTableSettings()
    {
        return true;
    }
    
    // Define fields - table headers are auto-generated from fields with on_index
    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'on_index' => true,  // Show in table
                'searchable' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Select',
                'name' => 'Status',
                'slug' => 'status',
                'on_index' => true,
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft'],
                    ['key' => 'published', 'value' => 'Published'],
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Date',
                'name' => 'Published At',
                'slug' => 'published_at',
                'on_index' => true,
            ],
        ];
    }
}
```

> **Note**: Table headers are automatically generated from your fields where `on_index` is `true` (default). The `getTableHeaders()` method in `InputFieldsTable` trait handles this automatically.

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
// In your resource (from InteractsWithTable trait)
public function tableView()
{
    return 'aura::components.table.list-view'; // Default view
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
// Enable grid view by returning a view path (returns false by default)
public function tableGridView()
{
    return 'aura::components.table.grid';
}
```

> **Note**: By default, `tableGridView()` returns `false`, disabling the grid view. Return a view path to enable it.

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
// Enable kanban view by returning a view path (returns false by default)
public function tableKanbanView()
{
    return 'aura::components.table.kanban-view';
}

// Customize the kanban query (optional)
public function kanbanQuery($query)
{
    // Return false to use default query, or modify and return $query
    return $query->orderBy('order', 'asc');
}

// Optional: Set custom pagination for kanban
public function kanbanPagination()
{
    return 50; // Items per column
}
```

The Kanban view automatically uses the `status` field's options to create columns. Each option should have `key`, `value`, and `color` properties:

```php
// In your getFields() method
[
    'type' => 'Aura\\Base\\Fields\\Select',
    'name' => 'Status',
    'slug' => 'status',
    'options' => [
        ['key' => 'todo', 'value' => 'To Do', 'color' => 'gray'],
        ['key' => 'in_progress', 'value' => 'In Progress', 'color' => 'blue'],
        ['key' => 'review', 'value' => 'Review', 'color' => 'yellow'],
        ['key' => 'done', 'value' => 'Done', 'color' => 'green'],
    ],
]
```

**Features:**
- Drag-and-drop between columns
- Status-based grouping
- Visual workflow
- Quick status updates
- Card limits per column

**Best for:** Project management, content workflows, task tracking

### Switching Views

Users can switch between views using the view selector. The available views depend on what methods return valid view paths:

```php
// Views are enabled by returning a view path:
public function tableView()        { return 'aura::components.table.list-view'; } // Always enabled
public function tableGridView()    { return false; } // Disabled by default
public function tableKanbanView()  { return false; } // Disabled by default

// User view preferences are stored automatically
// Key: 'table_view.{ResourceType}'
```

The `SwitchView` trait handles view switching and persists user preferences automatically.
```

## Configuration Options

The Table component offers extensive configuration through settings array:

### Complete Settings Reference

The `Settings` trait defines all available configuration options:

```php
$settings = [
    // Display settings
    'per_page' => 10,                    // Items per page (default from resource)
    'default_view' => 'list',            // Default view mode (from resource)
    'header' => true,                    // Show table header section
    'title' => true,                     // Show table title
    'create_url' => null,                // Custom create URL (null uses default)
    'create' => true,                    // Show create button
    
    // Feature toggles
    'search' => true,                    // Enable search
    'filters' => true,                   // Enable filtering
    'global_filters' => true,            // Enable global/team filters
    'selectable' => true,                // Enable row selection
    'bulk_actions' => true,              // Enable bulk actions
    'actions' => true,                   // Enable row actions
    'settings' => true,                  // Enable settings dropdown
    'sort_columns' => true,              // Enable column reordering
    
    // Modal options
    'edit_in_modal' => false,            // Open edit in modal
    'create_in_modal' => false,          // Open create in modal
    'view_in_modal' => false,            // Open view in modal
    
    // Column configuration
    'columns' => [],                     // Column definitions (from getTableHeaders)
    'columns_user_key' => 'columns.{Type}', // User preference key
    'columns_global_key' => false,       // Global settings key (false = disabled)
    
    // Header/footer slots
    'header_before' => true,             // Show header_before slot
    'header_after' => true,              // Show header_after slot
    'table_before' => true,              // Show table_before slot
    'table_after' => true,               // Show table_after slot
    
    // View customization
    'views' => [
        'table' => 'aura::components.table.index',
        'list' => 'aura::components.table.list-view',   // From tableView()
        'grid' => false,                                 // From tableGridView()
        'kanban' => false,                               // From tableKanbanView()
        'header' => 'aura::components.table.header',
        'row' => 'aura::components.table.row',          // From rowView()
        'filter' => 'aura::components.table.filter',
        'bulk_actions' => 'aura::components.table.bulk-actions',
        'table_header' => 'aura::components.table.table-header',
        'table_footer' => 'aura::components.table.footer',
        'filter_tabs' => 'aura::components.table.filter-tabs',
    ],
];
```

### Resource Method Configuration

Configure defaults in your resource class using methods from `InteractsWithTable` trait:

```php
class Article extends Resource
{
    // Default view mode: 'list', 'grid', or 'kanban'
    public function defaultTableView()
    {
        return 'list';
    }
    
    // Default items per page
    public function defaultPerPage()
    {
        return 10;
    }
    
    // Default sort column
    public function defaultTableSort()
    {
        return 'id';
    }
    
    // Default sort direction
    public function defaultTableSortDirection()
    {
        return 'desc';
    }
    
    // Show/hide settings dropdown
    public function showTableSettings()
    {
        return true;
    }
    
    // Custom table view
    public function tableView()
    {
        return 'aura::components.table.list-view';
    }
    
    // Enable grid view (return false to disable)
    public function tableGridView()
    {
        return false;
    }
    
    // Enable kanban view (return false to disable)
    public function tableKanbanView()
    {
        return false;
    }
    
    // Custom row view
    public function rowView()
    {
        return 'aura::components.table.row';
    }
    
    // Customize table settings via indexTableSettings()
    public function indexTableSettings()
    {
        return [
            'per_page' => 25,
            'selectable' => true,
            'bulk_actions' => true,
        ];
    }
}
```

## Search Functionality

The Table component includes powerful search capabilities that work across regular fields and meta fields.

### Basic Search Configuration

Mark fields as searchable in your field definitions:

```php
class Article extends Resource
{
    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'searchable' => true,  // Enable search for this field
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'name' => 'Content',
                'slug' => 'content',
                'searchable' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'SEO Keywords',
                'slug' => 'seo_keywords',
                'searchable' => true,  // Works for meta fields too
            ],
        ];
    }
}
```

The `getSearchableFields()` method in `Resource` automatically collects fields with `searchable => true`.

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

- **Debounced Input**: Prevents excessive queries during typing
- **Meta Field Support**: Automatically searches in meta fields using `whereExists` subquery
- **Custom Search Logic**: Override with `modifySearch($query, $search)` method
- **Relationship Search**: Use `modifySearch()` to add relationship searches
- **Automatic Reset**: Search resets pagination to page 1

## Filtering System

The filtering system allows building complex queries with a visual interface.

### Filter Configuration

Each field type provides its own filter operators via `filterOptions()`. The `QueryFilters` trait handles applying these filters to both regular table fields and meta fields.

**Available Filter Operators** (from `QueryFilters` trait):

```php
// Text/String operators
'contains'         => 'LIKE %value%'
'does_not_contain' => 'NOT LIKE %value%'
'starts_with'      => 'LIKE value%'
'ends_with'        => 'LIKE %value'
'is' / 'equals'    => '= value'
'is_not' / 'not_equals' => '!= value'
'is_empty'         => 'NULL OR empty string'
'is_not_empty'     => 'NOT NULL AND not empty'

// Number operators
'greater_than'           => '> value'
'less_than'              => '< value'
'greater_than_or_equal'  => '>= value'
'less_than_or_equal'     => '<= value'
'in'                     => 'IN (values)'
'not_in'                 => 'NOT IN (values)'

// Pattern operators
'like'      => 'LIKE value' (raw pattern)
'not_like'  => 'NOT LIKE value'
'regex'     => 'REGEXP value'
'not_regex' => 'NOT REGEXP value'

// Date operators
'date_is'           => 'DATE = value'
'date_is_not'       => 'DATE != value'
'date_before'       => 'DATE < value'
'date_after'        => 'DATE > value'
'date_on_or_before' => 'DATE <= value'
'date_on_or_after'  => 'DATE >= value'
'date_is_empty'     => 'NULL OR empty'
'date_is_not_empty' => 'NOT NULL AND not empty'
```

### Custom Filters

The `Filters` trait provides methods for building and managing custom filters:

```php
// Filter structure stored in $filters property
$filters = [
    'custom' => [
        // Filter groups - each group contains filters
        [
            'filters' => [
                [
                    'name' => 'status',           // Field slug
                    'operator' => 'equals',       // Filter operator
                    'value' => 'published',       // Filter value
                    'main_operator' => 'and',     // AND/OR with next filter
                    'options' => [],              // Field-specific options
                ],
                [
                    'name' => 'views_count',
                    'operator' => 'greater_than',
                    'value' => 1000,
                    'main_operator' => 'or',
                ],
            ],
            'operator' => 'and',  // Group operator (AND/OR with other groups)
        ],
    ],
];
```

**Available Methods:**

```php
// Add a new filter to current group
$this->addFilter();

// Add a new filter group
$this->addFilterGroup();

// Add a sub-filter within a group
$this->addSubFilter($groupKey);

// Remove a single filter
$this->removeCustomFilter($index);

// Remove a filter within a group
$this->removeFilter($groupKey, $filterKey);

// Remove entire filter group
$this->removeFilterGroup($groupKey);

// Reset all filters
$this->resetFilter();
```

### Saved Filters

Filters can be saved for reuse via the `saveFilter()` method:

```php
// Filter save structure
$this->filter = [
    'name' => 'Popular Posts',      // Required: filter name
    'public' => false,              // Whether publicly visible
    'global' => false,              // Save for team (true) or user (false)
    'icon' => 'star',               // Optional icon
];

// Save filter - calls saveFilter() method
// User filters: stored in '{ResourceType}.filters.{slug}'
// Team filters: stored in team's options (when global = true)

// Saved filter structure includes:
[
    'name' => 'Popular Posts',
    'slug' => 'popular-posts',      // Auto-generated from name
    'public' => false,
    'global' => false,
    'type' => 'user',               // or 'team'
    'custom' => [...],              // Filter configuration
]
```

**Accessing Saved Filters:**

```php
// Get all user and team filters (via userFilters computed property)
$filters = $this->userFilters;

// Select a saved filter
$this->selectedFilter = 'popular-posts';

// Delete a saved filter
$this->deleteFilter('popular-posts');
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

The Table component supports multi-column sorting with custom sort methods via the `Sorting` trait.

### Basic Sorting

```php
class Article extends Resource
{
    // Default sort configuration (from InteractsWithTable trait)
    public function defaultTableSort()
    {
        return 'published_at'; // Default: 'id'
    }
    
    public function defaultTableSortDirection()
    {
        return 'desc'; // Default: 'desc'
    }
}
```

**How Sorting Works:**

The `$sorts` property stores active sorts as an associative array:

```php
// Single column sort
$this->sorts = ['published_at' => 'desc'];

// Multi-column sort (when user clicks multiple columns)
$this->sorts = ['status' => 'asc', 'published_at' => 'desc'];
```

Clicking a column header cycles through: `asc` -> `desc` -> removed (default sort)

### Custom Sort Methods

Create custom sorting logic by defining `sort_{fieldname}` methods in your resource:

```php
class Article extends Resource
{
    // Custom sort method: sort_popularity
    // Called when user sorts by 'popularity' column
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
}
```

### Automatic Meta Field Sorting

The `Sorting` trait automatically handles sorting for meta fields:

```php
// For meta fields, the trait automatically:
// 1. Joins the meta table
// 2. Filters by key
// 3. Casts value appropriately (DECIMAL for numbers, CHAR for text)

// For taxonomy fields (Tags), it:
// 1. Joins post_relations table
// 2. Orders by MIN(resource_id)
```

### Default Sort Fallback

When no sorts are active, the default sort is applied:

```php
// Applied automatically when $this->sorts is empty
$query->orderBy(
    $this->model->getTable().'.'.$this->model->defaultTableSort(),
    $this->model->defaultTableSortDirection()
);
```

## Selection & Bulk Actions

The Table component provides sophisticated selection handling and bulk operations via the `Select` and `BulkActions` traits.

### Selection Features

The `Select` trait manages row selection:

- **`$selected`**: Array of selected row IDs
- **`$selectPage`**: Boolean - all rows on current page selected
- **`$selectAll`**: Boolean - all rows across all pages selected

**Selection Methods:**

```php
// Select all rows on current page
$this->selectPageRows();

// Select all rows across pages
$this->selectAll();

// Get query for selected rows
$this->selectedRowsQuery; // Computed property
```

### Bulk Actions Configuration

Define bulk actions in your resource using the `$bulkActions` property or `bulkActions()` method:

```php
class Article extends Resource
{
    // Simple array format
    public array $bulkActions = [
        'delete' => 'Delete Selected',
        'publish' => 'Publish Selected',
        'archive' => 'Archive Selected',
    ];
    
    // Or method format for dynamic actions
    public function bulkActions()
    {
        return [
            'delete' => 'Delete Selected',
            'publish' => 'Publish Selected',
            'export' => 'Export to CSV',
        ];
    }
    
    // Handle bulk action on each item
    // Method is called on each selected model
    public function publish()
    {
        $this->update(['status' => 'published', 'published_at' => now()]);
    }
    
    // Handle bulk action on collection
    // Method receives array of IDs
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

**Bulk Action Methods in Table Component:**

```php
// Execute action on each selected row
$this->bulkAction('publish');

// Execute action on collection (for exports, etc.)
$this->bulkCollectionAction('export');

// Open a modal for bulk action
$this->openBulkActionModal('assign_category', ['modal' => 'assign-category-modal']);
```

**Special Action Prefixes:**

- `callFlow.{flowId}` - Triggers a Flow on selected items
- `multiple{Action}` - Passes entire collection to method

### Selection Limits

When used with relationship fields, selection can be limited:

```php
// For relationship field tables
$field = [
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'slug' => 'comments',
    'max' => 5, // Maximum selections
];

// The Table component enforces this in updatedSelected():
if (optional($this->field)['max'] && count($this->selected) > $this->field['max']) {
    $this->selected = array_slice($this->selected, 0, $this->field['max']);
    $this->notify('You can only select '.$this->field['max'].' items.', 'error');
}
```

### Selection Events

The Table component dispatches events for selection changes:

```php
// Dispatched when selection changes
$this->dispatch('selectedRows', $this->selected);

// Dispatched when row IDs are updated
$this->dispatch('rowIdsUpdated', $rowIds);

// Listen for external selection updates
#[On('selectFieldRows')]
public function selectFieldRows($value, $slug) { ... }
```
## Column Management

The Table component provides drag-and-drop column management with persistent preferences.

### Column Configuration

Table headers are automatically generated from your fields via `InputFieldsTable` trait:

```php
class Article extends Resource
{
    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',           // Used as column header
                'slug' => 'title',           // Used as column key
                'on_index' => true,          // Show in table (default: true)
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Select',
                'name' => 'Status',
                'slug' => 'status',
                'on_index' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Date',
                'name' => 'Published At',
                'slug' => 'published_at',
                'on_index' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'name' => 'Content',
                'slug' => 'content',
                'on_index' => false,  // Hidden from table
            ],
        ];
    }
}
```

**Methods from `InputFieldsTable` trait:**

```php
// Get table headers as Collection: ['slug' => 'Name']
$this->getTableHeaders();

// Get columns array: ['slug' => 'Name', ...]
$this->getColumns();

// Get default columns: ['slug' => true, ...]
$this->getDefaultColumns();

// Check if field should show on index
$this->isFieldOnIndex($slug);
```

### Column Features

- **Drag & Drop Reordering**: Reorder columns via settings
- **Show/Hide Columns**: Toggle visibility via `$columns` property
- **Persistent Preferences**: Column order saved per user
- **Global Preferences**: Optional team-wide column settings

### Column Preferences

```php
// User column preferences stored in:
// Key: 'columns.{ResourceType}'
auth()->user()->updateOption('columns.Article', [
    'title' => true,
    'status' => true,
    'published_at' => false,  // Hidden
]);

// User column sort order stored in:
// Key: 'columns_sort.{ResourceType}'
auth()->user()->updateOption('columns_sort.Article', ['status', 'title', 'published_at']);

// Global column settings (when columns_global_key is set)
Aura::updateOption('columns.articles.global', [
    'title' => true,
    'status' => true,
]);
```

**Reordering Columns:**

```php
// Called when user reorders columns
$this->reorder(['status', 'title', 'published_at']);

// Saves to user options or global options depending on settings
```

## Pagination

The `PerPagePagination` trait provides pagination with session persistence.

### Pagination Configuration

```php
class Article extends Resource
{
    // Default items per page (default: 10)
    public function defaultPerPage()
    {
        return 25;
    }
}
```

**Pagination Properties:**

```php
// Current items per page
public $perPage = 10;

// Uses Livewire's WithPagination trait
use WithPagination;
```

### Pagination Features

- **Dynamic Per-Page**: Users can change items per page
- **Session Persistence**: Per-page preference saved in session (`perPage` key)
- **Settings Override**: Can be overridden via `$settings['per_page']`
- **Livewire Integration**: Uses standard Livewire pagination

**How Per-Page is Determined (priority order):**

1. Session value (`session('perPage')`)
2. Settings value (`$settings['per_page']`)
3. Resource default (`$model->defaultPerPage()`)

```php
// Change items per page
$this->perPage = 50;

// Automatically saved to session via updatedPerPage()
```

## User Preferences

The Table component automatically saves user preferences for a personalized experience.

### Saved Preferences

| Preference | Storage Key | Saved By |
|------------|-------------|----------|
| View Mode | `table_view.{ResourceType}` | SwitchView trait |
| Columns Visibility | `columns.{ResourceType}` | Table component |
| Columns Order | `columns_sort.{ResourceType}` | Table component |
| Per Page | Session: `perPage` | PerPagePagination trait |
| User Filters | `{ResourceType}.filters.{slug}` | Filters trait |
| Team Filters | Team option: `{ResourceType}.filters.{slug}` | Filters trait |
| Kanban Statuses | `kanban_statuses.{ResourceType}` | Kanban trait |

### Managing Preferences

```php
// Get user preference
$viewMode = auth()->user()->getOption('table_view.Article', 'list');

// Set user preference
auth()->user()->updateOption('table_view.Article', 'grid');

// Delete preference
auth()->user()->deleteOption('columns.Article');

// Clear cached options (for filters)
auth()->user()->clearCachedOption('Article.filters.*');

// Team preferences (for global filters)
auth()->user()->currentTeam->getOption('Article.filters.*');
auth()->user()->currentTeam->updateOption('Article.filters.published', [...]);
```

## Performance Optimization

The Table component is optimized for large datasets.

### Query Optimization

```php
class Article extends Resource
{
    // Customize the index query
    // This is called in Table::query() method
    public function indexQuery($query, $table)
    {
        return $query->with(['author', 'category', 'tags'])
            ->withCount(['comments', 'views']);
    }
}
```

**Automatic Optimizations:**

```php
// Meta is automatically eager loaded when usesMeta() is true
if ($this->model->usesMeta()) {
    $query = $query->with(['meta']);
}

// Default ordering is applied
$query->orderBy($this->model->getTable().'.id', 'desc');
```

### Lazy Loading

```php
// Table supports lazy loading via $loaded property
<div wire:init="loadTable">
    <livewire:aura::table :model="Article::class" />
</div>

// In Table component
public $loaded = false;

public function loadTable()
{
    $this->loaded = true;
}
```

### Query Flow

The table query is built in this order:

1. **Base Query**: `$this->model->query()`
2. **Index Query**: `$this->model->indexQuery($query, $this)` (if exists)
3. **Field Query**: `$field->queryFor($query, $this)` (for relationship tables)
4. **Dynamic Query**: `$this->query` string executed via DynamicFunctions
5. **Kanban Query**: `$this->applyKanbanQuery($query)` (if kanban view)
6. **Meta Eager Load**: `$query->with(['meta'])` (if usesMeta)
7. **Custom Filters**: `$this->applyCustomFilter($query)`
8. **Search**: `$this->applySearch($query)`
9. **Sorting**: `$this->applySorting($query)`
10. **Pagination**: `$query->paginate($this->perPage)`

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

Create a custom table component by extending the base Table class:

```php
<?php

namespace App\Livewire\Tables;

use Aura\Base\Livewire\Table\Table;

class ArticlesTable extends Table
{
    // Custom properties
    public $showStats = true;
    public $dateRange = '30';
    
    // Override mount to customize initial settings
    public function mount()
    {
        // Call parent mount first (handles settings, pagination, view, kanban)
        parent::mount();
        
        // Then customize
        $this->settings['per_page'] = 50;
    }
    
    // Override query() to add custom modifications
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
    
    // Add custom computed properties
    #[Computed]
    public function stats()
    {
        $baseQuery = $this->model->query();
        return [
            'total' => $baseQuery->count(),
            'published' => $baseQuery->clone()->where('status', 'published')->count(),
            'draft' => $baseQuery->clone()->where('status', 'draft')->count(),
        ];
    }
    
    // Override render method
    public function render()
    {
        return view('livewire.tables.articles-table', [
            'parent' => $this->parent,
            'rows' => $this->rows(),
            'rowIds' => $this->rowIds,
            'stats' => $this->stats,
        ]);
    }
}
```

**Key Methods to Override:**

```php
// Query building
protected function query()      // Base query with scopes
protected function rows()       // Final paginated results
public function rowsQuery()     // Query with filters, search, sorting

// Lifecycle
public function mount()         // Component initialization
public function render()        // View rendering

// Actions
public function action($data)   // Handle row actions
public function bulkAction($action) // Handle bulk actions
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

// Ensure tableComponentView returns correct view
public function tableComponentView()
{
    return 'aura::livewire.table'; // Default
}
```

**Filters not working:**
```php
// Ensure field slug matches filter name
public static function getFields()
{
    return [
        [
            'slug' => 'status', // Must match filter field name
            'type' => 'Aura\\Base\\Fields\\Select',
            'on_index' => true,
            // ...
        ]
    ];
}

// Check if field uses meta or table storage
// Meta fields use whereHas('meta', ...) queries
// Table fields use direct where() queries
```

**Search not finding results:**
```php
// Ensure fields are marked as searchable
[
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'searchable' => true,  // Required for search
]

// Or use custom search logic
public function modifySearch($query, $search)
{
    return $query->where('title', 'like', "%{$search}%");
}
```

**Sorting not working on meta fields:**
```php
// Meta field sorting requires the field to be recognized
// Check if isMetaField() returns true for your field
$this->model->isMetaField('your_field'); // Should return true

// Custom sort for complex scenarios
public function sort_your_field($query, $direction)
{
    return $query->orderBy('your_field', $direction);
}
```

**Performance issues:**
```php
// Use indexQuery for eager loading
public function indexQuery($query, $table)
{
    return $query
        ->select(['id', 'title', 'status', 'user_id', 'created_at'])
        ->with(['author:id,name'])
        ->withCount(['comments']);
}
```

### Debugging Tips

```php
// Enable query logging
DB::enableQueryLog();

// Test table component
Livewire::test(Table::class, ['model' => Article::class])
    ->set('search', 'test')
    ->assertSee('Expected Result');

dd(DB::getQueryLog());

// Debug filter application (note: ray() calls exist in QueryFilters)
// Check the applyFilterGroup method for ray() debugging

// Inspect current table state
ray([
    'search' => $this->search,
    'filters' => $this->filters,
    'sorts' => $this->sorts,
    'selected' => $this->selected,
    'perPage' => $this->perPage,
]);
```

### Events Reference

```php
// Table dispatches these events:
'tableMounted'       // After mount()
'refreshTable'       // Triggers table refresh
'refreshTableSelected' // Clears selection
'selectedRows'       // When selection changes
'rowIdsUpdated'      // When row IDs change
'selectFieldRows'    // External selection update
'selectRowsRange'    // Range selection
```

## Summary

The Table component is a powerful, flexible system built with these traits:

| Trait | Purpose |
|-------|---------|
| `BulkActions` | Handle bulk operations on selected rows |
| `Filters` | Complex filtering with save/share capabilities |
| `Kanban` | Kanban board view with drag-and-drop |
| `PerPagePagination` | Pagination with session persistence |
| `QueryFilters` | Apply filters to table/meta fields |
| `Search` | Search with meta field support |
| `Select` | Row selection (single, page, all) |
| `Settings` | Table configuration and defaults |
| `Sorting` | Multi-column sorting with custom methods |
| `SwitchView` | View mode switching with preferences |

**Key Features:**

- **Multiple view modes**: List, Grid, Kanban with seamless switching
- **Advanced filtering**: AND/OR logic, save filters for user/team
- **Smart selection**: Single, page, all with bulk operations
- **Multi-column sorting**: With custom sort methods for relationships/meta
- **Automatic meta support**: Search, filter, sort meta fields
- **User preferences**: View mode, columns, filters persisted

**Resource Configuration Methods:**

```php
defaultPerPage()           // Default: 10
defaultTableSort()         // Default: 'id'
defaultTableSortDirection() // Default: 'desc'
defaultTableView()         // Default: 'list'
tableView()                // Default: 'aura::components.table.list-view'
tableGridView()            // Default: false (disabled)
tableKanbanView()          // Default: false (disabled)
showTableSettings()        // Default: true
indexQuery($query, $table) // Customize base query
modifySearch($query, $search) // Custom search logic
sort_{field}($query, $direction) // Custom sort methods
```

For more information on resources that work with tables, see the [Resources Documentation](resources.md).
