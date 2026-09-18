# Table component

Resource index pages use the table component to display records. Users can search, filter, sort, select records, run bulk actions, and choose which columns to show. The table supports pagination and optional grid or Kanban views. Has-many and many-to-many relationship fields use the same component for their nested tables.

The Livewire component is registered as `aura::table`. Its class is `Aura\Base\Livewire\Table\Table`, with separate traits for settings, search, filters, sorting, selection, bulk actions, pagination, view switching, and Kanban behavior.

## Mounting a table

Pass a resource instance to the table's `model` property. The built-in resource index creates the instance and mounts the component for you:

```blade
<livewire:aura::table
    :model="$resource"
    :settings="$resource->indexTableSettings()"
/>
```

For a custom page, resolve the resource from the container:

```blade
<livewire:aura::table :model="app(App\Aura\Resources\Post::class)" />
```

Do not pass a class name string as the model. The table needs an instance so it can read the resource's headers, page size, and view settings.

## Settings

You can override the table's defaults through its `settings` property. Overrides merge recursively with the defaults returned by `Settings::defaultSettings()`. On resource index pages, define these overrides in the resource's `indexTableSettings()` method.

| Key | Default | Effect |
| --- | --- | --- |
| `per_page` | `10` | Initial page size. Session state and the resource default can override it. |
| `columns` | `$model->getTableHeaders()` | Header map used by the table. |
| `search` | `true` | Render the search input. |
| `filters` | `true` | Render the filter controls. |
| `global_filters` | `true` | Present in the default settings. It does not currently gate filter loading or saving. |
| `sort` | `['column' => 'id', 'direction' => 'desc']` | Initial sort metadata. Active user sorting is stored in the `sorts` property. |
| `settings` | `true` | Render the settings and view controls when the current view supports them. |
| `sort_columns` | `true` | Allow column drag-and-drop in the settings menu. |
| `columns_user_key` | `columns.{Type}` | User option key used for column order and visibility. |
| `columns_global_key` | `false` | Aura option key for a team-wide column order. |
| `title` | `true` | Show the resource title in the header. |
| `selectable` | `true` | Render row-selection controls. |
| `default_view` | `$model->defaultTableView()` | Initial view, normally `list`. |
| `header` | `true` | Render the table header area. |
| `header_before` / `header_after` | `true` | Enable the corresponding header injection points. |
| `table_before` / `table_after` | `true` | Enable the corresponding table injection points. |
| `create` | `true` | Show the create button when the policy permits it. |
| `create_url` | `null` | Override the generated create URL. |
| `actions` | `true` | Render the row-actions column and context menu when available. |
| `bulk_actions` | `true` | Render the bulk-actions menu. |
| `edit_in_modal` | `false` | Open edit actions in the resource slide-over. |
| `create_in_modal` | `false` | Open create from the header in a modal. |
| `view_in_modal` | `false` | Open view actions in the resource slide-over. |

The view settings are merged separately:

```php
'views' => [
    'table' => 'aura::components.table.index',
    'list' => $this->model->tableView(),
    'grid' => $this->model->tableGridView(),
    'kanban' => $this->model->tableKanbanView(),
    'filter' => 'aura::components.table.filter',
    'header' => 'aura::components.table.header',
    'row' => $this->model->rowView(),
    'bulk_actions' => 'aura::components.table.bulk-actions',
    'table_header' => 'aura::components.table.table-header',
    'table_footer' => 'aura::components.table.footer',
    'filter_tabs' => 'aura::components.table.filter-tabs',
],
```

For a list view, the outer `table` view includes the `list` view. Grid and Kanban include their own configured view directly. All custom view names must resolve to a Blade view.

![Table settings](/images/docs/table/table-settings.png)

### Resource defaults

Resources inherit the following defaults through the `InteractsWithTable` trait. Override the methods you need. Returning a non-empty Blade view name from the grid or legacy Kanban view method enables that view.

