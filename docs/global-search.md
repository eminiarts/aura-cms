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
    'execution_backend' => 'process',
    'worker_php' => null,
    'worker_artisan' => null,
    'worker_connections' => ['@default'],
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
    'per_resource_timeout_ms' => 500,
    'total_timeout_ms' => 3_000,
    'isolated_payload_bytes' => 1_048_576,
    'icon_bytes' => 8_192,
    'allowed_route_names' => ['aura.*'],
    'ranking' => [
        'exact' => 300,
        'prefix' => 200,
        'contains' => 100,
    ],
],
```

The default database adapter selects a key-ordered window of at most `candidate_limit` visible rows per resource and ranks that window in PHP. Aura evaluates global scopes and `applyGlobalSearchVisibility()` on two independent query clones and requires their exact SQL, bindings, connection, compiler, and operator state to match. Resource query callbacks execute separately on a predicate-free clone; their safe parameter-bound predicates are then appended beneath one core-owned `AND` group, so a callback cannot erase or top-level-`OR` around trusted visibility. Ordering, limit, and offset changes remain supported. A callback that introduces a raw SQL fragment, changes structural query components such as the selected columns, source, joins, grouping, or having clauses, changes the connection/compiler, or adds a union fails the resource closed. The final sealed query must use the resource's exact table and connection and cannot contain a union, including in nested builders. Aura then snapshots those predicates into a callback-free subquery and adds the candidate limit on a new, core-owned outer query. The adapter verifies the sealed SQL, bindings, callback state, and final limit before execution and fails closed if any invariant changes. It never runs an unbounded `%term%` table scan and never enables a resource's default eager loads. Aura also caps registered-resource inspection, authorized resources, searchable fields, title dependencies, per-resource queries, total queries, returned results, query characters, and elapsed search work. Package hard caps still apply if published configuration is accidentally set much higher.

Aura executes discovery and each resource search in a separately booted `php artisan` process. A minimal POSIX launcher first proves that `/proc/self/fd` or `/dev/fd` is readable and enumerable, then closes every inherited descriptor above standard input/output/error before executing PHP. Requests and scalar responses cross the boundary as size-limited JSON over standard input/output; no application object, PDO handle, file, or server socket crosses into the worker. Each worker receives an app-key-signed authentication context containing an allowlisted connection name and a keyed fingerprint of that connection's configuration. It selects that connection before authentication, reloads the user there, verifies both the resolved model connection and persisted current-team identifier, and exits after one operation. A connection-name or configuration mismatch fails closed, including equal user/team identifiers in another database.

`worker_connections` is a non-empty allowlist of at most 32 configured Laravel connection names. The special `@default` entry resolves to the application's current default connection. Multi-tenant applications must list every eligible named connection and bootstrap identical connection configuration in the request and CLI worker; credentials are never serialized into the worker request. Runtime-only connection changes that the fresh CLI boot cannot reproduce intentionally fail the configuration fingerprint check.

The process backend requires the authenticated principal to be an Eloquent model so its resolved connection can be verified. Other provider types fail closed.

The total deadline includes resource discovery. After class validation, Aura creates a constructor-free model subject and evaluates `viewAny` before container resolution, resource construction, field discovery, database access, visibility, adapter, or presentation hooks. Only an authorized class is resolved and constructed, and a container substitution for a different concrete class fails closed. The resource deadline includes those authorized hooks, record policies, title dependencies, and destinations. Worker standard output is untrusted diagnostic data: the parent accepts exactly one complete, core-shaped response envelope after the command reaches its distinct completion exit. Once command execution begins, an early `exit`, `die`, or fatal error emits a failure envelope and a different exit status; forged, partial, duplicate, malformed, or metadata-inconsistent envelopes fail closed. `isolated_payload_bytes` bounds all worker output. Exceptions and deadlines are logged using only resource class, reason, exception class, and configured timeout; search terms and result data are never included.

Query limits are installed before the worker reloads the authenticated user and are enforced with a before-execution callback on every Laravel-managed database connection created by the worker. Aura wraps Laravel's database manager so every existing or newly configured connection is guarded at the connection-creation boundary, independently of dispatcher listeners. Removing `ConnectionEstablished` listeners and then purging or reconnecting therefore cannot bypass the meter. Guarded connection objects are tracked by weak object identity, so recreating a connection installs a fresh callback even when PHP reuses the previous numeric object ID, without retaining discarded connections. Authentication plus queries issued by policies, hooks, title presentation, and custom adapters count even when those extensions do not cooperate with `GlobalSearchBudget`. A timed-out or malformed resource pessimistically consumes its remaining per-resource query allocation in the parent.

Native PDO cannot be intercepted portably by Laravel's connection callback. Global-search resources, policies, visibility/title hooks, and adapters therefore must not call `getPdo()`/`getReadPdo()`, construct `PDO`, or create unmanaged database connections. Such application code violates the extension contract and is not query-metered; the independent hard process deadline still kills it. Use Eloquent, the query builder, or Laravel `Connection` methods, and use `GlobalSearchBudget` for non-database backends.

The `process` backend requires a POSIX `/bin/sh`; an enumerable `/proc/self/fd` or `/dev/fd`; PHP's `proc_open`, `pcntl` signal handlers and signal masking, and `posix_kill` in the request runtime; a CLI PHP with fork/exec/wait, POSIX signal support, and Unix socket-pair stream functions; and a readable Artisan entry point. `worker_php: null` uses Symfony's SAPI-aware PHP executable finder, so an FPM binary is never reused as the worker command; set an absolute path when PHP CLI lives in a non-standard location. `worker_artisan: null` resolves to `base_path('artisan')`; set an absolute path only for a non-standard application layout. Unsupported request runtimes fail before a supervisor is launched, and unsupported CLI runtimes fail before the application worker is launched.

The small CLI supervisor gives every operation its own monotonic deadline and directly forks both the watcher and application worker, keeping both as waitable children. Before either fork it blocks termination signals and creates two bounded, non-blocking Unix socket pairs plus a random publication token. The worker publishes its PID to the watcher; the watcher validates and relays both child PIDs; and the supervisor accepts them only when they match its exact fork results and both child identities are still live. The worker cannot unmask signals or execute Artisan until the complete tokened handshake succeeds. Publication or acknowledgement failure kills and reaps both children and fails closed. The watcher independently kills the published worker if the supervisor, request parent, or deadline disappears, while the supervisor kills and reaps the exact worker child if the watcher exits, including under `SIGKILL`. Before Artisan boots, the application worker loads a final package-owned INI fragment whose `disable_functions` value is the union of the request runtime's effective restrictions, the selected CLI runtime's restrictions, and Aura's process-spawn, process-group, and signalling restrictions; FFI is disabled too. This avoids a command-line `disable_functions` override weakening host policy. The bootstrap verifies every restriction before application code can run and exits with an actionable configuration failure if the INI layer was not honored. The request runtime also kills active supervisors on output overflow, timeout, `SIGTERM`/`SIGINT`, and fatal shutdown. If any containment prerequisite is unavailable, `execution_backend` is `none`, or configuration is invalid, the complete search fails closed. `inline-testing` exists only for package unit tests and is rejected outside Laravel's unit-test runtime.

`allowed_route_names` must be a non-empty list of at most 20 simple route-name patterns. A malformed entry invalidates the complete list and produces a metadata-only warning. Resource icons are sanitized to a small SVG allowlist and capped by `icon_bytes` before raw rendering; scripts, event handlers, URL-bearing elements, and oversized output are discarded.

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

There is no class-name or slug denylist. Aura only searches registered, concrete, instantiable `Resource` classes for which the current user first passes the resource's `viewAny` policy, `getGlobalSearch()` returns `true`, and at least one explicit searchable field exists. `viewAny` is checked before `getGlobalSearch()`, the missing-team opt-in hook, searchable-field resolution, and `max_resources`, so denied registrations cannot execute those hooks or consume searchable-resource slots. Built-in internal resources opt out on their own resource definitions. Users use the same contract as every custom-table resource and remain searchable by name and email.

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

An adapter receives the already-scoped Eloquent query, searchable fields, normalized term, candidate cap, and `GlobalSearchBudget`. It must use Laravel-managed database access, claim non-database backend operations through the budget, preserve the query's visibility constraints, return no more than the candidate cap, and return `GlobalSearchCandidate` values. Adapter failures are isolated to that resource.

Authorization policies and resource visibility hooks are trusted application code. Laravel-managed statements they issue consume the worker's central budget. They should not issue per-row queries; express record visibility on the supplied Eloquent query and preload any policy inputs. Aura bounds the candidates passed to row policies, but native PDO is prohibited and only wall-clock contained as described above.

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
