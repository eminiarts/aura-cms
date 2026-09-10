# Performance

Aura's performance behavior comes from the query shape, model and process memoization, cache-store entries, and lazy loading in the admin UI. This guide documents those package behaviors and gives you small ways to measure them. Measure the route and workload that is slow before changing configuration.

## Measure one request

Use Laravel's query listener in a local environment to record query count and database time for one request or Livewire update:

```php
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

DB::listen(function (QueryExecuted $query): void {
    logger()->debug('Aura query', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time_ms' => $query->time,
    ]);
});
```

For a table or report query, record the query count and the slowest statement before and after a change. A high count with repeated statements against the same related table usually means a relation is being lazy-loaded while rows render. A single slow statement usually needs an index or a different storage/query shape.

For a database plan, inspect the SQL for the exact database engine you run. On MySQL, for example:

```php
$query = Order::query()->whereMeta('category', 'news');

dump($query->toRawSql());
```

Run the displayed statement with `EXPLAIN` in your database client. Aura does not choose indexes for an application's custom fields, and this page contains no benchmark that applies to every database or dataset.

## Process state and long-running workers

Aura keeps several small caches in PHP memory:

| State | Lifetime | Reset path |
| --- | --- | --- |
| Field definitions in `InputFieldsHelpers` | The PHP process | `Aura::flushState()` calls `Resource::flushFieldCache()`. |
| A resource's processed `fields` and normalized meta map | One model instance | `clearFieldsAttributeCache()`, relation replacement, and the model's `saved` hook clear them. |
| Table display values | One model instance during a table render | The table primes the current page and the values disappear with those model instances. |
| A user's resolved roles | One `User` model instance, keyed by team and role-catalog version | Refresh or use a new model instance. `Aura::flushState()` also resets related process state. |

`Aura::flushState()` restores the resource, field, widget, and injected-view registrations captured at boot. It clears conditional-logic state, field caches, scope state, and the configured user model. It does not clear Laravel's cache store or database rows.

The package calls `Aura::flushState()` after queue jobs finish or fail. When Laravel Octane is installed, the service provider also listens for `RequestReceived`, `TaskReceived`, and `TickReceived`. Those hooks protect process-local field, registration, scope, and user-model state across requests. You do not need to add an application-specific reset callback for those Aura caches.

## Cache-store entries and invalidation

Aura uses Laravel's cache manager through the `Cache` facade. The package does not require a particular cache driver, cache tags, or a queue worker for ordinary page rendering.

| Data | Reader | Key and default lifetime |
| --- | --- | --- |
| Named Aura settings | `Aura::getOption($name)` | `{team_id}.aura.{name}` with a current team, or `aura.{name}` without one. One hour. |
| Navigation | `Aura::navigation()` | `user-{id}-{team_id}-navigation-{resource-list-hash}`. 3,600 seconds. |
| Template discovery | `Aura::templates()` | `aura.templates`. One hour. |
| User option values | `User::getOption()` and convenience readers | `user.{id}.{option}`, `user.{id}.bookmarks`, `user.{id}.columns.{resource}`, `user.{id}.sidebar`, and `user.{id}.sidebarToggled`. One hour. |
| User team IDs | `User::getTeams()` | `user.{id}.teams`. One hour. Global Admins use the shared `aura.global_admin.teams` key. |
| Current team ID | `TeamScope` | `user_{id}_current_team_id`. Stored forever only after a non-null team ID is found. |
| Value, pie, and donut widget results | The `getValuesProperty()` methods | An MD5 key built from team ID, resource type, widget slug, start, and end. The default duration is 60 seconds, or `widget.cache.duration`. |

Field definitions and resolved roles are model or process state, not entries in this table. `User::cachedRoles()` currently memoizes resolved roles on the model instance. It does not read the old cache key returned by `getCacheKeyForRoles()`.

Use the package writers when changing values that Aura reads through a cache:

```php
use Aura\Base\Facades\Aura;

// Invalidates the exact global or current-team Aura::getOption() key.
Aura::updateOption('columns_global_key', ['title', 'status']);

// User::updateOption() forgets the user option key after writing it.
auth()->user()->updateOption('columns.Order', [
    'id' => true,
    'status' => true,
]);
```

