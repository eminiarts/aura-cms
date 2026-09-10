# Best practices

This guide collects conventions that are specific to Aura CMS. It assumes that
you already know Laravel and Eloquent. Use the linked pages for complete field,
Resource, table, team, and storage references.

<a id="coding-standards"></a>
## Keep Resource definitions explicit

An Aura Resource is an Eloquent record class with a static definition. Extend
<code>Aura\Base\Resource</code> and keep application Resources under the path
configured in <code>config/aura-settings.php</code>:

~~~bash
php artisan aura:resource Product
~~~

The generated class is placed in <code>app/Aura/Resources</code> with the
default <code>App\Aura\Resources</code> namespace:

~~~php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';

    public static ?string $slug = 'product';

    protected static ?string $group = 'Shop';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'searchable' => true,
            ],
        ];
    }
}
~~~

Use the generated declarations as the type reference when overriding inherited
static properties. Do not add a new type to an inherited untyped property such
as <code>$customTable</code>. PHP rejects an incompatible property declaration
before Aura can load the Resource.

The settings that affect a Resource most often are:

| Setting | Effect |
| --- | --- |
| <code>$type</code> | Type discriminator for rows stored in the shared <code>posts</code> table. |
| <code>$slug</code> | URL and route-name segment. |
| <code>$singularName</code> and <code>$pluralName</code> | Navigation and page labels. |
| <code>$group</code> and <code>$sort</code> | Navigation group and order. Lower sort values appear first. |
| <code>$globalSearch</code> | Includes the Resource in database-backed global search. |
| <code>$customTable</code> | Uses the Resource's <code>$table</code> instead of <code>posts</code>. |
| <code>$usesMeta</code> | Allows fields without a table column to use the <code>meta</code> table. |
| <code>$createEnabled</code>, <code>$editEnabled</code>, <code>$viewEnabled</code>, <code>$indexViewEnabled</code> | Gates the matching Resource policy ability. |

These flags configure Aura's Resource and policy pipeline. They do not replace
the host application's Eloquent relationships, casts, scopes, or migrations.
See [Resources](/docs/resources) for the full configuration reference.

<a id="field-development"></a>
## Define fields as arrays

<code>getFields(): array</code> returns plain arrays. Aura resolves the class
named by <code>type</code> through the container. Use a fully qualified field
class string:

~~~php
public static function getFields(): array
{
    return [
        [
            'name' => 'Status',
            'slug' => 'status',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => [
                'draft' => 'Draft',
                'published' => 'Published',
            ],
            'validation' => 'required|in:draft,published',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => true,
        ],
        [
            'name' => 'Category',
            'slug' => 'category_id',
            'type' => 'Aura\\Base\\Fields\\BelongsTo',
            'resource' => 'App\\Aura\\Resources\\Category',
        ],
    ];
}
~~~

The field pipeline validates <code>type</code> and <code>slug</code> and then
maps each definition to its field class. Include <code>name</code> for the
label used by forms and tables. Use stable lower-case slugs, usually in
<code>snake_case</code>. A slug is a storage key and a dynamic Resource
attribute. Changing a meta slug changes the meta key. Changing a custom-table
slug changes the column that Aura expects.

Use these keys for the common Resource concerns:

- <code>validation</code> supplies the form rules used by Aura's Resource form.
  It can be a Laravel rule string or an array.
- <code>searchable</code> opts the field into table search and global search.
  Search is field-level. Aura does not use a separate static
  <code>$searchable</code> property.
- <code>on_index</code>, <code>on_forms</code>, and <code>on_view</code> control
  the matching presentation. <code>on_create</code> and <code>on_edit</code>
  refine form visibility.
- <code>default</code> supplies an initial form value.
- <code>conditional_logic</code> controls field visibility when the field
  supports it.
- <code>Tab</code> and <code>Panel</code> group the fields that follow them.

Presentation keys do not authorize a write. Keep validation and policy checks
in place when a field is hidden from a form. See [Fields](/docs/fields) for
the built-in field catalogue and its options.

<a id="database-design"></a>
## Choose storage deliberately

<code>$customTable</code> and <code>$usesMeta</code> are independent flags. The
four combinations have different write paths:

