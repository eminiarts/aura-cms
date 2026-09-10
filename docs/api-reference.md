# API reference

This page documents the PHP APIs available to applications built with Aura CMS.
It covers the Aura facade, resource models, field classes, query helpers, and
the admin routes used by relationship fields.

Aura does not provide a public CRUD REST API or token-authentication
endpoints. Its resource pages use session-authenticated web routes and
Livewire. If an application needs an HTTP API, the application must define the
routes, authentication guard, authorization policy, and response format. A
host-written example is included at the end of this page.

## The Aura facade

Use the Aura facade to access the package singleton from application or package
code. The facade forwards calls to `Aura\Base\Aura`:

~~~php
use Aura\Base\Facades\Aura;
~~~

### Resource, field, and widget registries

Aura registers built-in resources and scans the configured application
directories during boot. Registration methods accept class strings:

~~~php
// Registered resource class names.
$resources = Aura::getResources();

// Add resources from a package or another application directory.
Aura::registerResources([
    \App\Aura\Resources\Product::class,
    \App\Aura\Resources\Category::class,
]);

// Resolve a registered resource by class name, declared slug, or class name.
$resource = Aura::findResourceBySlug('product'); // Resource instance or null

// Classes found under aura-settings.paths.resources.
$appResources = Aura::getAppResources();
~~~

Set the resource directory and namespace in the `aura-settings.paths.resources`
configuration, using its `path` and `namespace` keys. Aura discovers only classes
that extend `Aura\Base\Resource`.

The field and widget registries use the same pattern:

~~~php
$fieldClasses = Aura::getFields();
$fieldGroups = Aura::getFieldsWithGroups();

Aura::registerFields([
    \App\Aura\Fields\ColorPicker::class,
]);

$appFields = Aura::getAppFields();

$widgetClasses = Aura::getWidgets();
Aura::registerWidgets([
    \App\Aura\Widgets\SalesChart::class,
]);
$appWidgets = Aura::getAppWidgets();
~~~

`getFieldsWithGroups()` returns an array keyed by the field class's
`$optionGroup`. Each group maps a fully qualified class name to its basename:

~~~php
[
    'Fields' => [
        'Aura\\Base\\Fields\\Text' => 'Text',
    ],
    'Relationship Fields' => [
        'Aura\\Base\\Fields\\BelongsTo' => 'BelongsTo',
    ],
]
~~~

Field discovery reads `config('aura-settings.paths.fields.path')`. Widget
discovery reads `config('aura-settings.widgets.path')`. Use the registration
methods from a service provider when a package keeps its classes outside the
configured application directories.

### Configuration and stored options

To read the package configuration, use `options()` for the full array or
`option($key)` for a top-level value. Both read the `aura` configuration.
Use Laravel's config helper for nested values, since the facade does not parse
dot notation:

~~~php
$allConfig = Aura::options();
$teamsEnabled = Aura::option('teams');

// For nested configuration, use Laravel's config helper.
$globalSearch = config('aura.features.global_search');
~~~

Stored options are different from package configuration. `getOption()` reads a
value from the `options` table and returns an empty array when no value exists.
With teams enabled, it reads the current team's option. Reads are cached for one
hour. `updateOption()` writes the value and invalidates the facade cache keys:

~~~php
$settings = Aura::getOption('settings') ?: [];

Aura::updateOption('settings', [
    ...$settings,
    'support_email' => 'support@example.test',
]);
~~~

Call these methods after authenticating the user and establishing the current
team when teams are enabled. `updateOption()` does not authorize the caller.
Authorize the operation in the controller, policy, or Livewire action. See
[Settings](/docs/settings) for the built-in settings option and
[Teams](/docs/teams) for team context.

### Routes, navigation, and view injection

`registerRoutes($slug, $resource = null)` registers four session-authenticated
Livewire routes under `config('aura.path')`. The optional resource argument can
be a resource class string or an object and supplies custom page components:

