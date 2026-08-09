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
    'adapter' => Aura\Base\GlobalSearch\DatabaseGlobalSearchAdapter::class,
    'execution_backend' => 'auto',
    'minimum_query_length' => 2,
    'maximum_query_length' => 64,
    'max_resources' => 25,
    'max_resource_candidates' => 100,
    'max_fields_per_resource' => 8,
    'candidate_limit' => 100,
    'per_resource_limit' => 5,
    'global_limit' => 15,
    'max_title_dependencies' => 4,
    'max_queries_per_resource' => 4,
    'max_total_queries' => 100,
    'per_resource_timeout_ms' => 150,
    'total_timeout_ms' => 750,
    'database_statement_timeout_ms' => 150,
    'isolated_payload_bytes' => 1_048_576,
    'allowed_route_names' => ['aura.*'],
    'ranking' => [
        'exact' => 300,
        'prefix' => 200,
        'contains' => 100,
    ],
],
```

The default database adapter selects a key-ordered window of at most `candidate_limit` visible rows per resource and ranks that window in PHP. It never runs an unbounded `%term%` table scan and never enables a resource's default eager loads. Aura also caps registered-resource inspection, authorized resources, searchable fields, title dependencies, per-resource queries, total queries, returned results, query characters, and elapsed search work. Package hard caps still apply if published configuration is accidentally set much higher.

`database_statement_timeout_ms` is installed only around built-in candidate and title-dependency queries and is restored in a `finally` block, including after an exception or a nested deadline. A stricter pre-existing limit is left untouched. MySQL/MariaDB and PostgreSQL use session statement limits. SQLite uses its connection lock-wait limit in addition to the adapter's bounded indexed window. Microsoft SQL Server's PDO driver only exposes whole-second query timeouts, so an effective deadline below 1000 milliseconds (after applying the remaining resource and total budgets) fails closed on that driver. Unknown drivers fail closed rather than executing without a deadline.

Custom adapters and their resource presentation work execute in a disposable child process. `per_resource_timeout_ms` kills the complete process group when the deadline expires; `total_timeout_ms` stops Aura from starting more resource work. `isolated_payload_bytes` bounds data returned to the parent. Exceptions and deadlines are logged using only the resource class, reason, exception class, and configured timeout; search terms and result data are never included.

The `auto` and `fork` execution backends require a Unix CLI or CLI-server SAPI with `pcntl`, `posix`, and Unix socket support. Isolation is deliberately unavailable inside a nested isolated call or an Octane worker. On unsupported SAPIs, Windows, Octane, or when `execution_backend` is `none`, custom adapters fail closed and the remaining built-in resources continue. Keep the built-in database adapter when deploying to such a runtime, or move custom search to a separately supervised service with its own enforceable deadline.

This bounded default has an intentional completeness tradeoff: a matching row beyond the key window is not returned. Applications requiring complete or relevance-indexed search should supply an indexed adapter (Scout, Meilisearch, a database full-text index, or equivalent) through `global_search.adapter` or the resource's `globalSearchAdapter()` hook, and must deploy on a runtime where Aura can isolate it.

Ranking is deterministic inside the candidate window. A result's score is the configured match-quality score plus its field weight. Equal scores use resource registration order and then the model key in ascending order. Matching is exact, prefix, then contains using case-sensitive PHP string semantics. That gives SQLite, MySQL, PostgreSQL, and SQL Server the same codepoint/byte behavior regardless of database collation; composed and decomposed Unicode remain distinct. Query punctuation such as `%`, `_`, and `!` is literal because user input is never interpolated into a `LIKE` expression.

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

There is no class-name or slug denylist. Aura only searches registered, concrete, instantiable `Resource` classes for which `getGlobalSearch()` returns `true`, the current user passes the resource's `viewAny` policy, and at least one explicit searchable field exists. `viewAny` is checked before `max_resources` is applied, so denied registrations do not consume searchable-resource slots. Built-in internal resources opt out on their own resource definitions. Users use the same contract as every custom-table resource and remain searchable by name and email.

In teams mode, an unauthenticated user or an authenticated user without a current team receives no results. A trusted resource may deliberately override `globalSearchAllowsMissingTeamContext()` and return `true`, but it must then enforce the intended visibility in `applyGlobalSearchVisibility()`.

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

Global Search supports both table-backed and meta-backed fields in the explicit contract. The default adapter fetches only configured searchable meta keys for rows inside the bounded candidate window. It does not hydrate the Resource model's unconditional `meta` eager load.

No `title` column is assumed. Resources that use `name`, a meta field, or another custom-table column can be searched normally, and the resource's existing `title()` method controls the displayed label.

### User Search

The built-in User resource declares these searchable fields:
- `name` field
- `email` field

## Search Results

### Result Structure

Search results are:
- Selected from at most 100 candidates, limited to 5 results per resource and 15 globally by default
- Grouped by resource type after limiting
- Converted to immutable, scalar result DTOs before rendering
- Returned only when both `viewAny` and record-level `view` authorization pass
- Linked only to an allowed same-origin named GET route

Record-level policy denials do not consume result slots while candidates remain in the configured window. The policy check remains a defense in depth; row visibility should also be expressed in SQL with `applyGlobalSearchVisibility()` so forbidden candidates do not enter the window at all.

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

Apply resource-specific SQL visibility without replacing the component. This hook runs before the candidate window is limited or ranked:

```php
use Illuminate\Database\Eloquent\Builder;

