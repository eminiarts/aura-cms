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

### Resource-Level Configuration

Control whether a resource appears in global search results using the static `$globalSearch` property:

```php
class Post extends Resource
{
    public static $globalSearch = true; // Set to false to exclude from search
}
```

You can also access this setting programmatically:

```php
// Check if a resource is included in global search
$includeInSearch = Post::getGlobalSearch(); // Returns true or false
```

**Default excluded resources**: The following built-in resources are excluded from global search by default:
- `resource`, `flow`, `flowlog`, `operation`, `flowoperation`, `operationlog`, `option`, `team`, `user`, `product`

Note: While regular User resources are filtered from the resource loop, users are still searchable separately by name and email.

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
            'searchable' => false, // This field won't appear in search results
        ]
    ];
}
```

### Getting Searchable Fields

You can retrieve the searchable fields for a resource programmatically:

```php
$resource = new Post();
$searchableFields = $resource->getSearchableFields(); // Returns collection of fields with searchable => true
```

### Meta Fields Support

Global Search automatically includes meta fields marked as searchable in your field definitions. The search performs a LEFT JOIN with the `meta` table and searches both:

1. The `posts.title` column (always searched)
2. Meta field values where the field is marked as `searchable => true`

Both regular table fields and meta fields are supported as long as they have the `searchable` property set to `true`.

### User Search

Global Search also searches the User model separately, matching against:
- `name` field
- `email` field

## Search Results

### Result Structure

Search results are:
- Limited to 15 results total (across all resource types)
- Grouped by resource type after limiting
- Displayed with relevant icons and metadata
- Linked directly to the resource view page

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

### Custom Search Logic

You can customize the search behavior by extending the GlobalSearch component:

```php
use Aura\Base\Livewire\GlobalSearch;

class CustomGlobalSearch extends GlobalSearch
{
    public function getSearchResultsProperty()
    {
        // Custom search implementation
        // Must return a collection grouped by type
        
        if (!$this->search || $this->search === '') {
            return [];
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
   - Index searchable fields in your database for faster queries
   - Limit the number of searchable fields to essential ones
   - Consider that meta fields require JOIN operations which can be slower
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
