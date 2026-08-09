# Global Search

Global Search in Aura CMS provides a powerful and intuitive way to search across all your resources from anywhere in the admin interface. This feature helps users quickly find content across different resource types.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Usage](#usage)
- [Searchable Fields](#searchable-fields)
- [Search Results](#search-results)
- [Keyboard Shortcuts](#keyboard-shortcuts)
- [Customization](#customization)
- [Best Practices](#best-practices)

## Overview

Global Search is a Livewire component (`Aura\Base\Livewire\GlobalSearch`) that allows users to:
- Search across multiple resource types simultaneously
- Search users by name or email
- Access recently visited pages (stored in browser localStorage)
- Use keyboard shortcuts for quick navigation
- View and access bookmarked pages (up to 9 with keyboard shortcuts)

## Configuration

### Enabling/Disabling Global Search

Global Search can be enabled or disabled in your `config/aura.php`:

```php
return [
    'features' => [
        'global_search' => true, // Set to false to disable
    ],
];
```

When disabled, the GlobalSearch component returns a 403 error and the search interface is not rendered.

### Search Bounds and Ranking

The default search budget is configured separately from the feature toggle:

```php
'global_search' => [
    'minimum_query_length' => 2,
    'maximum_query_length' => 64,
    'max_resources' => 25,
    'max_fields_per_resource' => 8,
    'per_resource_limit' => 5,
    'global_limit' => 15,
    'ranking' => [
        'exact' => 300,
        'prefix' => 200,
        'contains' => 100,
    ],
],
```

Each resource query is ranked and limited in SQL before records are hydrated. Aura searches at most the configured number of resources and fields, then applies the global result limit. Package hard caps keep these values bounded even when published configuration is accidentally set much higher: 100 resources, 32 fields per resource, 50 candidates per resource, 100 global results, and 256 query characters.

Ranking is deterministic. A result's score is the configured match-quality score plus its field weight. Equal scores use resource registration order and then the model key in ascending order. `%`, `_`, and Aura's `!` escape character are always treated as literal query input, not caller-controlled SQL wildcards.

### Resource-Level Configuration

Control whether a resource participates using the static `$globalSearch` property. The default remains `true` for backward compatibility, so internal or non-navigable resources should opt out explicitly:

```php
class Post extends Resource
{
    public static $globalSearch = true;
}

class InternalAuditLog extends Resource
{
    public static $globalSearch = false;
}
```

You can also access this setting programmatically:

```php
// Check if a resource is included in global search
$includeInSearch = Post::getGlobalSearch(); // Returns true or false
```

There is no class-name or slug denylist. Aura only searches registered `Resource` classes for which `getGlobalSearch()` returns `true`, the current user passes the resource's `viewAny` policy, and at least one explicit searchable field exists. Built-in internal resources opt out on their own resource definitions. Users use the same contract as every custom-table resource and remain searchable by name and email.

## Usage

### Accessing Global Search

There are multiple ways to access Global Search:

1. Click the search icon in the navigation bar
2. Use the keyboard shortcut `⌘ + K` (Mac) or `Ctrl + K` (Windows/Linux)
3. Press the `/` (forward slash) key anywhere in the interface
4. Click the search field in the admin interface

Note: The `/` and `⌘ + K` shortcuts are disabled when focus is on input fields or textareas to prevent interference with typing.

### Search Interface Features

The search interface provides:
- Real-time search results with 300ms debounce
- Resource type grouping
- Recently visited pages (stored in browser localStorage)
- Bookmarked pages with quick access shortcuts
- Keyboard navigation with arrow keys

## Searchable Fields

### Defining Searchable Fields

The preferred explicit contract is the resource's `$searchable` property. List slugs in ranking order or assign integer weights:

```php
class Post extends Resource
{
    protected static array $searchable = [
        'title' => 20,
        'content' => 10,
    ];
}
```

Every listed slug must exist in `getFields()`. Higher weights win within the same match quality. You may also put `global_search_weight` on a field definition when using an ordered slug list.

For backward compatibility, a resource with an empty `$searchable` property falls back to fields marked `searchable => true`:

Make fields searchable by adding the `searchable` property in your field definitions:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'searchable' => true,
            'on_index' => true,
        ],
        [
            'name' => 'Content',
            'slug' => 'content',
            'type' => 'Aura\\Base\\Fields\\Textarea',
            'searchable' => true,
        ],
        [
            'name' => 'Description',
            'slug' => 'description',
            'type' => 'Aura\\Base\\Fields\\Text',
            'searchable' => false,
        ]
    ];
}
```

### Getting Searchable Fields

You can retrieve the searchable fields for a resource programmatically:

```php
$resource = new Post();
$searchableFields = $resource->getGlobalSearchableFields();
```

`getGlobalSearchableFields()` is the global-search hook. Override it when a resource needs to derive its searchable field contract dynamically. `getSearchableFields()` remains the lower-level collection of field definitions carrying `searchable => true`.

### Meta Fields Support

Global Search supports both table-backed and meta-backed fields in the explicit contract. Meta values are matched with a bounded correlated subquery, without duplicating resource rows.

No `title` column is assumed. Resources that use `name`, a meta field, or another custom-table column can be searched normally, and the resource's existing `title()` method controls the displayed label.

### User Search

The built-in User resource declares these searchable fields:
- `name` field
- `email` field

## Search Results

### Result Structure

Search results are:
- Limited to 5 candidates per resource and 15 results globally by default
- Grouped by resource type after limiting
- Displayed with relevant icons and metadata
- Returned only when both `viewAny` and record-level `view` authorization pass
- Linked only when `globalSearchUrl()` returns a non-empty authorized destination

### Result Display

Each search result shows:
- Resource ID and title in format: `#123 Resource Title`
- Resource type label
- Resource icon (from `getIcon()` method)
- Direct link to view the resource

### Empty Results

When no matches are found, the interface displays "No results" message.

## Keyboard Shortcuts

Global Search supports keyboard navigation:

| Shortcut | Action |
|----------|--------|
| `⌘ + K` | Open search (Mac) |
| `Ctrl + K` | Open search (Windows/Linux) |
| `/` | Open search (all platforms) |
| `ESC` | Clear input first, then close search on second press |
| `↑` | Previous result |
| `↓` | Next result |
| `Enter` | Go to selected result |
| `⌘ + 1` through `⌘ + 9` | Quick access to bookmarks 1-9 |

Note: The `/` and `⌘ + K` shortcuts only work when not focused on an input field or textarea.

## Customization

### Resource Query and Destination Hooks

Apply resource-specific scopes without replacing the component:

```php
use Illuminate\Database\Eloquent\Builder;

public function newGlobalSearchQuery(): Builder
{
    return parent::newGlobalSearchQuery()
        ->where('status', 'published');
}
```

The normal Eloquent global scopes, including Aura's team scope, remain active. To use a supported non-standard view destination, override the URL hook:

```php
public function globalSearchUrl(): ?string
{
    return $this->indexUrl();
}
```

Aura still requires the current user to pass both `viewAny` for the resource and `view` for the returned record.

### Custom Search Component

You can customize the search behavior by extending the GlobalSearch component:

```php
use Aura\Base\Livewire\GlobalSearch;

class CustomGlobalSearch extends GlobalSearch
{
    public function getSearchResultsProperty(): \Illuminate\Support\Collection
    {
        // Custom search implementation
        // Must return a collection grouped by type
        
        if (! $this->search || $this->search === '') {
            return collect();
        }
        
        // Your custom search logic here
        $results = collect([]);
        
        // Limit and group results
        return $results->take(15)->groupBy('type');
    }
}
```

Then register your custom component in a service provider:

```php
use Livewire\Livewire;

Livewire::component('aura::global-search', CustomGlobalSearch::class);
```

### Custom Result Display

Customize how search results are displayed by publishing and modifying the view:

```bash
php artisan vendor:publish --tag=aura-views --force
```

Then modify `resources/views/vendor/aura/livewire/global-search.blade.php`

### Custom Resource Title

Override the `title()` method in your resource to customize what appears in search results:

```php
class Post extends Resource
{
    public function title()
    {
        return $this->name ?? "Post #{$this->id}";
    }
}
```

## Best Practices

1. **Performance**
   - Index searchable fields where the database can use an appropriate search index
   - Limit the number of searchable fields to essential ones
   - Consider that meta-field correlated lookups are slower than table-backed fields
   - The search uses `LIKE '%term%'` queries which don't use indexes efficiently

2. **User Experience**
   - Choose searchable fields wisely - only fields users would search for
   - Provide meaningful `title()` method implementations for resources
   - Use descriptive resource icons via the `getIcon()` method
   - Keep resource names concise for better display in results

3. **Resource Configuration**
   - Set `public static $globalSearch = false;` for internal/admin resources
   - Consider which resources users actually need to find via search
   - Use the `searchable => true` property sparingly on fields

4. **Bookmarks**
   - Encourage users to bookmark frequently accessed pages
   - First 9 bookmarks have keyboard shortcuts (`⌘ + 1` through `⌘ + 9`)

## Source Files

- Component: `src/Livewire/GlobalSearch.php`
- View: `resources/views/livewire/global-search.blade.php`
- Config: `config/aura.php` (`features.global_search`)

---

Global Search is a powerful feature that enhances the usability of your Aura CMS installation. By following these guidelines and best practices, you can ensure your users have a smooth and efficient experience finding the content they need.