~~~php
Aura::registerRoutes('products', \App\Aura\Resources\Product::class);

Aura::clearRoutes(); // Refresh in-memory route name and action lookups.
Aura::clear();       // Refresh route lookups and flush the application cache.
~~~

The package registers application resources during boot, so most applications
do not call `registerRoutes()` themselves. See [Resources](/docs/resources) for
the resource component hooks.

`navigation()` returns a permission-filtered `Collection` of navigation
entries. `getInjectViews()` returns registered view callbacks. Register and
render a callback like this:

~~~php
Aura::registerInjectView('resource.edit.top', function () {
    return view('partials.edit-banner')->render();
});
~~~

~~~blade
{!! Aura::injectView('resource.edit.top') !!}
~~~

The callback is called through Laravel's container and the rendered output is
returned as an `Htmlable`.

### Assets and templates

Use the view helpers in a layout that includes Aura's assets:

~~~blade
{!! Aura::styles() !!}
{!! Aura::scripts() !!}
~~~

`viteStyles()` and `viteScripts()` select Aura's Vite hot file and build
directory for local package development. `assetsAreCurrent()` returns a
boolean after comparing the published manifest and referenced files. It throws
a `RuntimeException` when a required manifest is missing or invalid.

`templates()` returns a cached collection of application template file names
under `app_path('Aura/Templates')`. `findTemplateBySlug($slug)` resolves a
class under `Aura\Base\Templates`:

~~~php
$templates = Aura::templates();
$plain = Aura::findTemplateBySlug('Plain');
$fields = $plain->getFields();
~~~

The lookup converts kebab-case and snake_case slugs to StudlyCase. It also
accepts existing class basenames, such as `PanelWithSidebar`. For example:

~~~php
$template = Aura::findTemplateBySlug('panel-with-sidebar');
$fields = $template->getFields();
~~~

The Resource Editor uses these template classes. See
[Resource Editor](/docs/resource-editor).

### Other facade helpers

The facade also exposes these focused helpers:

~~~php
// A reusable default field definition, or null when the key is unknown.
$createdAt = Aura::fields('created_at');

// Conditional logic and its cache.
$visible = Aura::checkCondition($model, $field, $post);
Aura::clearConditionsCache();

// Resolve a configured attachment URL by id.
$url = Aura::getPath($attachmentId);

// Configure the user model used by Aura resources.
$class = Aura::userModel();
Aura::useUserModel(\App\Models\User::class);

// Register immutable record-layout panels during application boot.
Aura::registerRecordLayoutPanels('vendor/package', $panels);
~~~

See [Record layouts](/docs/record-layouts) for the panel contract. `flushState()`
resets process-level registrations and caches to the boot baseline. Aura calls
it around queue work and, when available, Octane request boundaries.

## The resource contract

`Aura\Base\Resource` is an Eloquent model that implements
`Aura\Base\Contracts\DefinesFields` and
`Aura\Base\Contracts\TableResource`. A resource declares its identity and
returns field-definition arrays from a static `getFields()` method:

~~~php
namespace App\Aura\Resources;

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class Post extends Resource
{
    public static string $type = 'Post';
    public static ?string $slug = 'post';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
                'validation' => 'required|max:255',
                'searchable' => true,
                'on_index' => true,
            ],
        ];
    }
}
~~~

The `type` value in a field definition must be a fully qualified field class
name. Aura does not provide fluent field builders. See
[Creating resources](/docs/creating-resources) and [Fields](/docs/fields) for
the resource properties and field options.

The `DefinesFields` contract is:

~~~php
public static function getFields(): array;
~~~

`TableResource` extends that contract and requires the methods shared by the
resource table:

~~~php
public function fieldBySlug($slug);
public function fieldClassBySlug($slug);
public function getActions();
public function getBulkActions();
public function isMetaField($key): bool;
public function isTableField($key): bool;
~~~

