# Table component

`aura::table` is the Livewire component used by resource index pages. It provides list rendering, search, filters, sorting, selection, bulk actions, column preferences, pagination, and optional grid or Kanban views. Relationship fields such as `HasMany` and `BelongsToMany` use the same component for their nested tables.

The component class is `Aura\Base\Livewire\Table\Table`. Its behavior is split across the `Settings`, `Search`, `Filters`, `QueryFilters`, `Sorting`, `Select`, `BulkActions`, `PerPagePagination`, `SwitchView`, and `Kanban` traits.

## Mounting a table

The `model` property must be a resource instance. The built-in resource index creates that instance and mounts the component for you:

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

Passing a class string directly as `model` is not supported. The component calls instance methods such as `getTableHeaders()`, `defaultPerPage()`, and `tableComponentView()`.

## Settings

The component starts with `Settings::defaultSettings()` and recursively merges the `settings` property you pass to it. The resource index passes `indexTableSettings()` to that property.

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

`Resource` includes the `InteractsWithTable` methods below. Return a non-empty Blade view name to enable grid or the legacy Kanban entry point.

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

`tableView()` changes the list view. `rowView()` changes the row partial used by the standard list. `tableComponentView()` changes the outer Livewire view and is used by resources such as the media resource.

## Columns

The standard headers come from input fields. `getTableHeaders()` takes fields from `indexHeaderFields()`, removes fields hidden by `on_index => false` or conditional visibility, and returns a `slug => name` map. `getColumns()` returns that map as an array. `getDefaultColumns()` returns a `slug => true` visibility map.

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

The `settings.columns` value is the header map. The component's `columns` property is the visibility map. The list and settings views render a header only when its visibility value is truthy.

Column visibility is stored in the user option `columns.{Type}` when a checkbox changes. Dragging the headers calls `reorder(array $slugs)`. With the default `columns_user_key`, the ordered header map is stored in the same user option. Set `columns_global_key` to store the ordered map through Aura for the current team.

## Search

The search input uses Livewire's debounced `search` property and `updatedSearch()` resets the paginator to page one.

The default query searches only fields whose definition contains `'searchable' => true`. Regular fields use a `LIKE` condition. Meta-backed fields use an `EXISTS` query against the configured meta table.

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

The default search does not discover relationship fields. Add relationship conditions in `modifySearch()` when they are needed.

## Filters

The query builder consumes grouped filters in `filters.custom`. A group contains a `filters` list. The first filter is applied with `AND`; later filters use their `main_operator`. Every group after the first uses its `operator` to join with the preceding groups.

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

The filter UI gets its field list and operators from each field's `filterOptions()`. The query builder applies the matching condition to a physical table field, a meta field, or a relation-backed taxonomy field. Blank values are ignored for the empty operators and invalid filter payloads fail closed.

The operators exposed by the built-in field classes are:

| Field | Operators |
| --- | --- |
| Text and other base fields | `contains`, `does_not_contain`, `is`, `is_not`, `starts_with`, `ends_with`, `is_empty`, `is_not_empty`, `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `in`, `not_in`, `like`, `not_like`, `regex`, `not_regex` |
| Number | `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `is_empty`, `is_not_empty` |
| Date and Datetime | `date_is`, `date_is_not`, `date_before`, `date_after`, `date_on_or_before`, `date_on_or_after`, `date_is_empty`, `date_is_not_empty` |
| Select | `is`, `is_not`, `is_empty`, `is_not_empty` |
| Tags | `contains`, `does_not_contain` |
| AdvancedSelect | `contains` |

Tags and relation-backed AdvancedSelect filters use the selected related IDs. The table adds the relation resource type to the filter options before applying the query.

![Table filters](/images/docs/table/table-filters.png)

### Saved filters

`saveFilter()` validates the filter name and stores the current filter state under a slug made from that name. With `filter.global = false`, it writes the option to the current user. With `filter.global = true` and teams enabled, it writes the option to the current team. `selectedFilter` is in the query string, so selecting a saved filter can survive a reload.

```php
$this->set('filter.name', 'Popular posts');
$this->set('filter.global', false);
$this->call('saveFilter');
```

Saved filters are read from the user and current-team option namespaces and merged by slug. `deleteFilter($slug)` removes a saved filter from its owning namespace.

The save dialog also stores `filter.public`, but the current `userFilters()` implementation does not use that flag when loading filters. It does not make a user filter available to other users.

## Sorting

Sorting is single-column. `sortBy($field)` removes any previous field, then cycles the selected field through ascending, descending, and unsorted. When `sorts` is empty, the query uses `defaultTableSort()` and `defaultTableSortDirection()`.

The built-in sorter handles:

- physical table fields with `orderBy`;
- meta fields with a join to `meta`, casting Number fields as `DECIMAL(10,2)` and other values as `CHAR`;
- taxonomy fields with a join to `post_relations`, ordering by the minimum related resource ID.