| <code>$customTable</code> | <code>$usesMeta</code> | Field storage |
| --- | --- | --- |
| <code>false</code> | <code>true</code> | Base fillable attributes use <code>posts</code>. Other input fields use <code>meta</code>. |
| <code>false</code> | <code>false</code> | Base fillable attributes use <code>posts</code>. Other input fields have no meta destination. |
| <code>true</code> | <code>true</code> | Base fillable attributes use the custom table. Other input fields use <code>meta</code>. |
| <code>true</code> | <code>false</code> | Every input field slug must be a column on the custom table. |

The default is shared <code>posts</code> plus <code>meta</code>. Base fillable
attributes include Aura's core columns such as <code>title</code>,
<code>content</code>, <code>type</code>, <code>status</code>, <code>slug</code>,
<code>user_id</code>, <code>parent_id</code>, <code>order</code>, team and
timestamp columns. The exact fillable list belongs to the Resource class and
its schema.

Use <code>isTableField($slug)</code> and <code>isMetaField($slug)</code> when
code needs to know the selected destination. Do not infer the destination from
<code>$customTable</code> alone.

A custom table with no meta storage requires a physical column for every input
field:

~~~php
class Product extends Resource
{
    public static $customTable = true;

    public static bool $usesMeta = false;

    protected $table = 'products';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Price',
                'slug' => 'price',
                'type' => 'Aura\\Base\\Fields\\Number',
            ],
        ];
    }
}
~~~

The generated custom stub uses this column-backed mode. It does not create the
database table. Create and review a migration before saving records. In
custom-table plus meta mode, add the column-backed fields to the Resource's
<code>$fillable</code> list. Aura captures that original list as its base
fillable list before it merges input field slugs at runtime:

~~~php
class Product extends Resource
{
    public static $customTable = true;

    public static bool $usesMeta = true;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'price',
        'user_id',
        'team_id',
    ];
}
~~~

Changing a Resource's storage flags is a schema and data change. Plan the
columns, existing meta rows, team ownership, and rollback before changing an
existing Resource. See [Custom tables](/docs/custom-tables) and
[Meta fields](/docs/meta-fields).

Meta scopes use relation subqueries:

~~~php
Article::whereMeta('featured', true)->get();
Article::whereMeta('priority', '>', 5)->get();
Article::whereMeta(['featured' => true, 'locale' => 'en'])->get();
Article::whereInMeta('category', ['news', 'updates'])->get();
Article::whereNotInMeta('category', ['internal', 'archived'])->get();
Article::whereMetaContains('tags', 'laravel')->get();
~~~

Use a real column when a value needs an ordinary database index, join, or
database-native sort. Meta values remain useful for fields whose shape does not
justify a column.

<a id="resource-development"></a>
## Use the generated commands as extension points

The generators encode Aura's current paths and method names:

| Command | Result |
| --- | --- |
| <code>aura:resource Product</code> | Resource class in the configured Resource path. |
| <code>aura:resource Product --custom</code> | Resource class with <code>$customTable</code>, <code>$usesMeta = false</code>, and a table name. It does not create the table. |
| <code>aura:field ColorPicker</code> | Field class plus edit and display Blade views in the configured field path. |
| <code>aura:create-resource-migration "App\Aura\Resources\Product"</code> | Migration columns derived from the Resource's fields and table. It does not run the migration. |
| <code>aura:create-resource-permissions</code> | Generates missing Resource permission rows. |
| <code>aura:schema-update</code> | Compares a supported <code>Schema::create</code> migration with the existing table. |

<code>aura:schema-update</code> fails before changing the table when it cannot
determine the table or safely parse columns. It keeps columns by default.
Pass <code>--drop</code> and confirm the prompt before the command removes
columns and their data. Add <code>--force</code> only when that confirmation is
already part of the controlled operation. Treat the migration as the source to
review, not as a substitute for reviewing the schema change.

The Resource Editor is a local-development tool. Its middleware blocks the
feature outside a local environment. Do not use it as a production schema
editor. See [Resource Editor](/docs/resource-editor) and
[Custom tables](/docs/custom-tables).

<a id="custom-fields"></a>
## Extend field classes without changing their contract

Generate a field before editing it:

~~~bash
php artisan aura:field ColorPicker
~~~

The command creates a class under the configured field namespace and views at
<code>resources/views/components/fields/colorpicker.blade.php</code> and
<code>colorpicker-view.blade.php</code>. A custom field extends
<code>Aura\Base\Fields\Field</code>:

~~~php
namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class ColorPicker extends Field
{
    public $edit = 'fields.colorpicker';

    public $view = 'fields.colorpicker-view';

    public function get($class, $value, $field = null)
    {
        return $value ?: '#000000';
    }

    public function set($post, $field, $value)
    {
        return strtoupper((string) $value);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Default',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'default',
            ],
        ]);
    }
}
~~~

Aura calls these optional hooks from the Resource save pipeline:

| Hook | Use |
| --- | --- |
| <code>get($class, $value, $field = null)</code> | Decode or normalize a stored value for the form and display path. |
| <code>set($post, $field, $value)</code> | Normalize submitted input before Aura stores it. |
| <code>saving($post, $field, $value)</code> | Change the Resource during its saving event. |
| <code>saved($post, $field, $value)</code> | Persist a relation or other value that needs the saved Resource. |

The edit view receives <code>$field</code> as an array. The field object is in
<code>$field['field']</code>. Bind input to <code>form.fields.{slug}</code> and
keep the wrapper:

~~~blade
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        id="resource-field-{{ optional($field)['slug'] }}"
    />
</x-aura::fields.wrapper>
~~~

Read configuration as array keys. Do not use object property syntax such as
<code>$field->slug</code> in this view. The base field display path escapes
scalar values. If a custom field emits HTML, escape interpolated stored values
and mark the field as raw only when the output is intentionally trusted.

<a id="code-organization"></a>
## Register and customize in the application

Aura discovers application Resources and fields from
<code>aura-settings.paths.resources</code> and
<code>aura-settings.paths.fields</code>:

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
~~~

Register classes from another path or package in a service provider:

~~~php
use Aura\Base\Facades\Aura;

public function boot(): void
{
    Aura::registerResources([
        \Acme\Blog\Resources\Article::class,
    ]);

    Aura::registerFields([
        \Acme\Blog\Fields\Rating::class,
    ]);
}
~~~

Use the actual methods <code>registerResources(array)</code> and
<code>registerFields(array)</code>. Aura has no <code>resources()</code> or
<code>fields()</code> registration shortcuts.

To replace an admin page, run <code>aura:customize</code>. It can generate an
application Livewire component, copy the corresponding Blade view, or both.
The command writes a static <code>indexComponent()</code>,
<code>createComponent()</code>, <code>editComponent()</code>, or
<code>viewComponent()</code> hook into the Resource. The existing Aura route and
route name continue to serve the replacement. Keep application components
under <code>App\Livewire</code>. Do not put application code in
<code>Aura\Base\Livewire</code>.

Aura Base does not generate a REST API for Resources. If an application
exposes Resource data over HTTP, define its routes and controllers in the host
application and authorize each operation with the Resource policy.

<a id="performance-patterns"></a>
## Compose table queries with Aura's scopes

The table starts with the Resource query and its normal global scopes. It then
calls <code>indexQuery($query, $table)</code>, applies relationship constraints
when the table belongs to a parent Resource, adds Kanban constraints when
needed, eager loads meta and opted-in relationships, applies filters and
search, sorts, and paginates. Keep a custom <code>indexQuery</code> composable:

~~~php
public function indexQuery($query, $table = null)
{
    return $query
        ->where('status', 'published')
        ->withCount('comments');
}
~~~

