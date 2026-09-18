# Global search

Global search lets users find records across registered resources and users. When the search box is empty, it shows recent pages and bookmarks. Aura includes it in the default layout when `aura.features.global_search` is enabled.

Search runs directly against your database using the searchable fields on each resource. It uses SQL `LIKE` queries and needs no separate search index or external search engine. Aura does not provide an Artisan command to build a search index.

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

Set the flag to `false` to remove search and its navigation button. A separately mounted instance also returns HTTP 403 while the feature is disabled.

## Open search

Open search with the sidebar Search button, the `/` key, or Command+K. These controls dispatch a `search` event from the default app layout. The sidebar button appears only while global search is enabled.

The current layout registers the Command+K binding. It does not register a separate Ctrl+K binding.

The event toggles the modal and focuses the search input. Search updates after a 300 millisecond debounce and accepts at most 64 characters. Use the arrow keys to move through the list and Enter to open the selected link.

## Exclude a resource

Resources participate in global search by default. Set their `$globalSearch` property to `false` to exclude them:

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

The built-in permission and role resources disable global search through their resource property. Options and teams are excluded by both the property and the slug list. Aura searches users separately, so the user slug is also excluded from the resource queries.

## Mark fields as searchable

Set `searchable` to `true` on each input field you want to search. Define the option alongside the field's name, slug, and type:

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

To retrieve the searchable input fields, call `getSearchableFields()`. It includes fields with any truthy value for the option:

```php
$slugs = (new Post)->getSearchableFields()->pluck('slug')->all();
```

If a resource has no searchable input fields, the global-search component skips it. The `on_index` option controls table columns. It does not make a field searchable.

## How matching works

Search starts with each resource's normal Eloquent query and matches the search term anywhere in a searchable field's stored value, using `LIKE '%term%'`.

Table fields are searched on the resource table. For meta fields, Aura uses an `EXISTS` subquery against the meta table. That subquery checks the resource key, morph type, and meta key so it matches values belonging to the correct record and field.

The resource's storage settings determine where Aura searches. Default resources can search both fillable columns on the posts table and meta fields. Custom-table resources can also search both table columns and meta fields when `$usesMeta` is true. When it is false, every searchable input field must be a column on the resource table.

The query reads stored values. It does not resolve a related record's title, a computed field value, or a field's display output before matching. Use a searchable field whose stored table or meta value contains the text users need to find.

Search loads all matching rows without adding query ordering, ranking, pagination, or a per-resource limit. It combines resources in their registration order, adds matching users at the end, and keeps the first 15 rows. The modal groups these rows by resource type.

## Permissions and team scoping

Aura searches only resource types that the current user is allowed to browse. It checks each resource's `viewAny` ability through Laravel's gate before running the query. The same check applies to user searches, which match names and email addresses.

Each query also keeps the resource's normal global scopes. With teams enabled, ordinary resource results are limited to the current team. The resource's `scope` permission can further limit results to the current user's records. Disabling teams removes the team scope. Global admins follow the normal user and team-scope rules.

The search permission check applies to the resource type. When a user opens a result, the resource view component separately checks the `view` ability for that record.

## Result display and limits

The component returns no search results for an empty query. When it has matches, the modal shows up to 15 rows across all resources and users. There is no per-type quota and no relevance ranking.

Each resource result shows its icon, record ID prefixed with #, title, and resource type. It links to the resource's `aura.<slug>.view` route. Custom components must provide the same result data, as described below.

User matches come from the user resource's name and email columns. These results have no assigned type, so the returned collection places them in a separate group with an empty key.

## Keyboard controls

| Shortcut | Action |
| --- | --- |
| Command+K | Toggle the search modal. |
| `/` | Toggle the search modal. |
| Escape | Clear the query when it is non-empty. Press it again to close the modal. |
| Up and Down | Move through search results, recent pages, or bookmarks. |
| Enter | Open the selected link. |
| Command+1 through Command+9 | Open bookmark 1 through bookmark 9 when that bookmark exists. |

The modal ignores a `search` event whose target is an input or textarea. The default keyboard shortcuts dispatch from the layout root, however, so this does not prevent them from opening search while another input has focus.

## Recent pages and bookmarks

Recent pages are stored in the browser. On each `DOMContentLoaded` event, Aura saves the page title and URL at the start of the history, removes any older entry for that URL, and keeps the five most recent pages. Search reads this history when it initializes. The entries live in `localStorage` under `visitedPages`, so clearing browser storage removes them.

Bookmarks belong to the current user and, with teams enabled, the current team. Aura stores their URLs and titles in the `user.{id}.bookmarks` option. The `User::getOptionBookmarks()` method reads them and caches the value for one hour.

The bookmark button appears only when both `aura.features.bookmarks` and `aura.features.global_search` are enabled. Toggling it updates the saved URLs and titles. The search modal displays saved bookmarks whenever the query is empty, even if the bookmarks feature is disabled. Disabling that feature hides the button but leaves existing bookmarks visible in search.

The first nine saved bookmarks have Command+number handlers. The stored bookmark order controls their numbers.

## Customize the component

To change the search query or result mapping, extend `Aura\Base\Livewire\GlobalSearch`. Register your replacement under the same Livewire name in an application service provider:

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

To use the default view, return results grouped by resource type. Each result must have an `id` and the methods the view calls: `getIcon()`, `getSlug()`, `getType()`, and `title()`.

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
