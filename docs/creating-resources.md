# Creating Resources

Resources are the foundation of Aura CMS, representing different types of content and data structures in your application. This guide will walk you through creating and configuring resources effectively.

## Table of Contents

- [Creating Resources](#creating-resources-1)
- [Resource Configuration](#resource-configuration)
- [Defining Fields](#defining-fields)
- [Dynamic Field Providers](#dynamic-field-providers)
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

## Dynamic Field Providers

Keep a resource's `getFields()` method as its declarative base definition. Plugins or database-backed property catalogs can add context-aware fields through `Aura\Base\Contracts\FieldProvider`:

```php
<?php

namespace App\Aura\Fields;

use App\Aura\Resources\Contact;
use App\Models\ContactProperty;
use Aura\Base\Contracts\ContextualFieldProvider;
use Aura\Base\FieldProviderContext;

class ContactPropertyFieldProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => auth()->user()?->current_team_id];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return ContactProperty::query()
            ->where('team_id', $context->value('team_id'))
            ->max('version') ?? 0;
    }

    public function fields(FieldProviderContext $context): array
    {
        return ContactProperty::query()
            ->where('team_id', $context->value('team_id'))
            ->orderBy('position')
            ->get('definition')
            ->pluck('definition')
            ->all();
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ContactProperty::query()
            ->withoutGlobalScopes()
            ->get('definition')
            ->pluck('definition.slug')
            ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
```

Register provider class names during a service provider's `boot()` method. Provider objects are rejected because a mutable object cannot be safely reused by a long-running worker. Registration order does not affect output: lower priorities run first, then provider class name breaks non-conflicting ties.

```php
use App\Aura\Fields\ContactPropertyFieldProvider;
use App\Aura\Resources\Contact;
use Aura\Base\Facades\Aura;
use Aura\Base\FieldProviderMode;

Aura::registerFieldProvider(
    ContactPropertyFieldProvider::class,
    resources: [Contact::class],
    mode: FieldProviderMode::Append,
    priority: 100,
);
```

Non-context providers may target `Resource`/`BaseResource` classes. Their default `['*']` target means every Aura resource; it does not apply providers to field classes or other classes that happen to use `InputFields`.

Providers with a non-empty `cacheContext()` must implement `ContextualFieldProvider` and return the complete, context-independent union of their managed slugs from `managedFieldSlugs()`. The manifest is the security boundary that lets a model hydrated directly in an inactive context quarantine fields it has never observed as active. Contextual providers must explicitly target `Resource` subclasses; `BaseResource` and wildcard targets are rejected because `BaseResource` does not implement model-state isolation. The manifest query must therefore be independent of the active team/user context and should use a stable property catalog or equivalent source.

`Append` adds fields after the declarative list and rejects duplicate slugs. `Replace` replaces matching slugs in place; a higher priority wins when several providers replace the same slug. Equal-priority replacements are rejected as ambiguous, and replacing a missing slug fails explicitly.

The `cacheContext()` result is part of the cache identity and accepts valid UTF-8 string keys with finite scalar or null values. Nested values, objects, resources, closures, `NAN`, and infinite floats are rejected. Declare every dimension that can change output, such as `team_id`, locale, or `user_id`; a provider must not read user-dependent state without including that dimension. Keep `cacheContext()` cheap and side-effect free. Aura may call it while resolving a definition so it can detect a context switch, but resolves `cacheVersion()` and `fields()` only once per resource/context during a lifecycle.

After committing database-backed definition changes, increment `cacheVersion()` and request a version re-read:

```php
Aura::refreshFieldProviderVersions();
```

Unchanged versions reuse their version-keyed field output; changed versions query `fields()` once and invalidate every derived field/conditional cache. Use `Aura::flushFieldCache()` for an unconditional reset, such as after changing provider registrations or when a source cannot expose a reliable version.

A reset covers every active `InputFields` consumer, including resource and field classes, parsed trees, container bindings, conditional decisions, and existing `Resource` instances on their next field-related access. On a `Resource` instance, when a context switch removes a managed field, Aura moves its attributes, nested field state, casts, loaded relations, and pending meta values into runtime-only quarantine. The supported isolation boundary is the tested `Resource` instance API: attribute and relation access, array/JSON/native PHP serialization, and instance persistence methods such as `save()`, `updateQuietly()`, `touch()`, `push()` / `pushQuietly()`, guarded increment/decrement, and instance-forwarded insert/upsert operations. Within that boundary, inactive state is neither exposed nor persisted. Switching the same live model back restores the pending state exactly; persisted database rows are not deleted. A model serialized while inactive intentionally omits quarantined values, while an active-context serialize/unserialize round trip retains them.

This is model-instance isolation, not a database policy. Mass writes started from an Eloquent builder, such as `Contact::newQuery()->update(...)`, raw query-builder or `DB::table(...)` writes, database triggers, and external writers bypass hydrated `Resource` model state. Aura cannot intercept those paths at runtime. They must not target context-managed slugs unless the caller applies equivalent tenant/user authorization in SQL. Prefer hydrating authorized `Resource` records and using the guarded instance methods. Intentional bulk operations should apply the same context authorization in the query and explicitly exclude context-managed columns, ideally inside a reviewed service or command. Calls forwarded from a `Resource` instance, such as `$contact->upsert(...)`, are guarded; calls started from `Contact::newQuery()` are not.

Aura resets provider instances and process-static state before and after queue jobs and at Octane request/task/tick boundaries. Providers registered during application boot are restored for the next lifecycle, while transient runtime registrations are discarded. Register or invalidate providers only during application boot or another quiescent lifecycle boundary. Standard process-isolated Octane workers are supported; custom overlapping coroutines that share one Laravel container and mutate Aura's static registries are not supported and must use isolated application containers or processes.

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
        'ability' => 'update',
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
    'deleteSelected' => [
        'label' => 'Delete',
        'ability' => 'delete',
    ],
    'publishSelected' => [
        'label' => 'Publish',
        'ability' => 'update',
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
            'ability' => 'update',
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
            'ability' => 'update',
            'class' => 'text-gray-500 hover:text-gray-700',
            'confirm' => true,
            'confirm-title' => 'Archive Project?',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => [
            'label' => 'Delete',
            'ability' => 'delete',
        ],
        'archiveSelected' => [
            'label' => 'Archive',
            'ability' => 'update',
        ],
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
