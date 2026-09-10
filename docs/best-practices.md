# Best practices

This guide collects conventions that are specific to Aura CMS. It assumes that
you already know Laravel and Eloquent. Use the linked pages for complete field,
resource, table, team, and storage references.

<a id="coding-standards"></a>
## Keep resource definitions explicit

An Aura resource extends Eloquent with a static definition for its fields and
admin pages. Extend <code>Aura\Base\Resource</code> and keep resource classes
in the directory configured in <code>config/aura-settings.php</code>:

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
before Aura can load the resource.

The settings that affect a resource most often are:

| Setting | Effect |
| --- | --- |
| <code>$type</code> | Type discriminator for rows stored in the shared <code>posts</code> table. |
| <code>$slug</code> | URL and route-name segment. |
| <code>$singularName</code> and <code>$pluralName</code> | Navigation and page labels. |
| <code>$group</code> and <code>$sort</code> | Navigation group and order. Lower sort values appear first. |
| <code>$globalSearch</code> | Includes the resource in database-backed global search. |
| <code>$customTable</code> | Uses the resource's <code>$table</code> instead of <code>posts</code>. |
| <code>$usesMeta</code> | Allows fields without a table column to use the <code>meta</code> table. |
| <code>$createEnabled</code>, <code>$editEnabled</code>, <code>$viewEnabled</code>, <code>$indexViewEnabled</code> | Gates the matching resource policy ability. |

These settings control resource behavior and access through policies. Define
Eloquent relationships, casts, scopes, and migrations in your application.
See [Resources](/docs/resources) for the full configuration reference.

<a id="field-development"></a>
## Define fields as arrays

Define each field as an array returned by <code>getFields()</code>. Set its
type to the fully qualified field class name. Aura uses Laravel's container to
resolve that class:

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

Aura validates the type and slug before resolving each field. Include a
<code>name</code> for its label in forms and tables.

Choose stable lowercase slugs, usually in snake_case. Each slug is both a
storage key and a dynamic resource attribute. Renaming it changes the meta key
or database column that Aura expects, depending on the field's storage.

Use these keys for the common resource concerns:

- <code>validation</code> supplies the form rules used by Aura's resource form.
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

Tabs and panels group the fields that follow them.

Presentation keys do not authorize a write. Keep validation and policy checks
in place when a field is hidden from a form. See [Fields](/docs/fields) for
the built-in field catalogue and its options.

<a id="database-design"></a>
## Choose storage deliberately

Choose the resource's table and meta storage separately. The
<code>$customTable</code> and <code>$usesMeta</code> settings combine as follows:

| <code>$customTable</code> | <code>$usesMeta</code> | Field storage |
| --- | --- | --- |
| <code>false</code> | <code>true</code> | Base fillable attributes use <code>posts</code>. Other input fields use <code>meta</code>. |
| <code>false</code> | <code>false</code> | Base fillable attributes use <code>posts</code>. Other input fields have no meta destination. |
| <code>true</code> | <code>true</code> | Base fillable attributes use the custom table. Other input fields use <code>meta</code>. |
| <code>true</code> | <code>false</code> | Every input field slug must be a column on the custom table. |

By default, resources share the posts and meta tables. Core attributes such as
the title, content, type, status, slug, owner, parent, order, team, and timestamps
use columns in the posts table. The resource class and its schema determine
the exact list of base fillable attributes.

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

The custom resource generator uses this mode but does not create the table.
Create and review a migration before saving records.

If a custom table also uses meta storage, add its column-backed fields to
<code>$fillable</code>. Aura uses this original list to distinguish table
columns from meta fields before adding input field slugs at runtime:

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

Changing a resource's storage flags is a schema and data change. Plan the
columns, existing meta rows, team ownership, and rollback before changing an
existing resource. See [Custom tables](/docs/custom-tables) and
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

Use the generators to create classes with the paths and method names Aura
expects:

| Command | Result |
| --- | --- |
| <code>aura:resource Product</code> | Creates a resource class in the configured resource path. |
| <code>aura:resource Product --custom</code> | Creates a resource class with <code>$customTable</code>, <code>$usesMeta = false</code>, and a table name. It does not create the table. |
| <code>aura:field ColorPicker</code> | Field class plus edit and display Blade views in the configured field path. |
| <code>aura:create-resource-migration "App\Aura\Resources\Product"</code> | Migration columns derived from the resource's fields and table. It does not run the migration. |
| <code>aura:create-resource-permissions</code> | Generates missing resource permission rows. |
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