`Aura\Base\BaseResource` implements the same contracts for models whose fields
live in physical columns on their own table. It uses the shared field and table
helpers but does not resolve values from Aura's meta relation.

### Identity, storage, and relationships

A resource exposes its names, type, slug, and record title through the following
methods. The slug becomes the admin URL segment and route-name segment.

| Identity | Methods |
| --- | --- |
| Names | `getName()`, `getPluralName()`, `singularName()`, `pluralName()` |
| Slug and type | `getSlug()`, `getType()` |
| Record title | `title()` |

The storage flags are independent:

~~~php
Post::usesCustomTable(); // false by default
Post::usesMeta();        // true by default

$post->isTableField('title');
$post->isMetaField('subtitle');
$post->getMeta();         // Collection of decoded and field-cast values
$post->getMeta('subtitle');
~~~

In the default posts-plus-meta mode, base fillable values use the shared
`posts` table and other input fields use `meta`. A custom-table resource can
store all input fields in its own columns with `$customTable = true` and
`$usesMeta = false`, or combine its own columns with meta storage. See
[Meta fields](/docs/meta-fields) and [Custom tables](/docs/custom-tables) for
the storage matrix and migrations.

Resource models provide user, team, parent, and children relations through
methods with those names, where the corresponding configuration is available.
Field classes can add dynamic Eloquent relations when `isRelation()` returns
true. This includes has-many, has-one, tags, roles, and polymorphic advanced
select fields.

A belongs-to field stores its foreign id as a scalar field value. It does not
add a dynamic Eloquent relation to the resource.

### Field access and validation

These helpers expose the raw definitions, processed definitions, and resolved
values:

~~~php
$post = new Post;

$post->fieldsCollection();        // Cached raw definitions, as a Collection.
$post->fieldBySlug('title');      // Definition array or null.
$post->fieldClassBySlug('title'); // Field instance or false.
$post->getFieldSlugs();           // Collection of all definition slugs.

$post->inputFields();             // Processed input fields.
$post->inputFieldsSlugs();        // Array of input slugs.
$post->indexFields();             // Input fields not marked on_index=false.
$post->createFields();
$post->editFields();
$post->viewFields();
$post->getFieldsWithIds();
$post->getGroupedFields();

$post->fields;                    // Conditional-logic filtered value map.
$post->getFieldsWithoutConditionalLogic();
$post->getSearchableFields();

$post->validationRules();
$post->resourceFieldValidationRules(); // form.fields.* keys for Livewire.
~~~

`clearFieldsAttributeCache()` clears the computed field and normalized meta
values for a model instance. `Resource::flushFieldCache()` clears process-static
field-definition caches after a resource definition changes in a long-running
process.

The `display($key)` method resolves a value through the field's display
transformation. `displayFieldValue($key, $value)` applies the same field-level
display logic to a value supplied by the caller:

~~~php
$label = $post->display('title');
$label = $post->displayFieldValue('title', 'Draft');
~~~

Resource dynamic property access checks real Eloquent attributes and relations
before computed field values. A non-null real attribute, including `0`, `false`,
or an empty string, wins over a field definition. Relation fields then resolve
through the field class, followed by the computed `fields` map.

### Meta query scopes and search

`AuraQueriesMeta` queries values stored in `meta`. These examples assume declared text fields such as `category` and `subtitle`, a Boolean `featured` field, and a JSON `topics` field:

~~~php
Post::whereMeta('category', 'news')->get();
Post::whereMeta('subtitle', 'like', 'Aura%')->get();
Post::whereMeta([
    'category' => 'news',
    'featured' => true,
])->get();

Post::orWhereMeta('category', 'updates')->get();
Post::whereInMeta('category_id', [1, 2, 3])->get();
Post::whereNotInMeta('category_id', [4, 5])->get();
Post::whereMetaContains('topics', 'laravel')->get();
~~~

