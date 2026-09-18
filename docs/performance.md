# Performance

Database queries, caching, and when the admin UI loads data all affect Aura's performance. This guide explains how those parts work and how to measure them. Start with the route and workload that is slow before changing configuration.

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

For a table or report query, record the query count and the slowest statement before and after a change. A high count with repeated statements against the same related table usually means a relation is being lazy-loaded while rows render. A single slow statement usually needs an index, a different query, or a change to how the data is stored.

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

To reset Aura's state within a PHP process, use `Aura::flushState()`. It restores the resource, field, widget, and injected-view registrations captured at boot. It also clears conditional logic, field caches, scope state, and the configured user model. Laravel's cache store and database rows are unaffected.

Aura resets this state after queue jobs finish or fail. With Laravel Octane, it also resets when a request, task, or tick begins, through the `RequestReceived`, `TaskReceived`, and `TickReceived` events. These hooks prevent field definitions, registrations, scopes, and the user model from carrying over between requests. You do not need to add a reset callback for these caches.

## Cache-store entries and invalidation

Aura uses Laravel's cache manager. Ordinary page rendering does not require a particular cache driver, cache tags, or a queue worker.

| Data | Reader | Key and default lifetime |
| --- | --- | --- |
| Named Aura settings | `Aura::getOption($name)` | `{team_id}.aura.{name}` with a current team, or `aura.{name}` without one. One hour. |
| Navigation | `Aura::navigation()` | `user-{id}-{team_id}-navigation-{resource-list-hash}`. 3,600 seconds. |
| Template discovery | `Aura::templates()` | `aura.templates`. One hour. |
| User option values | `User::getOption()` and convenience readers | `user.{id}.{option}`, `user.{id}.bookmarks`, `user.{id}.columns.{resource}`, `user.{id}.sidebar`, and `user.{id}.sidebarToggled`. One hour. |
| User team IDs | `User::getTeams()` | `user.{id}.teams`. One hour. Global Admins use the shared `aura.global_admin.teams` key. |
| Current team ID | `TeamScope` | `user_{id}_current_team_id`. Stored forever only after a non-null team ID is found. |
| Value, pie, and donut widget results | The `getValuesProperty()` methods | An MD5 key built from team ID, resource type, widget slug, start, and end. The default duration is 60 seconds, or `widget.cache.duration`. |

Field definitions and resolved roles stay in PHP memory rather than Laravel's cache store. Despite its name, `User::cachedRoles()` keeps resolved roles on the model instance. It does not read the old cache key returned by `getCacheKeyForRoles()`.

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

Switching teams through `User::switchTeam()` saves the new current team and clears its cached ID through the model's saved hook. Deleting a team also clears current-team and team-list entries for affected users. Neither operation clears every cached user option.

Navigation can remain cached for up to an hour after a permission change. Registering a new resource changes the cache key, but changing permissions does not clear it. To refresh navigation for the authenticated user, forget its exact key:

```php
Cache::forget(app('aura')->navigationCacheKey());
```

`Aura::clearConditionsCache()` clears only conditional-logic state. `Aura::clear()` refreshes route lookups and calls `Cache::clear()`, which clears the entire configured cache store. Use the exact key when possible. Treat `Aura::clear()` as a maintenance operation, not a request-handler default.

### A teams-on user-option caveat

With teams enabled, Aura saves user options with the current team ID, but their cache keys omit that ID. A cached table-column or sidebar preference can therefore carry over after a user switches teams. It remains cached until the entry expires or is cleared.

Clear the affected `user.{id}...` key after a team switch if your application needs separate preferences for each team. This is a defect in the package's cache keys. Clearing the whole cache store is unnecessary.

## Resource storage and database indexes

By default, resources store data in the shared posts table and its related meta rows. Two flags control this storage:

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

Meta filters use Eloquent relationship subqueries. The following examples assume you have declared category, subtitle, and featured meta fields, plus a topics field containing JSON. Use `where()` to query core columns such as `posts.status`:

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