Do not call <code>withoutGlobalScopes()</code> in a normal index hook. It removes
type, team, ownership, and other Resource restrictions that the request depends
on. Cross-team maintenance queries need their own authorization and explicit
constraints. See [Teams](/docs/teams#bypassing-team-scope).

Mark fields with <code>'searchable' => true</code> for the default table search.
Table search uses a column condition for table fields and an <code>EXISTS</code>
query for meta fields. Define <code>modifySearch($query, $search)</code> when
the query must include relations or a different condition. That method replaces
the default search logic:

~~~php
public function modifySearch($query, $search)
{
    return $query->where(function ($query) use ($search) {
        $query
            ->where('sku', 'like', '%'.$search.'%')
            ->orWhereHas('supplier', function ($supplier) use ($search) {
                $supplier->where('name', 'like', '%'.$search.'%');
            });
    });
}
~~~

The same field-level searchable definitions feed Aura's database-backed global
search. Global search applies the Resource's normal query and policy checks. It
does not resolve a related record's title or a display closure before matching.
See [Global search](/docs/global-search) for its result and authorization
rules.

The table defaults are ten rows per page, ID descending. Override only the
methods the Resource needs:

~~~php
public function defaultPerPage()
{
    return 25;
}

public function defaultTableSort()
{
    return 'created_at';
}

public function defaultTableSortDirection()
{
    return 'asc';
}
~~~

Relation and media fields can opt into Aura's table eager-load and display
preload contracts. Implement those contracts when a field needs related rows
for visible table cells. The table limits eager loads to visible list columns
and primes the current page before rendering. Measure the actual route and
query shape before changing this code. See [Table component](/docs/table) and
[Performance](/docs/performance).

Row and bulk actions must be declared by the Resource. Include the ability that
the action requires. Aura resolves records through the current table scope and
authorizes each record before invoking a custom method. A custom Livewire
control still needs an explicit policy check.

<a id="security-best-practices"></a>
## Keep authorization and ownership in the policy layer

<code>Aura\Base\Policies\ResourcePolicy</code> handles <code>viewAny</code>,
<code>view</code>, <code>create</code>, <code>update</code>,
<code>delete</code>, <code>restore</code>, and <code>forceDelete</code>. The
Resource capability flags disable the matching screen or ability. Otherwise
the policy accepts a Global Admin, a Super Admin in the current team, or the
matching generated permission.

Generate permission rows after adding a Resource:

~~~bash
php artisan aura:create-resource-permissions
php artisan aura:create-resource-permissions --team=42
~~~

Assign those permissions through the Roles Resource. A
<code>scope-{resource}</code> permission also narrows normal Resource queries
to rows whose <code>user_id</code> is the current User. It does not replace team
scoping, and a Resource that uses this permission needs a <code>user_id</code>
column.

Use Laravel abilities for Resource actions:

~~~php
Gate::authorize('update', $product);

if (auth()->user()->hasPermissionTo('create', Product::class)) {
    // The permission check is explicit. The policy still guards the write.
}
~~~

The field's <code>validation</code> rules protect form input. They do not
authorize a caller. The <code>on_forms</code> and <code>on_view</code> settings
control presentation. Keep policy checks around programmatic saves, imports,
jobs, and custom Livewire actions.

<a id="team-ownership"></a>
## Preserve team ownership

With Teams enabled, Aura adds <code>TeamScope</code> to Resources. Ordinary
team-aware Resource queries use the authenticated user's current Team. An
authenticated user without a current Team gets a fail-closed query for
ordinary team-scoped Resources. A normal save receives its <code>team_id</code>
from the active team context.

The built-in Resources have different ownership rules:

- Ordinary Resources use their <code>team_id</code> column.
- The User Resource filters membership through <code>user_role</code>. A Global
  Admin can list Users across Teams.
- The Team Resource is the context record and is not filtered by TeamScope.
- The Role Resource combines Team Roles with the shared Global Role catalog and
  resolves Shadowing by role slug.
- Teams-off mode makes TeamScope a no-op and uses the schema without team
  columns.

Do not describe every Resource as permanently team-scoped. Inspect the Resource
and its schema when adding a new query. Do not remove a global scope to make a
row visible in a request unless the operation is an authorized administrative
operation with an explicit Team filter.

Keep the role terms distinct:

| Term | Meaning |
| --- | --- |
| Global Admin | Instance-level status. It can enter any Team without creating Membership. |
| Super Admin | Role-level grant with full Resource permissions inside the Team where that role resolves. |
| Global Role | Shared role definition in the Role Catalog. Only a Global Admin can define it. |
| Team Role | Role owned by one Team. A same-slug Team Role shadows the Global Role in that Team. |
| Membership | One user's role in one Team, stored in <code>user_role</code>. |

Global Admin visitation does not turn into Membership. Resource data still
follows the current Team context. See [Teams](/docs/teams) and
[Roles and permissions](/docs/roles-permissions).

<a id="livewire-components"></a>
## Customize Livewire pages through the Resource

Use <code>aura:customize</code> for an Index, Create, Edit, or View page.
Override the generated component's lifecycle methods and call
<code>parent::</code> when the default save or mount work must remain.

For a custom field or page view, preserve Aura's state paths:

- Resource forms use <code>form.fields.{slug}</code>.
- Edit and View components receive the Resource ID and slug through their
  existing mount contract.
- A custom page can return a view with
  <code>->layout('aura::components.layout.app')</code> when it replaces the
  generated view.

Do not create a second table query that skips the Resource's global scopes. Use
the table's Resource hook or the generated component seam so team and policy
behavior remains in one place. See [Customizing views](/docs/customizing-views)
and [Livewire components](/docs/livewire-components).

<a id="testing-practices"></a>
## Test the Resource through its real paths

For a form test, drive the same Livewire component and state path that the
admin page uses:

~~~php
use Aura\Base\Livewire\Resource\Create;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('creates a product', function () {
    livewire(Create::class, ['slug' => 'product'])
        ->set('form.fields.name', 'Example')
        ->call('save')
        ->assertHasNoErrors();
});
~~~

The Aura package test suite provides helpers such as
<code>createSuperAdmin()</code>, <code>createSuperAdminWithoutTeam()</code>,
<code>createAdmin()</code>, and <code>createPost()</code> in
<code>tests/Pest.php</code>. Those helpers belong to the package test
environment. An application test suite should create its own authenticated
Users and Teams through its factories.

Use focused coverage for the behavior you changed:

- <code>tests/Feature/Resource</code> covers Resource configuration, storage,
  and actions.
- <code>tests/Feature/Fields</code> covers field input and display behavior.
- <code>tests/Feature/Table</code> covers search, filters, sorting, selection,
  and table display loading.
- <code>tests/Feature/Octane/OctaneSupportTest.php</code> covers process-state
  resets and lifecycle listeners.
- <code>tests/Browser</code> covers the rendered admin flow.

Feature tests use <code>RefreshDatabase</code>. Tests under
<code>FeatureWithDatabaseMigrations</code> use <code>DatabaseMigrations</code>.
The package Pest setup resets the Aura facade and process caches after feature
tests. The browser base class clears field and conditional-logic caches before
each browser test.

<a id="runtime-state"></a>
## Treat static state as process state

Aura caches field definitions and mapped field classes in static arrays keyed by
Resource class. A Resource instance also caches its resolved <code>fields</code>
and normalized meta map. Those caches are different from the Laravel cache
store.

Register Resources and fields during application boot. Do not put the current
User, Team, or request data in class-static caches. If code changes definitions
or registrations inside a long-lived process, clear the relevant state before
the next request. <code>Aura::flushState()</code> restores boot registrations
and clears field, conditional-logic, team-scope, and ownership caches.

When Laravel Octane is installed, Aura wires <code>Aura::flushState()</code> to
<code>RequestReceived</code>, <code>TaskReceived</code>, and
<code>TickReceived</code>. Queue completion and queue failure also flush Aura
state. This keeps process-local definitions and team/user state from crossing
request boundaries. It does not clear database rows or Laravel's cache store.

If code changes the meta relation on an existing Resource instance, call
<code>clearFieldsAttributeCache()</code> before reading the computed fields
again. Do not reuse a Resource instance across users or Teams.

<a id="common-gotchas"></a>
## Keep these boundaries visible

- Aura Base does not provide generated REST routes. A host application owns
  external HTTP contracts.
- The core thumbnail path uses Intervention Image 3 with the GD driver. Enable
  PHP GD. Do not make the optional Laravel image facade or Imagick the only
  requirement for core thumbnails. See [Media Library](/docs/media-manager).
- Teams-on and Teams-off use different migration schemas. Choose
  <code>AURA_TEAMS</code> before the first migration and plan a data migration
  before changing it. See [Teams](/docs/teams#schema-differences).
- A custom-table Resource does not acquire columns from its field arrays. The
  migration and physical table must match the field slugs.
- A field hidden with <code>on_forms</code> or a disabled screen is not an
  authorization boundary.
- Do not use <code>migrate:fresh</code>, regenerate an application key, broad
  permission changes, or disabled authentication as a generic Aura
  troubleshooting step.

## Related guides

- [Resources](/docs/resources) for Resource configuration and lifecycle.
- [Fields](/docs/fields) for built-in field options.
- [Creating fields](/docs/creating-fields) for package and application field
  extensions.
- [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) for
  storage and migrations.
- [Table component](/docs/table) for search, filters, sorting, and actions.
- [Teams](/docs/teams) and [Roles and permissions](/docs/roles-permissions) for
  ownership and authorization.
- [Performance](/docs/performance) for measurements and cache behavior.
