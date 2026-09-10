# Resources

A resource is a PHP class that extends `Aura\Base\Resource`. It defines a content type through static configuration and a `getFields(): array` method. Aura uses that definition for the admin pages, routes, navigation entry, table, and policy checks. The class remains an Eloquent model, so normal casts, fillable attributes, relationships, scopes, and model events still apply.

This page covers the resource contract. See [Creating resources](/docs/creating-resources) for the first resource walkthrough and [Fields](/docs/fields) for field types and their options.

<a id="creating-resources"></a>
## Creating a resource

Generate a resource in the configured application resource directory:

~~~bash
php artisan aura:resource Article
php artisan aura:resource Product --custom
~~~

The command signature is `aura:resource {name} {--custom}`. With the default configuration, it writes the class to app/Aura/Resources in the `App\Aura\Resources` namespace.

A posts-backed resource starts with these declarations:

~~~php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';

    public static ?string $slug = 'article';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'on_forms' => true,
            ],
        ];
    }
}
~~~

The generated class is enough for Aura to register the resource. The default field with the title slug is saved in the shared posts table. The generated slug becomes the route segment and the route-name segment.

The --custom stub sets public static `$customTable` = true, public static bool `$usesMeta` = false, and a protected `$table` name. It does not create the database table. Add a migration and make every input field slug a column when meta storage is disabled.

![Resource index page](/images/docs/resources/resources-index.png)

<a id="registration-and-discovery"></a>
## Registration and discovery

Aura registers application resources by scanning `config('aura-settings.paths.resources.path')` and keeping classes that extend `Aura\Base\Resource`. The default paths are:

~~~php
// config/aura-settings.php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
    ],
    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
    ],
],

'widgets' => [
    'namespace' => 'App\\Aura\\Widgets',
    'path' => app_path('Aura/Widgets'),
],
~~~

Aura also registers the built-in resources from `config('aura.resources')`. Attachment, Option, Permission, Role, and User are registered by default. Team and TeamInvitation are registered only when `config('aura.teams')` is true.

A package or service provider can add resources to the registry:

~~~php
use Aura\Base\Facades\Aura;

Aura::registerResources([
    \Acme\Blog\Resources\Article::class,
]);
~~~

`Aura::getResources()` returns the registered class strings. `Aura::getAppResources()` scans the configured application directory and returns only subclasses of Resource.

<a id="configuration"></a>
## Resource configuration

Configuration lives in static properties on the resource. The base declarations below show the visibility and type that a subclass must respect. The generated application stub redeclares several protected properties as public, which PHP permits.

### Identity

| Property | Base declaration and default | Used for |
|---|---|---|
| `$type` | protected static string = 'Resource' | Type discriminator for posts-backed rows. |
| `$slug` | protected static ?string = null | URL and route-name segment. |
| `$name` | protected static ?string = null | Optional internal name. |
| `$singularName` | public static = null | Singular navigation and title label. |
| `$pluralName` | public static = null | Explicit plural navigation label. |

Set `$type` and `$slug` explicitly in application resources:

~~~php
public static string $type = 'Article';
public static ?string $slug = 'article';
~~~

`getSlug()` uses the explicit slug. If it is null, it slugifies `$name` or the class basename. `singularName()` uses the raw static `$slug` when no singular label is set. `pluralName()` pluralizes that singular label. The static `getPluralName()` method instead pluralizes `$type`, so use the instance `pluralName()` method for the navigation label.

The generated stub makes `$slug` public. Keep that declaration even when you rely on a derived route slug, because the permission generator currently reads the static property directly.

### Navigation

| Property | Base declaration and default | Used for |
|---|---|---|
| `$group` | protected static ?string = 'Resources' | Sidebar group. |
| `$sort` | protected static ?int = 100 | Order within the group. |
| `$showInNavigation` | protected static bool = true | Whether Aura shows the entry. |
| `$dropdown` | protected static = false | Optional dropdown name. |
| `$icon` | protected static ?string = null | SVG returned by `getIcon()`. |
| `$contextMenu` | public static = true | Whether the table context menu is enabled. |