A resource can provide `sort_{slug}($query, $direction)`. The method must change the builder in place. The table stops its default sorting path after it calls the custom method.

```php
public function sort_popularity($query, $direction): void
{
    $query->orderByRaw('(views + comments) '.$direction);
}
```

## Selection and actions

Selection state is held in:

- `selected`, the selected row IDs;
- `selectPage`, the current-page checkbox state;
- `selectAll`, the flag used by the select-all flow.

The standard view supports shift-click ranges in the browser. `selectPageRows()` adds the current page's IDs to the selection. `selectAll()` resolves all rows matching the active search and filters, across pages. The mutation authorizer caps a bulk selection at 500 rows and checks every selected row inside the table scope.

### Row actions

The `actions` setting controls the standard row-actions column and the context menu. The row partial renders View and Edit links when the corresponding policies and URL helpers permit them. `view_in_modal` and `edit_in_modal` switch those buttons to the resource modals.

A context menu is rendered when the resource's `getContextMenu()` returns true. Its built-in View and Edit entries call the table's `action()` method. A custom row action must be declared by the resource and must declare an ability unless it is one of the built-in action names.

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

The table checks the declaration, resolves the record through the current table scope, and authorizes the declared ability before invoking the model method.

### Bulk actions

The resource can expose bulk actions through a `$bulkActions` property or a `bulkActions()` method. The table reads them through `getBulkActions()`.

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

List is always supported. Grid and Kanban are opt-in.

### List view

The default list view renders a table header, one row partial per paginator item, and the table footer. Header cells call `sortBy($slug)`. The standard row partial uses `$row->display($slug)` for visible columns.

Override `tableView()` or pass `views.list` to use a custom list view. Override `rowView()` or pass `views.row` to change each row.

### Grid view

Override `tableGridView()` with a Blade view name:

```php
public function tableGridView()
{
    return 'resources.posts.grid';
}
```

The view is included when `currentView` is `grid`. It can use the paginated `$rows`, `$rowIds`, the resource model, and the component's `settings`. The component still applies the same base query, filters, search, sorting, and pagination.

### Kanban view

Kanban needs a valid configuration. The default `kanbanSettings()` enables it when `tableKanbanView()` returns a non-empty view name. A resource can declare the board directly:

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

`columns` restricts the board to declared option keys. An invalid column key disables the board. `card_title` and `card_subtitle` select the fields displayed on each card. `show_empty_columns` controls empty-column rendering.

The built-in Kanban view renders the configured columns and filters the paginator's rows by `group_field`. It does not implement drag-and-drop. A custom board can call `updateCardStatus($cardId, $newStatus)`; the method checks that the destination is declared and authorizes the resource's `update` policy.

The Kanban settings menu persists visibility and order in the user option `kanban_statuses.{Type}`. Use `reorderKanbanStatuses()` or its `reorderKanbanColumns()` alias from a custom control. The resource's `kanbanQuery($query)` hook can change query ordering or add constraints:

```php
public function kanbanQuery($query)
{
    return $query->orderBy('position');
}
```

Returning `false` leaves the base query unchanged. If a resource defines `kanbanPagination()`, the table uses that value as `perPage` while Kanban is active. It is the total number of rows fetched for the table query, not a separate limit for each column.

The `order_by` key is normalized by the configuration class, but the current built-in query does not apply it. Use `kanbanQuery()` when ordering matters.

## Relationship tables

The standard `HasMany` field view mounts the table with the related resource instance, the field definition, and the parent resource:

```blade
<livewire:aura::table
    :model="app($field['resource'])"
    :field="$field"
    :settings="$mergedSettings"
    :parent="$this->model"
    :disabled="true"
/>
```

The field `type` must be the fully-qualified field class so the table can call its `queryFor()` method. `HasMany` scopes through the declared relationship. `BelongsToMany` scopes through the parent's many-to-many relationship.

Relationship tables start with these settings disabled: `filters`, `global_filters`, `header_before`, `header_after`, `settings`, `search`, and `selectable`. Override them with `table_settings` in the field definition:

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

The related table runs the same search, filter, sort, and pagination pipeline after `queryFor()` scopes it to the parent.

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

For the standard list, `tableEagerLoads()` limits relation eager loads to visible columns. Grid and Kanban can use fields outside the list columns, so they retain the broader field set. Table-display preloaders batch fields such as relation and image displays for the paginator's rows.

## Pagination

The table uses Livewire's `WithPagination`. The initial `perPage` value is resolved in this order:

1. The session value at `perPage`.
2. `settings.per_page`.
3. The resource's `defaultPerPage()`.

Changing `perPage` writes the new value to the session. The standard settings menu offers 10, 25, 50, and 100 rows. Search resets to page one. The rows computed property calls `paginate($perPage)` and passes the paginator to the list, grid, or Kanban view.

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
