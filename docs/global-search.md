# Global search

Global search is the `Aura\Base\Livewire\GlobalSearch` component. The default Aura layout mounts it when `aura.features.global_search` is enabled. The component searches registered resources and users while the query is non-empty. With an empty query it shows recent pages and bookmarks.

Global search is database-backed. It runs `LIKE` queries against the fields selected for each resource. Aura does not build a separate search index, connect to an external search engine, or provide a search-index Artisan command.

![Global search modal](/images/docs/global-search/global-search-modal.png)

## Enable and mount global search

Global search is enabled by default:

```php
// config/aura.php
return [
    'features' => [
        'global_search' => true,
    ],
];
```

The default app layout mounts the component when the flag is true:

```blade
@if (config('aura.features.global_search'))
    <livewire:aura::global-search />
@endif
```

Set the flag to `false` to remove the component and its navigation button. The component also aborts with HTTP 403 during `mount()` when the flag is false, so a separately mounted instance cannot be used while the feature is disabled.

## Open search

The default app layout dispatches a `search` event from these controls:

- The sidebar Search button, when `global_search` is enabled.
- The `/` key through `@keydown.window.slash`.
- Command+K through `@keydown.window.prevent.cmd.k`.

The current layout registers the Command+K binding. It does not register a separate Ctrl+K binding.

The component listens for `search`, toggles the modal, and focuses the search input. The input sends its Livewire value with a 300 millisecond debounce and accepts at most 64 characters. Arrow keys move through the current list and Enter opens the selected link.

## Exclude a resource

Resources inherit `$globalSearch = true`. Set it to `false` when a resource should not be included:

```php
use Aura\Base\Resource;

class InternalNote extends Resource
{
    public static $globalSearch = false;
}
```

Read the effective value with `InternalNote::getGlobalSearch()`.

The component also excludes these slugs before it searches, even if the resource property is true:

```text
resource, flow, flowlog, operation, flowoperation,
operationlog, option, team, user, product
```

The built-in Permission and Role resources set `$globalSearch = false`. Option and Team are excluded both by their resource property and by the slug list. Users are excluded from the resource loop because the component searches users separately.

## Mark fields as searchable

Add a truthy `searchable` option to an input field. Aura fields are arrays with a field class name in `type`:

```php
use Aura\Base\Resource;

class Post extends Resource
{
    public static string $type = 'Post';

    protected static ?string $slug = 'post';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => true,
            ],
            [
                'name' => 'Summary',
                'slug' => 'summary',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'searchable' => true,
            ],
            [
                'name' => 'Internal note',
                'slug' => 'internal_note',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'searchable' => false,
            ],
        ];
    }
}
```

`getSearchableFields()` returns the input fields whose `searchable` option is truthy:

```php
$slugs = (new Post)->getSearchableFields()->pluck('slug')->all();
```

If a resource has no searchable input fields, the global-search component skips it. The `on_index` option controls table columns. It does not make a field searchable.

## How matching works

For every included resource, the component starts with the resource's normal Eloquent query. It adds one `LIKE '%term%'` condition for each searchable field:

- A table field is queried on the resource table, such as `posts.title` or `projects.name`.
- A meta field is queried with an `EXISTS` subquery against the meta table. The subquery matches the resource key, morph type, meta key, and meta value.

Which fields use the resource table or meta table comes from the resource storage settings. A resource using the default posts-plus-meta storage can search base fillable columns and meta fields. A custom-table resource can mix custom-table columns and meta fields when `$usesMeta` is true. With `$usesMeta = false`, searchable input fields must be columns on the resource table.

The query reads stored values. It does not resolve a related record's title, a computed field value, or a field's display output before matching. Use a searchable field whose stored table or meta value contains the text users need to find.

The component does not add an order, ranking, pagination, or per-resource limit to these queries. It loads the matching rows, combines the resource rows in registered-resource order, appends matching users, then applies one global limit of 15 rows. It groups those 15 rows by resource type for display.

## Permissions and team scoping