`getIcon()` returns the configured `$icon` or Aura's default SVG. Override it when the icon must be computed at runtime. `getBadge()` and `getBadgeColor()` return empty values by default and can add a navigation badge.

### Storage and capabilities

| Property | Base declaration and default | Used for |
|---|---|---|
| `$customTable` | public static = false | Use a dedicated table instead of posts. |
| `$usesMeta` | public static bool = true | Store overflow field values in meta. |
| `$title` | protected static bool = false | Treat title as a configured table field and initialize it on save. |
| `$createEnabled` | public static = true | Allow the policy's create ability. |
| `$editEnabled` | public static = true | Allow the policy's update ability. |
| `$viewEnabled` | public static = true | Allow the policy's view ability. |
| `$indexViewEnabled` | public static bool = true | Allow the policy's viewAny ability. |
| `$globalSearch` | public static = true | Include the resource in global search. |
| `$taxonomy` | public static = false | Mark the resource as a taxonomy. |
| `$showActionsAsButtons` | public static = false | Render record actions as buttons instead of a dropdown. |

Every resource also has these public arrays:

~~~php
public array $actions = [];
public array $bulkActions = [];
public array $metaFields = [];
public array $taxonomyFields = [];
public array $widgetSettings = [
    'default' => '30d',
    'options' => [
        '1d' => '1 Day',
        '7d' => '7 Days',
        '30d' => '30 Days',
        '60d' => '60 Days',
        '90d' => '90 Days',
        '180d' => '180 Days',
        '365d' => '365 Days',
        'all' => 'All',
        'ytd' => 'Year to Date',
        'qtd' => 'Quarter to Date',
        'mtd' => 'Month to Date',
        'wtd' => 'Week to Date',
        'last-year' => 'Last Year',
        'last-month' => 'Last Month',
        'last-week' => 'Last Week',
        'custom' => 'Custom',
    ],
];
~~~

The inherited Eloquent properties `$table`, `$fillable`, `$casts`, `$hidden`, and `$appends` keep their normal Laravel meaning. A resource starts with posts as its table, the shared posts columns in its base fillable list, meta hidden from serialization, and fields appended to array and JSON output. When `$usesMeta` is true, the constructor eager loads meta.

Do not add a type when redeclaring an untyped inherited property:

~~~php
// Correct
public static $customTable = true;

// Fatal property declaration
public static bool $customTable = true;
~~~

The typed properties `$type`, `$slug`, `$name`, `$group`, `$sort`, `$showInNavigation`, `$icon`, `$usesMeta`, `$title`, and `$indexViewEnabled` must keep compatible types. The generated stub is the safe starting point.

<a id="fields"></a>
## Fields

`getFields(): array` returns plain configuration arrays. The type value must be a fully qualified field class string because Aura resolves it through the container.

~~~php
public static function getFields(): array
{
    return [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'on_index' => true,
            'on_forms' => true,
            'searchable' => true,
        ],
        [
            'name' => 'Body',
            'slug' => 'body',
            'type' => 'Aura\\Base\\Fields\\Wysiwyg',
            'instructions' => 'The article body.',
            'on_forms' => true,
        ],
    ];
}
~~~

Common field keys are:

| Key | Effect |
|---|---|
| name | Label shown in forms and tables. |
| slug | Storage key and dynamic attribute name. |
| type | Fully qualified field class. |
| validation | Laravel validation rules as a string or array. |
| on_index | Include the field in the resource table. |
| on_forms | Include it on create and edit forms. |
| on_create, on_edit, on_view | Limit the field to one page. |
| searchable | Include the field in Aura's resource search queries. |
| default | Value used when the form initializes. |
| instructions | Help text shown under the field. |
| conditional_logic | Rules or a closure that controls visibility. |

Wrapper fields such as Tab and Panel can pass display flags to nested fields. Use [Fields](/docs/fields) for the complete field catalogue and per-type options.