Each meta condition adds its own relationship subquery through `whereHas('meta')`. Adding more conditions adds more subqueries. JSON containment filters use `whereMetaContains`, which requires valid JSON in the stored meta value.

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

Aura loads relationships for fields that can appear in the current table view. List view uses visible columns, while grid and Kanban views start from all input fields.

A field declares its relationships through `ProvidesTableEagerLoad`. Tags and polymorphic advanced select fields use this contract to return their field slug as the relationship name. Aura does not infer relationships for fields that do not implement it.

Belongs-to, image, and role columns use a display preloader instead of Eloquent eager loading. Visible columns load their display values together for the current page. They collect IDs from the paginated rows, look them up with team and other model scopes still active, and store the results on each row. Custom display closures and views can issue their own queries, so inspect those separately.

For a visible input field without conditional logic, `Resource::display()` resolves only the requested field. It does not build the complete field collection. Conditional fields, hidden fields, and nested field slugs still require the full collection. Keep expensive relationship fields out of the index when the table does not need them.

### Serialized fields

By default, converting a resource to an array or JSON includes its computed `fields` value. Building this value resolves every input field, including casts and relationships. If a large table response or export does not need it, disable the legacy append in `config/aura.php`:

```php
'features' => [
    // ...
    'legacy_fields_append' => false,
],
```

You can still include the computed values where needed by calling `$resource->append('fields')`. This setting does not change table display. The table continues to resolve individual columns when they meet the conditions described above.

## AdvancedSelect fields

Advanced select fields load options through the API by default. You can override this behavior with the `api` setting in the field definition.

With API loading enabled:

- The edit form queries selected IDs only so existing selections render without loading the complete resource set.
- The first options request runs when the listbox opens, not during the initial form render.
- Each API page contains up to 10 options. **Load more** requests the next page.
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

Disabling API loading calls `values()` and loads every target resource. Keep API loading enabled for relationships with many options. See `tests/Feature/Fields/AdvancedSelectLazyLoadingTest.php` for the focused tests.

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

Saving an image attachment dispatches the queued `GenerateImageThumbnail` job. It creates each configured size unless thumbnail generation is disabled with `generate_thumbnails`. It also skips generation in the testing environment. If one size fails, the job logs the failure and continues with the remaining sizes.

Thumbnails are also generated on demand by the `aura.image` route. `Attachment::thumbnail('sm')` looks up the named configured dimension and returns that route URL. The generator:

- rejects a width or width and height pair that is not in `media.dimensions` when `restrict_to_dimensions` is true;
- returns an existing thumbnail without regenerating it;
- keeps the original path for a width-only request larger than the source image;
- scales width-only requests without upscaling; and
- writes generated output as JPEG under `thumbnails/{source-folder}/` using the configured quality.

The generator reads and writes files on the configured media disk. A non-public disk must provide a working `url()` implementation so the attachment's `path()` and `thumbnail_path()` methods can return URLs. The image route reads the file bytes from that same disk.

Aura constructs Intervention Image 3 with its GD driver. Enable the PHP GD extension for thumbnail generation. Check the PHP runtime used by Laravel with `php -m`; it must list `gd`. Installing the optional Laravel image facade or enabling Imagick alone does not change the driver used by Aura.

If a thumbnail is missing, check the configured disk, the original attachment path, the requested named dimension, and the job log. Do not add an arbitrary resize URL when `restrict_to_dimensions` is enabled.

## Widgets

Resource widgets render before the resource table. Their Livewire views use `wire:init` so a widget without a cached result first renders its placeholder and loads its value after the component mounts.

Value, pie, and donut widgets cache their results for 60 seconds by default. Set `cache.duration` in the widget definition to change this. The cache key includes the current team, resource type, widget slug, start date, and end date.

Sparkline, sparkline area, sparkline bar, and bar widgets share the loading view. The current sparkline implementation does not use the base widget's result cache.

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