```php
class Post extends Resource
{
    public function defaultPerPage(): int
    {
        return 25;
    }

    public function defaultTableSort(): string
    {
        return 'created_at';
    }

    public function defaultTableSortDirection(): string
    {
        return 'desc';
    }

    public function defaultTableView(): string
    {
        return 'list';
    }

    public function showTableSettings(): bool
    {
        return true;
    }

    public function tableGridView()
    {
        return 'resources.posts.grid';
    }

    public function tableKanbanView()
    {
        return false;
    }

    public function indexTableSettings(): array
    {
        return [
            'per_page' => 25,
            'view_in_modal' => true,
        ];
    }
}
```

To customize the list markup, override `tableView()`. To change only its rows, override `rowView()`. The `tableComponentView()` method replaces the outer Livewire view, as the media resource does.

## Columns

The table builds its headers from the resource's input fields. Fields with `on_index => false` or a failing visibility condition are excluded.

For custom views, `getTableHeaders()` reads the fields from `indexHeaderFields()` and returns a map of field slugs to header names. The `getColumns()` method returns that map as an array, while `getDefaultColumns()` maps each slug to `true` for its initial visibility.

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'title',
            'on_index' => true,
        ],
        [
            'name' => 'Body',
            'type' => 'Aura\\Base\\Fields\\Textarea',
            'slug' => 'body',
            'on_index' => false,
        ],
    ];
}
```

Header labels and visibility are separate. The `settings.columns` value maps slugs to labels, while the component's `columns` property tracks visibility. The list and settings views show a header only when its visibility value is truthy.

Changing a column checkbox saves its visibility in the user's `columns.{Type}` option. Dragging headers calls `reorder(array $slugs)` to save their order. By default, the ordered header map uses the same user option. You can change that key with `columns_user_key`, or set `columns_global_key` to save the order through Aura for the current team.

## Search

Search waits briefly after typing before updating the results, using Livewire's debounced `search` property. Each change resets pagination to page one through `updatedSearch()`.

Only fields marked with `'searchable' => true` take part in the default search. Aura searches regular columns with a SQL LIKE condition. For fields stored as meta, it uses an EXISTS query against the configured meta table.

```php
[
    'name' => 'Title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'slug' => 'title',
    'searchable' => true,
]
```

Define `modifySearch($query, $search)` on the resource to replace the default search:

```php
public function modifySearch($query, $search)
{
    return $query->where(function ($query) use ($search) {
        $query
            ->where('title', 'like', '%'.$search.'%')
            ->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%'.$search.'%'));
    });
}
```

Relationship fields are not included automatically. Add the relationship conditions you need to your custom search method.

## Filters

Filters are arranged in groups under `filters.custom`. Each group contains a `filters` list. Within a group, the query applies the first filter with AND and joins later filters using their `main_operator`. Each group after the first uses its own `operator` to join the preceding groups.

```php
$filters = [
    'custom' => [
        [
            'filters' => [
                [
                    'name' => 'status',
                    'operator' => 'is',
                    'value' => 'published',
                ],
                [
                    'name' => 'views',
                    'operator' => 'greater_than',
                    'value' => 1000,
                    'main_operator' => 'and',
                ],
            ],
        ],
        [
            'operator' => 'or',
            'filters' => [
                [
                    'name' => 'featured',
                    'operator' => 'is',
                    'value' => true,
                ],
            ],
        ],
    ],
];
```

Use the grouped methods when building filter state:

| Method | Effect |
| --- | --- |
| `addFilterGroup()` | Add a group with one empty filter. |
| `addSubFilter($groupKey)` | Add a filter to an existing group. |
| `removeFilter($groupKey, $filterKey)` | Remove one filter and remove an empty group. |
| `removeFilterGroup($groupKey)` | Remove a whole group. |
| `removeCustomFilter($index)` | Remove an entry from `filters.custom`. |
| `resetFilter()` | Clear custom filters. |

`addFilter()` still exists, but it creates a legacy flat entry without a `filters` wrapper. The query builder expects groups, so use `addFilterGroup()` and `addSubFilter()`.

Each field's `filterOptions()` method supplies the fields and operators available in the filter controls. Aura applies the chosen condition to the database column, meta field, or taxonomy relationship as appropriate. Empty-value operators ignore blank values. Invalid filter data fails closed rather than broadening the results.

The operators exposed by the built-in field classes are:

| Field | Operators |
| --- | --- |
| Text and other base fields | `contains`, `does_not_contain`, `is`, `is_not`, `starts_with`, `ends_with`, `is_empty`, `is_not_empty`, `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `in`, `not_in`, `like`, `not_like`, `regex`, `not_regex` |
| Number | `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `is_empty`, `is_not_empty` |
| Date and Datetime | `date_is`, `date_is_not`, `date_before`, `date_after`, `date_on_or_before`, `date_on_or_after`, `date_is_empty`, `date_is_not_empty` |
| Select | `is`, `is_not`, `is_empty`, `is_not_empty` |
| Tags | `contains`, `does_not_contain` |
| AdvancedSelect | `contains` |

Tag filters and advanced selects backed by relationships filter by the selected related record IDs. Before applying the query, the table adds the related resource type to the filter options.

![Table filters](/images/docs/table/table-filters.png)

### Saved filters

Users can save the current filters under a name. The `saveFilter()` method validates that name and uses its slug as the storage key. Filters belong to the current user by default. Set `filter.global` to `true` to save them for the current team when teams are enabled.

The selected filter is kept in the `selectedFilter` query parameter, so it can survive a page reload.

```php
$this->set('filter.name', 'Popular posts');
$this->set('filter.global', false);
$this->call('saveFilter');
```

Aura loads saved filters from both the current user's options and the current team's options, then merges them by slug. Use `deleteFilter($slug)` to remove a filter from the user or team that owns it.

The save dialog also stores `filter.public`, but Aura does not currently use that flag when loading filters through `userFilters()`. It does not share a user's filter with other users.

## Sorting

Users can sort by one column at a time. Calling `sortBy($field)` clears the previous column and cycles the chosen column through ascending, descending, and unsorted. When no user sort is active, the query uses the resource's `defaultTableSort()` and `defaultTableSortDirection()` values.

The built-in sorter handles:

- Database columns use `orderBy`.
- Meta fields join the `meta` table. Number fields are cast to `DECIMAL(10,2)` and other values to `CHAR`.
- Taxonomy fields join `post_relations` and sort by the lowest related resource ID.

To customize sorting for a column, define `sort_{slug}($query, $direction)` on the resource. Modify the supplied query builder in place. Once this method runs, the table skips its default sorting logic.

```php
public function sort_popularity($query, $direction): void
{
    $query->orderByRaw('(views + comments) '.$direction);
}
```

## Selection and actions

The component tracks selected row IDs in `selected`. It also keeps the current-page checkbox state in `selectPage` and a flag for selection across all pages in `selectAll`.

Users can select a range of rows with Shift-click. Custom controls can call `selectPageRows()` to add the current page or `selectAll()` to select every record matching the active search and filters across pages. Bulk mutations are limited to 500 selected rows. The table checks every selected record within its current scope before authorizing a mutation.

### Row actions

The `actions` setting controls the row-actions column and context menu. View and Edit links appear only when the resource's policies and URL helpers permit them. Enable `view_in_modal` or `edit_in_modal` to open the corresponding action in a resource modal.

The context menu appears when the resource's `getContextMenu()` method returns true. Its View and Edit entries call the table's `action()` method. Declare custom row actions on the resource, including the policy ability each action requires. Only built-in action names can omit the ability.

```php
public array $actions = [
    'publish' => [
        'label' => 'Publish',
        'ability' => 'update',
    ],
];
```

The standard context menu does not render arbitrary entries from `$actions`. A custom row view or another Livewire control can call:

```php
$this->call('action', [
    'action' => 'publish',
    'id' => $postId,
]);
```

Before calling the model method, the table checks that the action is declared, finds the record within the current table scope, and authorizes the required ability.

### Bulk actions

Define bulk actions on the resource using a `$bulkActions` property or a `bulkActions()` method. The table reads either definition through `getBulkActions()`.

```php
public array $bulkActions = [
    // Calls deleteSelected() once for every selected row.
    'deleteSelected' => [
        'label' => 'Delete',
        'ability' => 'delete',
    ],

    // Calls archive($ids) once with all selected IDs.
    'archive' => [
        'label' => 'Archive',
        'method' => 'collection',
        'ability' => 'update',
    ],

    // Opens the named modal with the authorized selected IDs.
    'export' => [
        'label' => 'Export',
        'modal' => 'export::export-selected-modal',
        'ability' => 'view',
    ],
];
```

The dispatch rules are:

- A string value, or an array without `modal` and without `method => collection`, calls `bulkAction($key)`. The table invokes the method on each selected model. Keys beginning with `callFlow.` call `callFlow($id)` instead.
- An array with `modal` calls `openBulkActionModal()` and dispatches `openModal` with the action, selected IDs, and model class.
- An array with `method => collection` calls `bulkCollectionAction()`. The resource method receives the selected ID array. A `StreamedResponse` is returned to the browser.

Every bulk action must be declared. Custom action names must include an `ability` in their definition. The table checks the policy for every selected record before it invokes the method.

## View modes

Every table supports the list view. Enable grid or Kanban on the resource when you need them.

### List view

The default list view renders a header, a row partial for each record on the current page, and a footer. Clicking a header sorts by that column through `sortBy($slug)`. Each visible cell gets its display value from `$row->display($slug)`.

Override `tableView()` or pass `views.list` to use a custom list view. Override `rowView()` or pass `views.row` to change each row.

### Grid view

Override `tableGridView()` with a Blade view name:

```php
public function tableGridView()
{
    return 'resources.posts.grid';
}
```

The table includes your view when the user switches to grid mode. It receives the paginated `$rows`, `$rowIds`, the resource model, and the component's `settings`. Grid mode uses the same base query, filters, search, sorting, and pagination as the list.

### Kanban view

To enable Kanban, provide a valid board configuration and a view. By default, `kanbanSettings()` enables the board when `tableKanbanView()` returns a non-empty view name. Configure the board on your resource:

```php
public function kanbanSettings(): array
{
    return array_replace(parent::kanbanSettings(), [
        'enabled' => true,
        'group_field' => 'status',
        'columns' => ['todo', 'in_progress', 'done'],
        'card_title' => 'title',
        'card_subtitle' => 'content',
        'show_empty_columns' => true,
    ]);
}