The resource field helpers are:

- `fieldBySlug($slug)` returns the raw definition or null.
- `fieldClassBySlug($slug)` resolves the field class from the container.
- `fieldsCollection()` returns the cached raw definitions as a collection.
- `inputFields()` returns processed input fields.
- `inputFieldsSlugs()` returns their slugs as an array.
- `indexFields()` returns fields whose on_index value is not false.
- `getFieldsWithIds()` and `getFieldsWithIdsWithoutWrappers()` add generated IDs to processed definitions.
- `getGroupedFields()`, `createFields()`, `editFields()`, and `viewFields()` run the field pipelines used by the corresponding forms and views.
- `flushFieldCache()` clears process-static field caches. Call it after changing definitions in a long-lived process or between test definitions.

![Resource edit page](/images/docs/resources/resources-edit.png)

<a id="storage"></a>
## Storage

Aura combines two independent flags. `$customTable` selects the model table. `$usesMeta` selects whether overflow field values can use the meta table.

| `$customTable` | `$usesMeta` | Storage behavior |
|---|---|---|
| false | true | Base fillable attributes go to posts; other input fields go to meta. |
| false | false | Only base fillable posts attributes are saved. Other input fields have no meta destination. |
| true | false | Every input field slug must be a column on the custom table. |
| true | true | Base fillable custom-table columns are saved in the table; remaining input fields go to meta. |

Check the destination for a slug with `isTableField($slug)` and `isMetaField($slug)`. The custom-table migration and physical columns are the application's responsibility. See [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) for migrations and transfer commands.

A custom-table resource with no meta storage looks like this:

~~~php
class Product extends Resource
{
    public static string $type = 'Product';
    public static ?string $slug = 'product';

    public static $customTable = true;
    public static bool $usesMeta = false;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'price',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_forms' => true,
            ],
            [
                'name' => 'Price',
                'slug' => 'price',
                'type' => 'Aura\\Base\\Fields\\Number',
                'on_forms' => true,
            ],
        ];
    }
}
~~~

For posts-backed resources, Aura fills the posts columns content, user_id, team_id when the relevant configuration and authenticated user are present, type from `$type`, and slug from the title when the row uses the posts table. `$title` initializes an empty title when the resource opts into title handling.

The built-in meta scopes query the polymorphic meta relation:

~~~php
Article::whereMeta('featured', true)->get();
Article::whereMeta('priority', '>', 5)->get();
Article::whereMeta(['featured' => true, 'locale' => 'en'])->get();
Article::orWhereMeta('spotlight', true)->get();
Article::whereInMeta('category', ['news', 'updates'])->get();
Article::whereNotInMeta('category', ['internal', 'archived'])->get();
Article::whereMetaContains('tags', 'laravel')->get();
~~~

These scopes use relation subqueries. Store frequently filtered or sorted values in a real table column when you need database indexes or ordinary joins.

<a id="attributes"></a>
## Attribute resolution

Resource property access is meta-aware. The resolution order is:

1. Eloquent attributes, accessors, and loaded or lazy relations.
2. A non-null result from that Eloquent lookup, including 0, false, and an empty string.
3. A relation field's `getRelation()` result.
4. The computed fields collection.
5. null when no value exists.

The fields accessor resolves input fields and caches the collection on the model instance. `clearFieldsAttributeCache()` clears it and refreshes the meta relation when needed. The default `aura.features.legacy_fields_append` setting is true, so fields is included in serialization. Set it to false when serialized resources do not need the computed map, then opt in for a model with `$model->append('fields')`.

A resource can intercept one field during reads and saves:

~~~php
public function getStatusField($value)
{
    return strtoupper((string) $value);
}
~~~

A `get{Slug}Field($value)` method transforms the resolved value. A `set{Slug}Field($value)` method consumes the submitted value instead of sending that field to a table column or ordinary meta write. Use the actual StudlyCase slug in the method name.