`whereMeta()` and `orWhereMeta()` accept `key, value`, `key, operator,
value`, or one associative array. `whereInMeta()` and `whereNotInMeta()` accept
an array, collection, or scalar. `whereMetaContains()` checks a JSON meta value.

Aura also registers the `searchIn($columns, $search, $model)` Eloquent builder
macro. It searches physical columns directly and uses a correlated meta query
for meta-backed fields:

~~~php
$post = new Post;
$columns = $post->getSearchableFields()->pluck('slug')->all();

$results = Post::query()
    ->searchIn($columns, request('q'), $post)
    ->paginate(15);
~~~

Use this macro when a search includes both table and meta fields. A loop of
plain `orWhere()` calls does not search the meta table.

### URLs, views, actions, and table settings

Resource URL helpers use the `aura.{slug}.*` route names:

~~~php
$post->indexUrl();
$post->createUrl();
$post->editUrl();
$post->viewUrl();
$post->getIndexRoute();
~~~

The first four helpers return `null` when the route is missing. The edit and
view helpers also return `null` for unsaved resources. `getIndexRoute()` calls
Laravel's `route()` helper directly and therefore throws if the index route does
not exist.

The resource view methods return Blade view names:

~~~php
$post->indexView();
$post->createView();
$post->editView();
$post->viewView();
$post->editHeaderView();
$post->viewHeaderView();
$post->rowView();
$post->tableComponentView();
~~~

Use these methods to configure the resource table:

| Setting | Methods |
| --- | --- |
| Pagination | `defaultPerPage()` |
| Default sorting | `defaultTableSort()`, `defaultTableSortDirection()` |
| Default view | `defaultTableView()` |
| View templates | `tableView()`, `tableGridView()`, `tableKanbanView()` |
| Kanban query and settings | `kanbanQuery()`, `kanbanSettings()` |
| Settings visibility | `showTableSettings()` |
| Headers and index settings | `getHeaders()`, `indexTableSettings()` |

The grid and Kanban view methods return a Blade view name or `false`, rather
than a boolean enable flag. See [Table](/docs/table).

`getActions()` and `getBulkActions()` read an `actions` or `bulkActions`
method when present, otherwise the corresponding public array. The default
`allowedToPerformActions()` returns `false`, so a resource that exposes actions
must implement its authorization rules.

`navigation()` returns the resource's icon, slug, route, group, sort order,
badge, and visibility values. Override `getIcon()`, `getBadge()`, or
`getBadgeColor()` when the navigation entry needs custom values.

### Optional resource contracts

Reporting accepts an explicit scope allowlist. A resource that implements
`DeclaresReportingQueryScopes` must return the names of no-argument Eloquent
scopes that the reporting engine may call:

~~~php
use Aura\Base\Contracts\DeclaresReportingQueryScopes;