public function tableKanbanView()
{
    return 'aura::components.table.kanban-view';
}
```

The group field must be a resource field whose options can be normalized into keys and labels. Select options can use either keyed values or entries with `key`, `value`, and an optional `color`:

```php
[
    'name' => 'Status',
    'type' => 'Aura\\Base\\Fields\\Select',
    'slug' => 'status',
    'options' => [
        ['key' => 'todo', 'value' => 'To do', 'color' => 'gray'],
        ['key' => 'done', 'value' => 'Done', 'color' => 'green'],
    ],
]
```

Use `columns` to restrict the board to particular option keys. An invalid key disables the board. Choose the fields shown on each card with `card_title` and `card_subtitle`, and use `show_empty_columns` to control whether columns with no cards appear.

The built-in board groups records from the current page into the configured columns using `group_field`. It does not support drag-and-drop. If you add that behavior in a custom board, call `updateCardStatus($cardId, $newStatus)` to move a card. This method checks that the destination is declared and authorizes the resource's update policy.

The Kanban settings menu saves column visibility and order in the user's `kanban_statuses.{Type}` option. Custom controls can call `reorderKanbanStatuses()` or its alias, `reorderKanbanColumns()`.

To change the board's query ordering or add constraints, define `kanbanQuery($query)` on the resource:

```php
public function kanbanQuery($query)
{
    return $query->orderBy('position');
}
```

Returning `false` leaves the base query unchanged. To set the page size for Kanban, define `kanbanPagination()` on the resource. Its return value becomes the active page size for the whole board, not a separate limit for each column.

The `order_by` key is normalized by the configuration class, but the current built-in query does not apply it. Use `kanbanQuery()` when ordering matters.

## Relationship tables

The standard has-many field view mounts a nested table with the related resource instance, the field definition, and the parent resource:

```blade
<livewire:aura::table
    :model="app($field['resource'])"
    :field="$field"
    :settings="$mergedSettings"
    :parent="$this->model"
    :disabled="true"