<a id="routes-and-pages"></a>
## Routes and page components

Aura registers resource routes inside the web and auth middleware configured at `aura-settings.middleware.aura-admin`. The prefix is `config('aura.path')`, which defaults to admin. `config('aura.domain')` can restrict the routes to a domain.

For a resource with slug article, the generic route names and paths are:

| Route name | Method and path |
|---|---|
| `aura.article.index` | GET /admin/article |
| `aura.article.create` | GET /admin/article/create |
| `aura.article.edit` | GET /admin/article/{id}/edit |
| `aura.article.view` | GET /admin/article/{id} |

Use the configured prefix when `aura.path` is changed. The built-in Attachment resource has only `aura.attachment.index`, which points to the media page. It has no generic create, edit, or view routes.

The URL helpers guard missing routes:

~~~php
$article->indexUrl();
$article->createUrl();
$article->editUrl();
$article->viewUrl();
~~~

`indexUrl()` and `createUrl()` return null when their route is absent. `editUrl()` and `viewUrl()` also return null for an unsaved model. `getIndexRoute()` calls Laravel's `route()` helper without a route-existence check and throws when the index route is absent.

Override a static component hook to replace one page while keeping its URI and route name:

~~~php
public static function indexComponent(): string;
public static function createComponent(): string;
public static function editComponent(): string;
public static function viewComponent(): string;
~~~

The defaults are `Aura\Base\Livewire\Resource\Index`, Create, Edit, and View. A custom edit or view component receives `mount($id, $slug = null)`. Index and create components receive `mount($slug = null)`.

~~~php
namespace App\Livewire;

use Aura\Base\Livewire\Resource\View as BaseView;

class ViewArticle extends BaseView
{
    public function mount($id, $slug = null)
    {
        parent::mount($id, $slug ?? 'article');
    }

    public function render()
    {
        return view('aura.article.view')
            ->layout('aura::components.layout.app');
    }
}
~~~

Point the resource hook at the custom component:

~~~php
public static function viewComponent(): string
{
    return \App\Livewire\ViewArticle::class;
}
~~~

Use `php artisan aura:customize Article view --mode=full` to generate a component and view. The command accepts page types index, create, edit, and view, and modes full, view, and component. To change only markup, override `indexView()`, `createView()`, `editView()`, `viewView()`, `editHeaderView()`, `viewHeaderView()`, `rowView()`, or `tableComponentView()`.

The Resource Editor route is `aura.resource.editor`. It is available only in the local or testing environment when `aura.features.resource_editor` is enabled, and it requires a Super Admin. It is not a production resource-management route.

<a id="navigation"></a>
## Navigation and icons

`navigation()` returns the sidebar data built from `pluralName()`, `getSort()`, `getGroup()`, `getDropdown()`, `getShowInNavigation()`, `getIndexRoute()`, and `icon()`. A missing index route therefore breaks navigation generation for that resource.

Override the navigation values with the static properties or these methods:

~~~php
public static function getGroup(): ?string;
public static function getSort(): ?int;
public static function getDropdown();
public static function getShowInNavigation(): bool;
public static function getContextMenu();

public function getIcon();
public function icon();
public function getBadge();
public function getBadgeColor();
~~~

`icon()` delegates to `getIcon()`. The base `getIcon()` uses `$icon` when set and otherwise returns Aura's default SVG.

<a id="table"></a>
## Table configuration

Table configuration methods are instance methods:

~~~php
public function defaultPerPage();              // 10
public function defaultTableSort();            // id
public function defaultTableSortDirection();  // desc
public function defaultTableView();            // list
public function showTableSettings();            // true
public function tableView();                   // list view name
public function tableRowView();                // row view name
public function tableGridView();               // false, or a view name
public function tableKanbanView();             // false, or a view name
public function kanbanQuery($query);           // false
public function kanbanSettings(): array;
public function indexTableSettings();          // []
~~~

