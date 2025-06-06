# Creating Resources

Resources are the foundation of Aura CMS, representing different types of content and data structures in your application. This guide will walk you through creating and configuring resources effectively.

*Video 1: Creating Your First Resource*

![Creating Your First Resource](placeholder-video.mp4)

## Table of Contents

- [Creating Resources](#creating-resources-1)
- [Resource Configuration](#resource-configuration)
- [Defining Fields](#defining-fields)
- [Resource Properties](#resource-properties)
- [Advanced Configuration](#advanced-configuration)

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

This command generates a new resource class in your `app/Aura/Resources` directory with a basic structure:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';
    public static ?string $slug = 'project';
    protected static ?string $group = 'Content';
}
```

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
    protected static ?string $name = 'Project';
    protected static ?string $pluralName = 'Projects';
    protected static ?string $icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" /></svg>';

    // Feature Flags
    public static $createEnabled = true;
    public static $editEnabled = true;
    public static $viewEnabled = true;
    public static $deleteEnabled = true;
    public static $globalSearch = true;

    // Database Configuration
    public static $customTable = false;
    public static $usesMeta = true;
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

| Property | Description | Default |
|----------|-------------|---------|
| `$type` | Unique identifier for the resource | Required |
| `$slug` | URL-friendly identifier | Required |
| `$group` | Navigation group name | 'Content' |
| `$sort` | Order in navigation | null |
| `$name` | Display name (singular) | Class name |
| `$pluralName` | Display name (plural) | Pluralized class name |
| `$icon` | SVG icon code for navigation | null |
| `$customTable` | Use custom database table | false |
| `$usesMeta` | Enable meta fields storage | true |

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