/>
```

Set the field's `type` to its fully qualified class name so the table can call the field's `queryFor()` method. Has-many fields scope the query through the declared relationship. Many-to-many fields scope it through the parent's many-to-many relationship.

Relationship tables hide filters, table settings, search, and row selection by default. The header injection points are also disabled. The affected settings are `filters`, `global_filters`, `header_before`, `header_after`, `settings`, `search`, and `selectable`. Override them through `table_settings` in the field definition:

```php
[
    'name' => 'Movies',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => App\Aura\Resources\Movie::class,
    'slug' => 'movies',
    'table_settings' => [
        'per_page' => 5,
        'search' => true,
        'filters' => false,
        'actions' => false,
        'selectable' => false,
        'create' => false,
    ],
]
```

After the field scopes the query to the parent, the nested table applies the same search, filtering, sorting, and pagination as a resource index table.

## Query and display pipeline

The table builds its rows in this order:

1. Start with the resource query ordered by the resource table's ID descending.
2. Call `indexQuery($query, $table)` when the resource defines it.
3. Call the relationship field's `queryFor()` when the table has a field and parent.
4. Apply `kanbanQuery()` while Kanban is active.
5. Eager-load `meta` for resources that use meta storage.
6. Eager-load relation fields that implement `ProvidesTableEagerLoad`.
7. Apply grouped filters.
8. Apply search.
9. Apply the active or default sort.
10. Paginate with `perPage`.

A resource can use `indexQuery()` for scoped constraints and explicit eager loads:

```php
public function indexQuery($query, $table)
{
    return $query
        ->with('author')
        ->withCount('comments');
}
```

The standard list eager-loads relationships only for visible columns, as determined by `tableEagerLoads()`. Grid and Kanban views can display fields outside those columns, so they retain the broader set of fields. Aura also batches the data needed to display relationships and images for records on the current page.

## Pagination

The table uses Livewire pagination through the `WithPagination` trait. It chooses the initial page size in this order:

1. The session value at `perPage`.
2. `settings.per_page`.
3. The resource's `defaultPerPage()`.

Changing the page size saves it in the session. The standard settings menu offers 10, 25, 50, and 100 rows, and changing the search returns to page one. The table fetches records with `paginate($perPage)` and passes the resulting paginator to whichever view is active.

## Persisted state

| State | Storage |
| --- | --- |
| Active view | User option `table_view.{Type}` |
| Column visibility | User option `columns.{Type}` |
| Column order | `columns_user_key`, defaulting to the same `columns.{Type}` option |
| Team-wide column order | Aura option named by `columns_global_key` |
| Rows per page | Session key `perPage` |
| User saved filters | User option `{Type}.filters.{slug}` |
| Team saved filters | Current-team option `{Type}.filters.{slug}` when `filter.global` is true |
| Kanban order and visibility | User option `kanban_statuses.{Type}` |

## Events

The table dispatches `tableMounted`, `rowIdsUpdated`, `selectedRows`, `refreshTable`, and `openModal` during its normal flows. It listens for `refreshTable`, `refreshTableSelected`, `selectedRows`, `selectFieldRows`, and `selectRowsRange`.

## Related guides

- [Resources](/docs/resources) for resource definitions and index settings.
- [Fields](/docs/fields) for field options, `on_index`, `searchable`, and field-specific filters.
- [Meta fields](/docs/meta-fields) for meta storage and its query behavior.
- [Livewire components](/docs/livewire-components) for other built-in components.