Aura calls these optional hooks from the resource save pipeline:

| Hook | Use |
| --- | --- |
| <code>get($class, $value, $field = null)</code> | Decode or normalize a stored value for the form and display path. |
| <code>set($post, $field, $value)</code> | Normalize submitted input before Aura stores it. |
| <code>saving($post, $field, $value)</code> | Change the resource during its saving event. |
| <code>saved($post, $field, $value)</code> | Persist a relation or other value that needs the saved resource. |

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

Aura discovers application resources and fields from
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

The registration methods accept arrays of class names. There are no
<code>resources()</code> or <code>fields()</code> registration shortcuts.

To replace an admin page, run <code>aura:customize</code>. It can generate an
application Livewire component, copy the corresponding Blade view, or both.
The command writes a static <code>indexComponent()</code>,
<code>createComponent()</code>, <code>editComponent()</code>, or
<code>viewComponent()</code> hook into the resource. The existing Aura route and
route name continue to serve the replacement. Keep application components
under <code>App\Livewire</code>. Do not put application code in
<code>Aura\Base\Livewire</code>.

Aura Base does not generate a REST API for resources. If an application
exposes resource data over HTTP, define its routes and controllers in the host
application and authorize each operation with the resource policy.

<a id="performance-patterns"></a>
## Compose table queries with Aura's scopes

Add table query constraints through <code>indexQuery($query, $table)</code>.
Aura calls this hook on the resource query with its normal global scopes.
Return the query builder so Aura can continue building the table query:

~~~php
public function indexQuery($query, $table = null)
{
    return $query
        ->where('status', 'published')
        ->withCount('comments');
}
~~~

After this hook, Aura applies parent relationship and Kanban constraints when
needed. It then eager loads meta and opted-in relationships, applies filters
and search, sorts the results, and paginates.

Do not call <code>withoutGlobalScopes()</code> in a normal index hook. It removes
type, team, ownership, and other resource restrictions that the request depends
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
search. Global search applies the resource's normal query and policy checks. It
does not resolve a related record's title or a display closure before matching.
See [Global search](/docs/global-search) for its result and authorization
rules.

The table defaults are ten rows per page, ID descending. Override only the
methods the resource needs:

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

Relationship and media fields can ask the table to load related records before
rendering. Use Aura's eager-loading and display-preloading contracts when a
custom field needs this behavior. The table loads data for visible columns and
prepares the current page before rendering. Measure the route and its queries
before changing how it loads data. See [Table component](/docs/table) and
[Performance](/docs/performance).

Row and bulk actions must be declared by the resource. Include the ability that
the action requires. Aura resolves records through the current table scope and
authorizes each record before invoking a custom method. A custom Livewire
control still needs an explicit policy check.

<a id="security-best-practices"></a>
## Keep authorization and ownership in the policy layer

Aura's resource policy checks access to listing, viewing, creating, updating,
deleting, restoring, and permanently deleting records. Disabling a resource
capability also disables its matching screen or ability. Otherwise, the policy
allows a Global Admin, a Super Admin in the current team, or a user with the
matching generated permission. See <code>Aura\Base\Policies\ResourcePolicy</code>
for these standard Laravel policy methods.

Generate permission rows after adding a resource:

~~~bash
php artisan aura:create-resource-permissions
php artisan aura:create-resource-permissions --team=42
~~~

Assign those permissions through the Roles resource. A
<code>scope-{resource}</code> permission also narrows normal resource queries
to rows whose <code>user_id</code> is the current user. It does not replace team
scoping, and a resource that uses this permission needs a <code>user_id</code>
column.

Use Laravel abilities for resource actions:

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

With teams enabled, Aura scopes ordinary resource queries to the authenticated
user's current team. These queries return no records if the user has no current
team. Normal saves receive their <code>team_id</code> from the active team
context.

The built-in resources have different ownership rules:

- Ordinary resources use their <code>team_id</code> column.
- The user resource filters membership through <code>user_role</code>. A Global
  Admin can list users across teams.
- The team resource represents the team itself and is not filtered by the team scope.
- The role resource combines team roles with shared global roles. A team role
  takes precedence over a global role with the same slug.