The default Kanban settings use status as group_field, title as card_title, no explicit order, and show empty columns. Set enabled, group_field, columns, card_title, card_subtitle, order_by, and show_empty_columns in `kanbanSettings()`.

The table uses input fields with on_index enabled for its headers and always prepends an ID column. It also prepends title when `usesTitle()` is true. Field classes provide filter behavior. Define an optional `indexQuery($query, $table = null)` method to constrain the index query before field filters run:

~~~php
public function indexQuery($query, $table = null)
{
    return $query->where('status', 'published');
}
~~~

`display($slug)` resolves a value through the field class's display method. `getHeaders()` returns the visible header map. `isNumberField($slug)` checks whether the field class is Aura's Number field.

<a id="widgets"></a>
## Widgets

Return widget definitions from `getWidgets(): array`. Aura renders them above the resource table.

~~~php
public static function getWidgets(): array
{
    return [
        [
            'type' => \Aura\Base\Widgets\ValueWidget::class,
            'name' => 'Total articles',
            'slug' => 'total-articles',
            'method' => 'count',
            'style' => ['width' => 50],
        ],
    ];
}
~~~

The date range available to widgets comes from the resource's `$widgetSettings` array. See [Widgets](/docs/widgets) for widget-specific options.

<a id="actions"></a>
## Actions

A row action key is the method name Aura calls on the resource. Define row actions with an `actions()` method or the public `$actions` array. `getActions()` uses the method when it exists and otherwise falls back to the property.