class Post extends Resource implements DeclaresReportingQueryScopes
{
    public static function reportingQueryScopes(): array
    {
        return ['published'];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
~~~

The reporting service authorizes `viewAny`, applies the resource's `indexQuery`
when present, and keeps the normal resource scopes. It rejects scope names that
are not returned by `reportingQueryScopes()`.

## The field contract

Every field class extends `Aura\Base\Fields\Field`, which implements Livewire's
`Wireable` contract. A resource field is an array. Aura resolves its `type` to
the field class through the container.

The base class exposes properties that control field behavior:

| Property | Meaning |
| --- | --- |
| `$type` | `input`, `relation`, `repeater`, `group`, or another field type. |
| `$optionGroup` | Group label in the field picker. |
| `$edit` and `$view` | Blade views for editing and viewing a value. |
| `$index` | Optional Blade component for index output. |
| `$on_forms` | Whether the field is available in forms. |
| `$tableColumnType` and `$tableNullable` | Defaults used for custom-table schema generation. |
| `$taxonomy` | Whether the field is a taxonomy field. |
| `$rawHtmlDisplay` | Whether the field intentionally returns trusted HTML. |

Resources and tables use the following field methods to resolve values and
render the interface. See [Creating fields](/docs/creating-fields) for a complete
custom field example.

| Purpose | Methods |
| --- | --- |
| Read and format values | `get($class, $value, $field = null)`, `display($field, $value, $model)`, `value($value)` |
| Identify field behavior | `isInputField()`, `isRelation()`, `isTaxonomyField()` |
| Provide filters | `filterOptions()`, `getFilterValues($model, $field)` |
| Check disabled state | `isDisabled($model, $field)` |
| Resolve editing and viewing templates | `edit()`, `view()` |

### Field lifecycle hooks

Aura calls these hooks when the corresponding field behavior exists:

| Hook | Signature | Use |
| --- | --- | --- |
| `set` | `set($post, $field, $value)` | Transform submitted data before it is routed to a column, meta row, or relation. |
| `saving` | `saving($post, $field, $value)` | Adjust the model before it is saved. A returned model replaces the current model for the remainder of the hook. |
| `saved` | `saved($post, $field, $value)` | Persist relations or other data after the model row is saved. |
| `get` | `get($class, $value, $field = null)` | Cast a stored value when Aura reads it. |
| `display` | `display($field, $value, $model)` | Format a value for a table or record page. |
| `api` | `api($request)` | Respond to the internal relationship-field option request. |

These hooks are conventions checked with `method_exists`; they are not methods
declared abstract on the base class. When a resource does not intercept the
slug with `set{StudlySlug}Field()`, a field definition may provide a `set`
closure. Aura calls that closure before the field class's `set()` hook:

~~~php
[
    'name' => 'Slug',
    'slug' => 'slug',
    'type' => \Aura\Base\Fields\Text::class,
    'set' => fn ($post, $field, $value) => \Illuminate\Support\Str::slug($value),
]
~~~

Resources can intercept a field with `get{StudlySlug}Field($value)` and
`set{StudlySlug}Field($value)`. The setter can consume a custom payload that is
not a regular Aura field.

### Relation fields and table loading

Aura identifies relation fields through `Field::isRelation()`. The default
implementation checks whether the field's `$type` is `relation`, as it is for
has-many and has-one fields. Tags and roles override this method.

Advanced select fields count as relations unless `polymorphic_relation` is
`false`. Belongs-to fields remain input fields and store a scalar foreign id.

A relation field that supports table eager loading may implement
`ProvidesTableEagerLoad`:

~~~php
public function tableEagerLoad(array $field): string|array|null;
~~~

A field that can batch-resolve display values after pagination may implement
`PreloadsTableDisplay`:

~~~php
use Illuminate\Database\Eloquent\Collection;

public function preloadTableDisplay(Collection $rows, array $field): void;
~~~

The table calls these optional contracts only when the field implements them.
Keep the resource's normal team, type, and authorization scopes in any custom
query. See [Table](/docs/table) for the rendering and eager-loading behavior.

## Conditional logic

`Aura\Base\ConditionalLogic` evaluates a field's `conditional_logic`
definition. Conditions can be arrays, closures, or role checks:

~~~php
use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;

$visible = ConditionalLogic::shouldDisplayField($model, $field, $post);
$visible = ConditionalLogic::checkCondition($model, $field, $post);
$roleVisible = ConditionalLogic::fieldIsVisibleTo($field, $user);

// The facade delegates to shouldDisplayField().
$visible = Aura::checkCondition($model, $field, $post);

ConditionalLogic::clearConditionsCache();
~~~

The supported array operators are `==`, `!=`, `>`, `>=`, `<`, and `<=`. The
package also registers `@checkCondition($model, $field, $post)` for Blade
templates:

~~~blade
@checkCondition($model, $field, $post)
    {{-- Render the field. --}}
@endcheckCondition
~~~

## Admin routes and the internal fields endpoint

Aura's resource pages are web routes protected by the configured
`aura-admin` middleware. The default stack is `web` and `auth`. If `aura.path`
is `admin` and a resource slug is `post`, the routes are:

| Route name | Method and path |
| --- | --- |
| `aura.post.index` | `GET /admin/post` |
| `aura.post.create` | `GET /admin/post/create` |
| `aura.post.edit` | `GET /admin/post/{id}/edit` |
| `aura.post.view` | `GET /admin/post/{id}` |

The prefix comes from `config('aura.path')`, and `config('aura.domain')` can
restrict the routes to a host. Attachment uses a dedicated
`aura.attachment.index` media route and does not receive generic create, edit,
or view routes.

For resource data, the package registers one internal JSON endpoint. It is used
by relationship fields to load selectable values:

~~~
POST {config('aura.path')}/api/fields/values
Route name: aura.api.fields.values
Middleware: aura-admin (web, auth)
~~~

The controller requires `model`, `slug`, and `field` request values:

| Key | Requirement |
| --- | --- |
| `model` | A class string that extends `Aura\Base\Resource`. The caller must pass the `viewAny` policy check for it. |
| `field` | A class string that extends `Aura\Base\Fields\Field` and provides an `api()` method. |
| `slug` | The field slug used by the requesting form. |
| `search` | Search text consumed by fields that support search. |
| `id` | A selected id that a field may append to its result. |
| `page` | Page number used by `AdvancedSelect`. |
| `fullField` | The complete field definition used by `AdvancedSelect` to render option markup. |

The response is field-specific. `BelongsTo::api()` returns id/title rows.
`AdvancedSelect::api()` returns paged rows with option and selected-item view
markup. The endpoint is an implementation detail of the admin UI. It is not a
stable public API and it does not issue bearer tokens.

## Building a host API

The following route belongs in the host application. Aura does not register
`auth:sanctum`, the `/api/posts` path, or the controller logic. Replace the
guard and policy with the host application's choices when they differ:

~~~php
// routes/api.php
use App\Aura\Resources\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/posts', function (Request $request) {
    $validated = $request->validate([
        'q' => ['nullable', 'string', 'max:255'],
        'status' => ['nullable', 'string', 'max:20'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
    ]);

    $user = $request->user();
    $resource = app(Post::class);

    Gate::forUser($user)->authorize('viewAny', $resource);

    if (config('aura.teams') && ! $user->current_team_id) {
        abort(403, 'Select a team before reading posts.');
    }

    $post = new Post;
    $query = Post::query();

    if ($search = $request->string('q')->toString()) {
        $columns = $post->getSearchableFields()->pluck('slug')->all();
        $query->searchIn($columns, $search, $post);
    }

    if ($status = $request->string('status')->toString()) {
        $query->where('status', $status);
    }

    return $query->paginate($validated['per_page'] ?? 15)
        ->through(fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => $post->title,
            'status' => $post->status,
        ]);
});
~~~

The example checks permission to view the collection and requires a current
team when teams are enabled. It also preserves the normal Eloquent scopes,
which filter by team, resource type, and access according to the installation
and the authenticated user's permissions. Aura registers its default
`ResourcePolicy` for resources, but your application may replace it with its
own policy.

List response fields explicitly, as the example does. Returning raw Aura
resources can include the appended `fields` collection. The status filter uses
an ordinary column query because `status` is a core column on the posts table.

If an endpoint must read across teams, define that ability explicitly and
authorize it before considering any `withoutGlobalScope()` call. Do not expose
the internal fields endpoint as a public content API.

## Related guides

- [Resources](/docs/resources) and [Creating resources](/docs/creating-resources)
- [Fields](/docs/fields) and [Creating fields](/docs/creating-fields)
- [Meta fields](/docs/meta-fields) and [Custom tables](/docs/custom-tables)
- [Table](/docs/table), [Teams](/docs/teams), and [Configuration](/docs/configuration)
- [Record layouts](/docs/record-layouts) and [Resource Editor](/docs/resource-editor)
