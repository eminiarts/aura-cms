# Creating Resources

Resources are the foundation of Aura CMS, representing different types of content and data structures in your application. This guide will walk you through creating and configuring resources effectively.

## Table of Contents

- [Creating Resources](#creating-resources-1)
- [Resource Configuration](#resource-configuration)
- [Defining Fields](#defining-fields)
- [Resource Properties](#resource-properties)
- [Advanced Configuration](#advanced-configuration)
- [Custom Methods](#custom-methods)

## Creating Resources

There are two ways to create resources in Aura CMS:

### 1. Using the Admin Interface

When in development mode and with the `features.create_resource` enabled in your [Aura configuration](configuration.md), you can create resources directly through the admin interface. This provides a user-friendly way to:

- Define basic resource properties
- Set up fields visually
- Configure display options
- Set up relationships

### 2. Using the CLI Command

For more control or when working in production environments, use the Aura CLI command:

```bash
php artisan aura:resource {name}
```

For example, to create a Project resource:

```bash
php artisan aura:resource Project
```

This generates a new resource class in your `app/Aura/Resources` directory:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg>...</svg>';
    }

    public static function getFields()
    {
        return [];
    }
}
```

#### Creating Resources with Custom Tables

If you want your resource to use a dedicated database table instead of the shared `posts` table, use the `--custom` flag:

```bash
php artisan aura:resource Project --custom
```

This generates a resource configured to use its own table:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static $customTable = true;

    protected $table = 'projects';

    public static function getWidgets(): array
    {
        return [];
    }

    public function getIcon()
    {
        return '<svg>...</svg>';
    }

    public static function getFields()
    {
        return [];
    }
}
```

When using custom tables, you need to create a migration for your table. See [Custom Tables](custom-tables.md) for more details.

## Resource Configuration

Each resource can be configured with various properties to control its behavior and appearance:

```php
class Project extends Resource
{
    // Basic Configuration
    public static string $type = 'Project';
    public static ?string $slug = 'project';
    protected static ?string $group = 'Content';
    protected static ?int $sort = 10;

    // Display Configuration
    public static $singularName = 'Project';
    public static $pluralName = 'Projects';

    // Feature Flags
    public static $createEnabled = true;
    public static $editEnabled = true;
    public static $viewEnabled = true;
    public static $globalSearch = true;
    public static $contextMenu = true;
    public static bool $indexViewEnabled = true;
    protected static bool $showInNavigation = true;

    // Database Configuration
    public static $customTable = false;
    public static bool $usesMeta = true;
    protected static bool $title = false;

    // Navigation
    protected static $dropdown = false; // Group under a dropdown menu (e.g., 'Users')

    // UI Options
    public static $showActionsAsButtons = false;

    // Searchable fields for global search
    protected static array $searchable = ['title', 'content'];

    // Custom icon (define via getIcon() method instead for SVG)
    protected static ?string $icon = null;
}
```

## Defining Fields

Fields define the data structure of your resource. Define them in the `getFields()` method. For a complete list of available fields and their options, see the [Fields documentation](fields.md).

```php
public static function getFields()
{
    return [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => true,
        ],
        [
            'name' => 'Description',
            'slug' => 'description',
            'type' => 'Aura\\Base\\Fields\\Textarea',
            'validation' => 'nullable',
            'on_forms' => true,
            'on_view' => true,
        ],
        [
            'name' => 'Status',
            'slug' => 'status',
            'type' => 'Aura\\Base\\Fields\\Status',
            'options' => [
                'active' => 'Active',
                'pending' => 'Pending',
                'completed' => 'Completed',
            ],
            'on_index' => true,
            'on_forms' => true,
        ],
    ];
}
```

## Resource Properties

### Basic Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `$type` | `string` | Unique identifier for the resource (used in `type` column) | Required |
| `$slug` | `?string` | URL-friendly identifier for routes | Required |
| `$group` | `?string` | Navigation group name | `'Resources'` |
| `$sort` | `?int` | Order in navigation (lower = higher) | `100` |

### Display Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `$singularName` | `?string` | Display name (singular) | Derived from slug |
| `$pluralName` | `?string` | Display name (plural) | Pluralized type |
| `$icon` | `?string` | Static icon (prefer `getIcon()` method) | `null` |
| `$showInNavigation` | `bool` | Show in sidebar navigation | `true` |
| `$dropdown` | `string\|false` | Group under dropdown (e.g., `'Users'`) | `false` |
| `$showActionsAsButtons` | `bool` | Display row actions as buttons | `false` |
| `$title` | `bool` | Resource uses title field from posts table | `false` |

### Feature Flags

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `$createEnabled` | `bool` | Allow creating new records | `true` |
| `$editEnabled` | `bool` | Allow editing records | `true` |
| `$viewEnabled` | `bool` | Allow viewing records | `true` |
| `$indexViewEnabled` | `bool` | Show index/list view | `true` |
| `$globalSearch` | `bool` | Include in global search | `true` |
| `$contextMenu` | `bool` | Show context menu on rows | `true` |

### Database Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `$customTable` | `bool` | Use a custom database table | `false` |
| `$usesMeta` | `bool` | Store field values in meta table | `true` |
| `$table` | `string` | Database table name | `'posts'` |
| `$searchable` | `array` | Fields to search in global search | `[]` |
| `$taxonomy` | `bool` | Resource is a taxonomy (like tags/categories) | `false` |

## Advanced Configuration

### Custom Actions

Define custom actions for your resource:

```php
public array $actions = [
    'publish' => [
        'label' => 'Publish',
        'icon-view' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>',
        'class' => 'hover:text-green-700 text-green-500 font-bold',
        'confirm' => true,
        'confirm-title' => 'Publish Project?',
    ],
];
```

### Bulk Actions

Define actions that can be performed on multiple resources:

```php
public array $bulkActions = [
    'deleteSelected' => 'Delete',
    'publishSelected' => [
        'label' => 'Publish',
        'modal' => 'publish-modal',
    ],
];
```

### Widgets

Add dashboard widgets for your resource:

```php
public static function getWidgets(): array
{
    return [
        [
            'name' => 'Total Projects',
            'slug' => 'total_projects',
            'type' => 'Aura\\Base\\Widgets\\ValueWidget',
            'method' => 'count',
            'cache' => 300,
            'style' => [
                'width' => '33.33',
            ],
        ],
    ];
}
```

## Custom Methods

### Icon

Define a custom SVG icon for your resource:

```php
public function getIcon()
{
    return '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>';
}
```

### Title

Customize how the resource title is displayed:

```php
public function title()
{
    return $this->name . " (#{$this->id})";
}
```

### Actions

Define custom actions for individual records:

```php
public function actions()
{
    return [
        'publish' => [
            'label' => 'Publish',
            'icon-view' => 'aura::components.actions.check',
            'class' => 'hover:text-green-700 text-green-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Publish Project?',
            'confirm-content' => 'Are you sure you want to publish this project?',
            'confirm-button' => 'Publish',
        ],
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Project?',
        ],
    ];
}

// Action handler method
public function publish()
{
    $this->update(['status' => 'published']);
    return redirect()->back();
}
```

### Index Query

Modify the default query for the index/list view:

```php
public function indexQuery($query)
{
    return $query->where('status', 'active')
                 ->orderBy('created_at', 'desc');
}
```

### Index Table Settings

Configure the table display options:

```php
public function indexTableSettings()
{
    return [
        'default_view' => 'table', // 'table' or 'grid'
        'views' => [
            'grid' => 'custom.table.grid',
        ],
    ];
}
```

### Custom Views

Override the default views for your resource:

```php
public function createView()
{
    return 'resources.project.create';
}

public function editView()
{
    return 'resources.project.edit';
}

public function viewView()
{
    return 'resources.project.view';
}

public function indexView()
{
    return 'resources.project.index';
}
```

### Custom Permissions

Define additional permissions specific to your resource:

```php
public function customPermissions()
{
    return [
        'publish' => 'Publish projects',
        'archive' => 'Archive projects',
    ];
}
```

### Widget Settings

Customize the date range options for widgets:

```php
public array $widgetSettings = [
    'default' => '30d',
    'options' => [
        '7d' => '7 Days',
        '30d' => '30 Days',
        '90d' => '90 Days',
        'all' => 'All Time',
    ],
];
```

## Complete Example

Here's a complete example of a well-configured resource:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static $singularName = 'Project';

    public static $pluralName = 'Projects';

    protected static ?string $group = 'Content';

    protected static ?int $sort = 10;

    public static $globalSearch = true;

    protected static array $searchable = ['title', 'description'];

    public array $actions = [
        'archive' => [
            'label' => 'Archive',
            'class' => 'text-gray-500 hover:text-gray-700',
            'confirm' => true,
            'confirm-title' => 'Archive Project?',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
        'archiveSelected' => 'Archive',
    ];

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>';
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Details',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-details',
                'global' => true,
            ],
            [
                'name' => 'Project Info',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-info',
                'style' => ['width' => '70'],
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'nullable',
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Sidebar',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-sidebar',
                'style' => ['width' => '30'],
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'archived' => 'Archived',
                ],
                'default' => 'draft',
                'on_index' => true,
                'on_forms' => true,
            ],
            [
                'name' => 'Due Date',
                'slug' => 'due_date',
                'type' => 'Aura\\Base\\Fields\\Date',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Total Projects',
                'slug' => 'total_projects',
                'type' => 'Aura\\Base\\Widgets\\ValueWidget',
                'method' => 'count',
                'cache' => 300,
                'style' => ['width' => '33.33'],
            ],
        ];
    }

    public function title()
    {
        return $this->title ?? "Project #{$this->id}";
    }

    public function archive()
    {
        $this->update(['status' => 'archived']);
        return redirect()->back();
    }
}
```

## See Also

- [Fields Documentation](fields.md) - Complete list of available field types
- [Custom Tables](custom-tables.md) - Using dedicated database tables
- [Resources Overview](resources.md) - General resource concepts
- [Widgets](widgets.md) - Creating and configuring widgets
