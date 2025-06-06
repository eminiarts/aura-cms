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

Global Search is a Livewire component that allows users to:
- Search across multiple resource types simultaneously
- Access recently visited pages
- Use keyboard shortcuts for quick navigation
- View and access bookmarked pages

*Figure 1: Global Search Interface*

![Global Search Interface](placeholder-image.png)

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

### Resource-Level Configuration

Control whether a resource appears in global search results:

```php
class Post extends Resource
{
    public static $globalSearch = true; // Set to false to exclude from search
}
```

## Usage

### Accessing Global Search

There are multiple ways to access Global Search:

1. Click the search icon in the navigation bar
2. Use the keyboard shortcut `⌘ + K` (Mac) or `Ctrl + K` (Windows/Linux)
3. Click the search field in the admin interface

### Search Interface Features

The search interface provides:
- Real-time search results
- Resource type grouping
- Recently visited pages
- Bookmarked pages
- Keyboard navigation

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
            'name' => 'Custom Field',
            'slug' => 'custom_field',
            'type' => 'Aura\\Base\\Fields\\Text',
            'searchable' => true,
        ]
    ];
}
```

### Meta Fields Support

Global Search automatically includes meta fields marked as searchable in your field definitions. Both regular fields and meta fields are supported as long as they have the `searchable` property set to `true`.

## Search Results

### Result Structure

Search results are:
- Grouped by resource type
- Limited to 15 results per search
- Displayed with relevant icons and metadata
- Linked directly to the resource view page

### Result Display

Each search result shows:
- Resource ID
- Resource title
- Resource type
- Resource icon
- Direct link to view the resource

## Keyboard Shortcuts

Global Search supports keyboard navigation:

| Shortcut | Action |
|----------|--------|
| `⌘ + K` | Open search (Mac) |
| `Ctrl + K` | Open search (Windows/Linux) |
| `ESC` | Close search/Clear input |
| `↑` | Previous result |
| `↓` | Next result |
| `Enter` | Go to selected result |
| `⌘ + 1-9` | Quick access bookmarks |

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
    }
}
```

### Custom Result Display

Customize how search results are displayed by publishing and modifying the view:

```bash
php artisan vendor:publish --tag=aura-views --force
```

Then modify `resources/views/vendor/aura/livewire/global-search.blade.php`

## Best Practices

1. **Performance**
   - Index searchable fields in your database
   - Keep search queries optimized
   - Use appropriate field types for searchable content

2. **User Experience**
   - Choose searchable fields wisely
   - Provide meaningful titles for resources
   - Use descriptive resource icons

3. **Maintenance**
   - Regularly review searchable fields
   - Monitor search performance
   - Update search indexes when needed

*Video 1: Using Global Search*

![Using Global Search](placeholder-video.mp4)

---

Global Search is a powerful feature that enhances the usability of your Aura CMS installation. By following these guidelines and best practices, you can ensure your users have a smooth and efficient experience finding the content they need.