~~~php
public function actions(): array
{
    return [
        'publish' => [
            'label' => 'Publish',
            'ability' => 'update',
            'conditional_logic' => fn () => auth()->user()->hasRole('editor'),
        ],
        'delete' => [
            'label' => 'Delete',
            'ability' => 'delete',
            'icon-view' => 'aura::components.actions.trash',
            'confirm' => true,
            'confirm-title' => 'Delete article?',
            'confirm-content' => 'Are you sure you want to delete this article?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
    ];
}

public function publish(): void
{
    $this->update(['status' => 'published']);
}
~~~

Supported row-action options are label, description, icon for raw SVG, icon-view for an included Blade view, class, onclick, conditional_logic, confirm, confirm-title, confirm-content, confirm-button, and confirm-button-class. The ability option is used by table mutation authorization. Resource-page `singleAction()` authorizes update unless `allowedToPerformActions()` returns true, then checks that the action is declared and its conditional closure allows it.

Bulk actions use `bulkActions()` or the public `$bulkActions` array. The method takes precedence over the property:

~~~php
public array $bulkActions = [
    'deleteSelected' => [
        'label' => 'Delete',
        'ability' => 'delete',
    ],
];
~~~

The table authorizer requires the action to be declared and checks its Gate ability for every selected row in the current table query. Built-in ability mappings cover delete, deleteAttachment, deleteSelected, forceDelete, restore, update, view, and edit. A custom table action must set ability. Selections are capped at 500 rows.

Set `method => collection` to call the model action once with the selected IDs. Set `modal => modal-name` to open a modal with the authorized IDs.

<a id="permissions"></a>
## Permissions and scopes

Create the standard permissions for registered resources with:

~~~bash
php artisan aura:create-resource-permissions
php artisan aura:create-resource-permissions --team=3
~~~

The optional --team value is a numeric team ID. Without it, the job uses the authenticated user's current team when teams are enabled. It creates these eight abilities for each eligible registered resource:

view-{slug}, viewAny-{slug}, create-{slug}, update-{slug}, restore-{slug}, delete-{slug}, forceDelete-{slug}, and scope-{slug}.

The all-resources job skips Team and its subclasses. Custom actions do not create new permission rows. Map table actions to an existing Gate ability with the ability option.

`Aura\Base\Policies\ResourcePolicy` applies the standard abilities:

~~~blade
@can('create', App\Aura\Resources\Article::class)
    <a href="{{ app(App\Aura\Resources\Article::class)->createUrl() }}">
        New article
    </a>
@endcan
~~~

Setting `$createEnabled`, `$editEnabled`, `$viewEnabled`, or `$indexViewEnabled` to false denies the matching policy ability. Super Admins and Global Admins have blanket resource access, except that a team Super Admin cannot mutate a Global Role. A user with the scope-{slug} permission and the matching read or write ability is restricted to rows whose user_id is their own.

When `config('aura.teams')` is true, TeamScope restricts most resource queries to the active team. User queries use team membership, the Team resource itself is left unscoped, and Role queries include the team's roles and global roles. When teams are disabled, Aura does not register team resources and does not apply team filtering.

<a id="relationships"></a>
## Relationships

Every Resource provides these Eloquent relationships:

~~~php
$article->meta();     // morphMany when $usesMeta is true
$article->user();     // belongsTo the configured user resource
$article->team();     // belongsTo the configured team resource
$article->parent();   // belongsTo the same resource through parent_id
$article->children(); // hasMany the same resource through parent_id
~~~

When `$usesMeta` is false, `meta()` returns no relationship. Custom tables need the columns required by any inherited relationship you use.

A relationship field can point to another resource:

~~~php
[
    'name' => 'Author',
    'slug' => 'author_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => 'App\\Aura\\Resources\\Author',
    'on_forms' => true,
]
~~~

HasMany, Tags, and AdvancedSelect use relation field behavior, so `Resource::__call()` can expose their configured relation as a method. BelongsTo is an input field and stores the foreign key; it does not create a magic relation method. Use ordinary Eloquent relationships when you need a named belongsTo relation.

<a id="lifecycle"></a>
## Save lifecycle

Resource saves use one saving listener followed by one saved listener.

1. Aura fills initial posts values when applicable: title, content, user_id, team_id, type, and slug.
2. Aura packs submitted input fields into the transient fields attribute.
3. Aura applies field setters and field-class `set()`, `saving()`, and `shouldSkip()` hooks, then routes each value to a table column or a queued meta write.
4. The saved listener writes queued meta values, calls field-class `saved()` hooks, and fires the metaSaved model event.

A field definition can also provide a set closure. The model-level `set{Slug}Field()` method is useful when a resource owns a non-column payload or needs to transform a submitted value before storage.

<a id="built-in-resources"></a>
## Built-in resources and soft deletes

The built-in resources use these storage profiles:

| Resource | Slug | Storage | Notes |
|---|---|---|---|
| `Aura\Base\Resources\User` | user | Custom users table with meta | The authenticatable user and team membership resource. |
| `Aura\Base\Resources\Role` | role | Custom roles table without meta | Stores global and team roles. |
| `Aura\Base\Resources\Permission` | permission | Custom permissions table without meta | Stores generated permissions. |
| `Aura\Base\Resources\Team` | team | Custom teams table with meta | Registered only when teams are enabled and uses SoftDeletes. |
| `Aura\Base\Resources\TeamInvitation` | teaminvitation | Shared posts table | Registered only when teams are enabled and cannot be created through the resource policy. |
| `Aura\Base\Resources\Attachment` | attachment | Shared posts table | Uses the dedicated media index route. |
| `Aura\Base\Resources\Option` | option | Custom options table | Stores Aura options. |

The base Resource does not use Laravel's SoftDeletes trait and has no `$softDeletes` flag. Add the trait to a resource when the table has a deleted_at column:

~~~php
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Resource
{
    use SoftDeletes;
}
~~~

<a id="related"></a>
## Related pages

- [Creating resources](/docs/creating-resources) covers the generator and first field.
- [Fields](/docs/fields) lists field types and options.
- [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) cover storage migrations and queries.
- [Table](/docs/table) covers list, grid, and Kanban configuration.
- [Widgets](/docs/widgets) covers index-page metrics.
- [Customizing views](/docs/customizing-views) covers Blade overrides and aura:customize.
- [Roles and permissions](/docs/roles-permissions) covers role and team access.
- [Testing](/docs/testing) covers Livewire resource tests.