- With teams disabled, the team scope does nothing and the schema has no team
  columns.

Check the resource and its schema when adding a query, since ownership rules
vary. Remove a global scope only for an authorized administrative operation
with an explicit team filter.

Keep the role terms distinct:

| Term | Meaning |
| --- | --- |
| Global Admin | Instance-level status. It can enter any team without creating membership. |
| Super Admin | Role-level grant with full resource permissions inside the team where that role resolves. |
| Global role | A shared role definition in the role catalog. Only a Global Admin can define it. |
| Team role | A role owned by one team. It takes precedence over a global role with the same slug in that team. |
| Membership | One user's role in one team, stored in <code>user_role</code>. |

A Global Admin visiting a team does not become a member. Resource data still
follows the current team context. See [Teams](/docs/teams) and
[Roles and permissions](/docs/roles-permissions).

<a id="livewire-components"></a>
## Customize Livewire pages through the resource

Use <code>aura:customize</code> for an Index, Create, Edit, or View page.
Override the generated component's lifecycle methods and call
<code>parent::</code> when the default save or mount work must remain.

For a custom field or page view, preserve Aura's state paths:

- Resource forms use <code>form.fields.{slug}</code>.
- Edit and View components receive the resource ID and slug through their
  existing mount contract.
- A custom page can return a view with
  <code>->layout('aura::components.layout.app')</code> when it replaces the
  generated view.

Customize the table through its resource hook or generated component. This
preserves its global scopes and keeps team and policy checks in one place.
See [Customizing views](/docs/customizing-views)
and [Livewire components](/docs/livewire-components).

<a id="testing-practices"></a>
## Test the resource through its real paths

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

The package test suite provides helpers for creating admins with or without a
team, and for creating posts in
<code>tests/Pest.php</code>, including the <code>createSuperAdmin()</code>
helper used above. These helpers belong to the package test environment. In
application tests, use your own factories to create authenticated users and
teams.

Use focused coverage for the behavior you changed:

- <code>tests/Feature/Resource</code> covers resource configuration, storage,
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
resource class. A resource instance also caches its resolved <code>fields</code>
and normalized meta map. Those caches are different from the Laravel cache
store.

Register resources and fields during application boot. Do not put the current
user, team, or request data in class-static caches. If code changes definitions
or registrations inside a long-lived process, clear the relevant state before
the next request. <code>Aura::flushState()</code> restores boot registrations
and clears field, conditional-logic, team-scope, and ownership caches.

With Laravel Octane installed, Aura flushes this state when a request, task, or
tick begins. It also flushes state after a queued job completes or fails. This
prevents definitions and user or team state from leaking into another request.
It does not clear database rows or Laravel's cache store.

If code changes the meta relation on an existing resource instance, call
<code>clearFieldsAttributeCache()</code> before reading the computed fields
again. Do not reuse a resource instance across users or teams.

<a id="common-gotchas"></a>
## Keep these boundaries visible

- Aura Base does not provide generated REST routes. A host application owns
  external HTTP contracts.
- The core thumbnail path uses Intervention Image 3 with the GD driver. Enable
  PHP GD. Do not make the optional Laravel image facade or Imagick the only
  requirement for core thumbnails. See [Media Library](/docs/media-manager).
- Enabling or disabling teams selects a different migration schema. Choose
  <code>AURA_TEAMS</code> before the first migration and plan a data migration
  before changing it. See [Teams](/docs/teams#schema-differences).
- A custom-table resource does not acquire columns from its field arrays. The
  migration and physical table must match the field slugs.
- A field hidden with <code>on_forms</code> or a disabled screen is not an
  authorization boundary.
- Do not use <code>migrate:fresh</code>, regenerate an application key, broad
  permission changes, or disabled authentication as a generic Aura
  troubleshooting step.

## Related guides

- [Resources](/docs/resources) for resource configuration and lifecycle.
- [Fields](/docs/fields) for built-in field options.
- [Creating fields](/docs/creating-fields) for package and application field
  extensions.
- [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) for
  storage and migrations.
- [Table component](/docs/table) for search, filters, sorting, and actions.
- [Teams](/docs/teams) and [Roles and permissions](/docs/roles-permissions) for
  ownership and authorization.
- [Performance](/docs/performance) for measurements and cache behavior.