If application code writes an option row directly, forget the key used by the reader. If application code changes a user's current team through a direct database update, clear the team-scope entry as well:

```php
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;

Cache::forget('user.'.$user->id.'.columns.Order');
Cache::forget(User::currentTeamCacheKey($user->id));
```

`User::switchTeam()` persists the new `current_team_id`, and the model's `saved` hook clears the current-team cache. Team deletion also clears current-team and team-list entries for affected users. Those paths do not clear every user option entry.

`Aura::navigation()` has no permission-change invalidation hook. The resource list hash makes a newly registered resource use a new key, but a permission change can leave the previous navigation in cache until its one-hour lifetime ends. For the current authenticated user, the exact key can be forgotten with:

```php
Cache::forget(app('aura')->navigationCacheKey());
```

`Aura::clearConditionsCache()` clears only conditional-logic state. `Aura::clear()` refreshes route lookups and calls `Cache::clear()`, which clears the entire configured cache store. Use the exact key when possible. Treat `Aura::clear()` as a maintenance operation, not a request-handler default.

### A teams-on user-option caveat

In teams-on mode, `User::updateOption()` writes the current `team_id` to the `options` row, but the `User::getOption*()` cache keys above do not include that team ID. A warmed table-column or sidebar option can therefore be reused after a user switches teams until the entry expires or is forgotten. Clear the affected `user.{id}...` key after a team switch if that behavior matters to your application. This is a package defect to fix in the cache-key implementation, not a reason to flush the whole cache store.

## Resource storage and database indexes

The default `Resource` uses the shared `posts` table and `meta` rows:

| `customTable` | `usesMeta` | Field storage |
| --- | --- | --- |
| `false` | `true` | Base fillable fields use `posts`; other input fields use `meta`. |
| `false` | `false` | Base fillable fields use `posts`; other input fields have no meta destination. |
| `true` | `true` | Base fillable fields use the custom table; other input fields use `meta`. |
| `true` | `false` | Every input field must have a physical column on the custom table. |

`customTable` and `usesMeta` are independent flags. Setting only `customTable` does not move every field into a column. See [Custom tables](/docs/custom-tables) before converting an existing resource.

The Aura migration stub creates these relevant indexes:

- `posts.slug`, `posts.user_id`, and `posts.parent_id` are indexed.
- With teams enabled, `posts` gets a composite `team_id, type` index.
- With teams disabled, `posts` gets a composite `type, status, created_at, id` index.
- `meta` gets the polymorphic columns, a `metable_type, metable_id, key` composite index, and a `key` index. MySQL also gets a prefix index over `metable_id, key, value(255)`.
- `post_relations` gets the polymorphic columns, a `resource_id, related_id, related_type` index, and a `slug` index.

These indexes support identity and key lookups. They do not make arbitrary text searches or casts on the long `meta.value` column cheap. Add application-specific indexes to a custom table when a field is frequently filtered or sorted.

### Meta query behavior

The meta scopes use Eloquent relationship subqueries. These examples assume declared `category`, `subtitle`, `featured`, and JSON `topics` meta fields. Query core columns such as `posts.status` with `where()` instead:

```php
Order::whereMeta('category', 'news')->get();
Order::whereMeta('subtitle', 'like', 'Order%')->get();
Order::whereMeta([
    'category' => 'news',
    'featured' => true,
])->get();
Order::orWhereMeta('category', 'updates')->get();
Order::whereInMeta('category', ['news', 'updates'])->get();
Order::whereNotInMeta('category', ['archived'])->get();
Order::whereMetaContains('topics', 'laravel')->get();
```

Each `whereMeta` condition becomes a `whereHas('meta')` subquery. Multiple conditions add multiple meta subqueries. `whereMetaContains` uses JSON containment against `meta.value`, so the stored value must be valid JSON for that operation.

Sorting a meta field uses a left join restricted by resource type and field key. Number fields are ordered with `CAST(meta.value AS DECIMAL(10,2))`; other fields use `CAST(meta.value AS CHAR)`. The cast and the long text value can dominate a large sort even when the relation indexes are present.