public function applyGlobalSearchVisibility($query, $user)
{
    return $query->where('status', 'published');
}
```

The normal Eloquent global scopes, including Aura's team scope, remain active. `newGlobalSearchQuery()` is still available for changing the base query, but visibility belongs in the explicit visibility hook.

Use a named GET route for non-standard result destinations:

```php
public function globalSearchDestination()
{
    return [
        'route' => 'aura.orders.view',
        'parameters' => ['id' => $this->getKey()],
    ];
}
```

Route names must match `allowed_route_names`, support GET, resolve to the application's origin, and receive only declared path parameters. Existing `globalSearchUrl()` overrides remain supported only when they return a query-free, fragment-free relative or same-origin HTTP(S) URL that resolves to an allowed named GET route. External, protocol-relative, `javascript:`, `data:`, unknown-route, and open-redirect-style destinations are rejected. Aura still requires both `viewAny` for the resource and `view` for the returned record.

### Indexed Adapter

Implement `Aura\Base\Contracts\GlobalSearchAdapter` to replace bounded key-window discovery for one resource or globally:

```php
public function globalSearchAdapter()
{
    return App\Search\IndexedOrderSearch::class;
}
```

An adapter receives the already-scoped Eloquent query, searchable fields, normalized term, candidate cap, and `GlobalSearchBudget`. It must claim every database/backend operation through the budget, preserve the query's visibility constraints, return no more than the candidate cap, and return `GlobalSearchCandidate` values. Adapter failures are isolated to that resource.

Authorization policies and resource visibility hooks are trusted application code outside the adapter budget. They should not issue per-row queries; express record visibility on the supplied Eloquent query and preload any policy inputs. Aura bounds the candidates passed to row policies, but it cannot cancel or meter arbitrary queries issued inside application hooks.

### Title Dependencies

Candidate models start with `meta` disabled and lazy loading prevented during presentation. Declare the small set of meta keys or direct `BelongsTo` relations required by `title()`:

```php
public function globalSearchTitleDependencies()
{
    return [
        'meta' => ['display_name'],
        'relations' => ['company'],
    ];
}
```

Only declared dependencies for authorized, retained candidates are loaded, and those queries consume the resource and global query budgets. Invalid dependencies, lazy-load attempts, non-scalar titles, and presentation exceptions safely omit the affected result.

### Custom Search Component

You can customize the search behavior by extending the GlobalSearch component:

```php
use Aura\Base\Livewire\GlobalSearch;

class CustomGlobalSearch extends GlobalSearch
{
    public function getSearchResultsProperty()
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
   - Keep visibility predicates supported by indexes used in the candidate query
   - Limit the number of searchable fields to essential ones
   - Lower `candidate_limit` for tighter latency; raise it only when the completeness tradeoff is acceptable
   - Use an indexed adapter when results must be complete across a large resource
   - Verify that the deployment runtime supports Aura's isolated custom-adapter backend before enabling one

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