Before searching a resource, the component checks `Gate::allows('viewAny', $resource)`. A resource is omitted when the current user cannot view any records of that type. User name and email search runs only when the user can `viewAny` the User resource.

Each resource query still uses the resource's normal global scopes. With teams enabled, `TeamScope` limits ordinary resource rows to the current team. `ScopedScope` can further limit rows to the current user's records when the resource has the `scope` permission. In teams-off mode, the team scope is disabled. Global Admin behavior follows the normal User and team-scope rules.

The search component performs a type-level `viewAny` check. Opening a result still goes through the resource view component, which authorizes the selected record with the `view` ability.

## Result display and limits

The component returns no search results for an empty query. When it has matches, the modal shows up to 15 rows across all resources and users. There is no per-type quota and no relevance ranking.

Each resource result displays:

- The resource icon from `getIcon()`.
- `#id` followed by the resource's `title()` value.
- The resource type from `getType()`.
- A link to the resource's `aura.<slug>.view` route.

User matches use the User resource's name and email columns. User rows do not receive a `type` value in the search result mapper, so they form a separate empty-key group in the returned collection.

## Keyboard controls

| Shortcut | Action |
| --- | --- |
| Command+K | Toggle the search modal. |
| `/` | Toggle the search modal. |
| Escape | Clear the query when it is non-empty. Press it again to close the modal. |
| Up and Down | Move through search results, recent pages, or bookmarks. |
| Enter | Open the selected link. |
| Command+1 through Command+9 | Open bookmark 1 through bookmark 9 when that bookmark exists. |

The view checks the `search` event's own target and ignores it when that target is an input or textarea. The default keyboard handlers dispatch from the layout root, so this check does not inspect the browser's currently focused element.

## Recent pages and bookmarks

Recent pages are client-side history. Aura's app script runs on `DOMContentLoaded`, reads the `document.title` and current URL, removes an existing entry for that URL, prepends the new entry, and stores the first five entries in `localStorage` under `visitedPages`. The global-search view reads that array when it initializes. Clearing browser storage removes the history.

Bookmarks are stored per user in the `user.{id}.bookmarks` Option value. With teams enabled, the option is stored and read in the current team context. `User::getOptionBookmarks()` reads that value and caches it for one hour. The bookmark button in `aura::bookmark-page` is shown only when both `aura.features.bookmarks` and `aura.features.global_search` are enabled. Toggling the button writes the updated URL and title array back to the user option. The search modal shows saved bookmarks when the query is empty. The global-search view does not check `aura.features.bookmarks` before rendering saved entries, so disabling that flag hides the toggle but does not remove existing entries from the modal.

The first nine saved bookmarks have Command+number handlers. The stored bookmark order controls their numbers.

## Customize the component

To change the search query or result mapping, extend the component and register the replacement under the same Livewire name in a host service provider:

```php
use Aura\Base\Livewire\GlobalSearch;
use Livewire\Livewire;

class CustomGlobalSearch extends GlobalSearch
{
    public function getSearchResultsProperty()
    {
        if (! $this->search) {
            return [];
        }

        return collect([])->take(15)->groupBy('type');
    }
}

Livewire::component('aura::global-search', CustomGlobalSearch::class);
```

If a replacement keeps the default view contract, return groups of resource-like results with the methods used by the view: `getIcon()`, `getSlug()`, `getType()`, `title()`, and `id`.

## Source files and focused tests

The implementation is in:

- `src/Livewire/GlobalSearch.php`
- `resources/views/livewire/global-search.blade.php`
- `resources/views/components/layout/app.blade.php`
- `resources/views/livewire/navigation.blade.php`
- `resources/js/app.js`
- `src/Resource.php` and `src/Traits/Concerns/AuraResourceMeta.php`

Focused coverage is in:

- `tests/Feature/GlobalSearchTest.php`
- `tests/Feature/Security/GlobalSearchAuthorizationTest.php`
- `tests/Feature/Security/RemainingSecurityGapsTest.php`
- `tests/Feature/Aura/FeaturesSettingsConfigTest.php`