When a filter or sort is a frequent part of a high-volume query, use a custom table with a real column and an index that matches the query. The generated custom resource stub sets both flags explicitly:

```php
use Aura\Base\Resource;

class Order extends Resource
{
    public static string $type = 'Order';

    public static $customTable = true;

    public static bool $usesMeta = false;

    protected $table = 'orders';
}
```

The table still needs a migration containing every input field column. The package does not create a table merely because the flags are present.

## Table queries and relationship loading

The table query starts with the resource query and applies the following package hooks before pagination:

1. `indexQuery($query, $component)`, when the resource defines it.
2. A field-specific `queryFor` hook, when the table is rendering a field query.
3. A configured dynamic query and Kanban constraints.
4. `with('meta')` whenever `usesMeta()` is true.
5. Relation names returned by fields implementing `ProvidesTableEagerLoad`.

Use `indexQuery` for relations that your own table view reads:

```php
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;

class Order extends Resource
{
    public function indexQuery(Builder $query, ?Table $component = null): Builder
    {
        return $query->with(['customer']);
    }
}
```

The table limits package-managed relation loading to the fields that can appear in the current view. In list view, `ProvidesTableEagerLoad` fields are collected from visible columns. Grid and Kanban views can use more fields, so their eager-load set starts from all input fields. `Tags` and polymorphic `AdvancedSelect` fields opt into this path by returning their field slug as a relation name. A relation field that does not implement the contract is not inferred automatically.

Some fields use a page-level display preloader instead of Eloquent eager loading. Visible `BelongsTo`, `Image`, and `Roles` columns collect the IDs for the paginated rows, run scoped lookups, and store the results on each row. This keeps team and other model scopes active. Custom display closures and custom views can issue their own queries, so inspect those paths separately.

Table display also has a field-level fast path. For a plain visible input field with no conditional logic, `Resource::display()` resolves that field without building the complete `fields` collection. Conditional fields, hidden fields, and nested field slugs use the full accessor. Keep expensive relationship fields out of the index when the table does not need them.

### Serialized fields

Aura resources append the computed `fields` accessor to array and JSON serialization by default. Building that accessor resolves every input field, including field casts and relationships. For a large table response or export that does not need the computed map, disable the legacy append in `config/aura.php`:

```php
'features' => [
    // ...
    'legacy_fields_append' => false,
],
```

Call `$resource->append('fields')` at the specific boundary that needs the map. The setting does not change table display, which resolves its requested column separately when the fast path applies.

## AdvancedSelect fields

`AdvancedSelect` uses its API path by default. The field class sets `$api = true`, and the Blade component uses that value unless the field definition includes an `api` key.

With the API path:

- The edit form queries selected IDs only so existing selections render without loading the complete resource set.
- The first options request runs when the listbox opens, not during the initial form render.
- Each API page contains up to 10 options. `Load more` requests the next page.
- Search runs through the resource's searchable fields and starts a new page at 10 results.

This field definition keeps the default lazy behavior:

```php
[
    'name' => 'Actors',
    'slug' => 'actors',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => App\Aura\Resources\Actor::class,
    'multiple' => true,
],
```

Set `api` to `false` only when the complete option set is small enough to load during render:

```php
[
    'name' => 'Priority',
    'slug' => 'priority',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => App\Aura\Resources\Priority::class,
    'api' => false,
],
```

The non-API path calls `values()` and loads every target resource. The API path is the safer default for a relation with many options. The focused behavior is covered by `tests/Feature/Fields/AdvancedSelectLazyLoadingTest.php`.

## Media thumbnails

Media uploads use `aura.media.disk` and `aura.media.path`. The default configuration stores files on the `public` disk under `media`:

```php
'media' => [
    'disk' => 'public',
    'path' => 'media',
    'quality' => 80,
    'restrict_to_dimensions' => true,
    'max_file_size' => 10000, // kilobytes
    'generate_thumbnails' => true,
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

The uploader applies `max_file_size` as Laravel's kilobyte validation limit and stores the file on the configured disk and path. It allows the package's documented file types and rejects executable extensions and SVG uploads.

When an image `Attachment` is saved, its model hook dispatches `GenerateImageThumbnail`, a `ShouldQueue` job. The job reads the media settings, skips when `generate_thumbnails` is false, and asks `ThumbnailGenerator` to create each configured dimension. It does nothing in the testing environment. The job logs a failure for an individual dimension and continues with the remaining dimensions.

Thumbnails are also generated on demand by the `aura.image` route. `Attachment::thumbnail('sm')` looks up the named configured dimension and returns that route URL. The generator:

- rejects a width or width and height pair that is not in `media.dimensions` when `restrict_to_dimensions` is true;
- returns an existing thumbnail without regenerating it;
- keeps the original path for a width-only request larger than the source image;
- scales width-only requests without upscaling; and
- writes generated output as JPEG under `thumbnails/{source-folder}/` using the configured quality.

The generator reads and writes through `Storage::disk(config('aura.media.disk'))`. A non-public disk must provide a working `url()` implementation for `Attachment::path()` and `thumbnail_path()`, and the image route still reads the bytes from that configured disk.

Aura constructs Intervention Image 3 with its GD driver. Enable the PHP GD extension for thumbnail generation. Check the PHP runtime used by Laravel with `php -m`; it must list `gd`. Installing the optional Laravel image facade or enabling Imagick alone does not change the driver used by Aura.

If a thumbnail is missing, check the configured disk, the original attachment path, the requested named dimension, and the job log. Do not add an arbitrary resize URL when `restrict_to_dimensions` is enabled.

## Widgets

Resource widgets render before the resource table. Their Livewire views use `wire:init` so a widget without a cached result first renders its placeholder and loads its value after the component mounts.

The built-in `ValueWidget`, `Pie`, and `Donut` cache their value payloads with `cache()->remember()`. Their default duration is 60 seconds. Set `cache.duration` in the widget definition to change it. The cache key includes the current team, resource type, widget slug, start date, and end date. `Sparkline`, `SparklineArea`, `SparklineBar`, and `Bar` use the shared loading view, but the current `Sparkline` implementation does not wrap its values in the base cache call.

```php
use Aura\Base\Resource;
use Aura\Base\Widgets\ValueWidget;

class Order extends Resource
{
    public static function getWidgets(): array
    {
        return [[
            'name' => 'Orders',
            'slug' => 'orders-total',
            'type' => ValueWidget::class,
            'method' => 'count',
            'cache' => ['duration' => 30],
        ]];
    }
}
```

Widget result caches are not invalidated when a resource row changes. Choose a duration that fits the freshness your dashboard needs. See [Widgets](/docs/widgets) for the available widget definitions and date ranges.

## Focused troubleshooting

Use the symptom to choose the smallest check:

| Symptom | Check |
| --- | --- |
| Repeated related-table queries in a table | Log queries for one page. Check whether the field is visible and whether it implements `ProvidesTableEagerLoad` or `PreloadsTableDisplay`. Inspect custom display views and closures. |
| Slow filter or sort on a field | Determine whether `isMetaField($slug)` is true. Inspect the `whereHas` or meta join and run `EXPLAIN` on the generated SQL. Move a frequent high-volume field to a custom column when the workload warrants it. |
| A newly changed setting is not visible | Confirm the writer used `Aura::updateOption()` or `User::updateOption()`. Inspect the exact cache key and forget only that key when a direct database write bypassed the writer. |
| A user sees old team-scoped preferences | Clear the affected `user.{id}...` key after switching teams. The current package cache key omits team ID for these user option readers. |
| A thumbnail request returns 404 | Check that the requested dimensions are declared when `restrict_to_dimensions` is true, then check the configured disk and original path. |
| A thumbnail job produced no file | Check `generate_thumbnails`, the queue job log, the image's MIME type, and the PHP GD extension. |
| A widget is stale | Check its `cache.duration`, slug, date range, and whether the widget class actually uses the base cache path. |

## Related guides

- [Custom tables](/docs/custom-tables)
- [Meta fields](/docs/meta-fields)
- [Media library](/docs/media-manager)
- [Table](/docs/table)
- [Widgets](/docs/widgets)
