# Creating resources

A resource is a PHP class that describes a content type. It declares the fields that appear in Aura's forms, tables, and detail pages, and it determines where those values are stored. Resources extend `Aura\Base\Resource`.

This guide takes a resource from the generator to its first record. It also covers field definitions, custom tables, existing-table scaffolding, discovery, factories, permissions, and page customization. See [Resources](/docs/resources) for the complete property and method reference, and [Fields](/docs/fields) for field-specific options.

## Generate a resource

Run the generator from the Laravel application that uses Aura:

```bash
php artisan aura:resource Project
```

The command is `aura:resource {name} {--custom}`. With the default configuration it writes `app/Aura/Resources/Project.php`. The directory and namespace come from `aura-settings.paths.resources`, so a customized path changes the generated location too.

The generated class contains the resource identity, a default SVG icon, an empty `getWidgets()` method, and an empty `getFields()` method. The part you normally replace is `getFields()`:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static $singularName = 'Project';

    public static $pluralName = 'Projects';

    public static function getFields(): array
    {
        return [];
    }
}
```

The generated file also contains `getWidgets()` and `getIcon()`. Keep those generated methods when you do not need to change them.

### Names and generated values

`MakeResource` derives the main values from the name passed to the command:

| Value | Derivation | `Project` | `BlogPost` |
|-------|------------|-----------|------------|
| `$type` | `ucfirst()` | `Project` | `BlogPost` |
| `$slug` | `Str::kebab()` | `project` | `blog-post` |
| `$singularName` | `Str::headline()` | `Project` | `Blog Post` |
| `$pluralName` | plural headline | `Projects` | `Blog Posts` |
| `$table` with `--custom` | snake case, then plural | `projects` | `blog_posts` |

For example:

```bash
php artisan aura:resource BlogPost --custom
```

The generator writes `blog-post` as the route slug and `blog_posts` as the custom table name. Keep the explicit names in the generated class when the resource name contains more than one word.

## Add fields and create the first record

Fields are plain configuration arrays returned by `getFields()`. A minimal resource with a required field looks like this:

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Name',
            'slug' => 'name',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'on_index' => true,
            'searchable' => true,
        ],
        [
            'name' => 'Notes',
            'slug' => 'notes',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ],
    ];
}
```

`type` must be the fully qualified field class. Aura resolves it through the container, so `'Text'` is not enough. Fields are shown on create, edit, and view pages unless an `on_*` option is set to `false`.

After saving the class, Aura discovers it on the next application boot. Sign in as a user who can create the resource and open the create route. With the default `aura.path` value, the route for the example above is:

```text
/admin/project/create
```

The slug is singular because it comes from `$slug`, not `$pluralName`. Submit the form to create the record. Aura redirects to the new record's edit page. If the resource does not appear in the navigation, open the route directly and check the user's `viewAny` and `create` permissions. The route prefix changes when `aura.path` changes.

With the default storage settings, the resource uses Aura's shared `posts` and `meta` tables. You do not need a new migration for each field in this mode. See [Meta fields](/docs/meta-fields) for the storage rules.

## Choose a storage model

The default `Resource` values are `$customTable = false` and `$usesMeta = true`. Base resource columns such as `title` and `content` can live on `posts`; other input fields use `meta` unless the resource changes the storage settings.

### Use a custom table

Pass `--custom` when generating the class:

```bash
php artisan aura:resource Project --custom
```

The custom stub sets all three values needed for column-backed fields:

```php
public static $customTable = true;

public static bool $usesMeta = false;

protected $table = 'projects';
```

With `$customTable = true` and `$usesMeta = false`, every declared input field slug is treated as a column on `$table`. Aura adds the declared input slugs to the model's fillable attributes while it constructs the resource, so a separate `$fillable` list is not required for this generated class. `Panel` and `Tab` are layout fields and do not become columns. `Group` and `Repeater` are input fields, so the migration and factory commands include them. Their field classes control how their values are encoded.

Add fields to the resource, then generate and review the migration before running it:

```bash
php artisan aura:create-resource-migration 'App\Aura\Resources\Project'
php artisan migrate
```

`aura:create-resource-migration` reads the resource's current `$table`. Its optional `--table` value overrides that target when you need to point the generated migration at a different table name:

```bash
php artisan aura:create-resource-migration 'App\Aura\Resources\Project' --table=projects
```

The command builds the migration from `inputFields()`. It writes an `id` column, one column for each input field, `user_id`, `team_id` when teams are enabled, and `created_at` and `updated_at`. Layout fields are skipped. Each field class supplies its column type and nullability. The command reuses a migration whose name contains `create_{table}_table` when one already exists, so inspect the file after every run.

The generated migration does not run automatically. Review the columns, then run `php artisan migrate`. After the migration has run, open `/admin/project/create` and save the first record. The form writes `name` and `notes` to `projects`, and it does not create rows in `meta` while `$usesMeta` is `false`.

If the table already exists and you edited the migration to describe its desired shape, sync it explicitly:

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_projects_table.php
```

The command adds missing columns as nullable. It keeps columns that are absent from the migration unless you pass `--drop`, and it asks for confirmation before dropping them unless you also pass `--force`. If the table does not exist, it runs the migrations instead.

### Mix table columns and meta fields

Set `$usesMeta = true` only when the custom table should use both storage locations. With a custom table and meta enabled, fields in the resource's base fillable set use the custom table and other input fields use `meta`. The generated `--custom` stub chooses `$usesMeta = false`, which is the simpler all-columns configuration.

## Generate a resource from an existing table

Use the single-table command when the table already exists:

```bash
php artisan aura:transform-table-to-resource blog_posts
```

It creates `app/Aura/Resources/BlogPost.php` when that file does not already exist. The generated class imports `Aura\Base\Resource`, sets `$customTable = true`, sets `$usesMeta = false`, points `$table` at `blog_posts`, and derives the slug `blog-post`. It creates one field for every source column.

The type mapping is:

| Database column type | Generated field |
|----------------------|-----------------|
| `text`, `longtext` | `Aura\Base\Fields\Textarea` |
| `integer`, `float`, `double` | `Aura\Base\Fields\Number` |
| `date` | `Aura\Base\Fields\Date` |
| anything else | `Aura\Base\Fields\Text` |

The command does not infer validation, editable columns, or display flags. Review the generated fields before exposing the form, especially for identity and timestamp columns. It does not run a migration and it does not overwrite an existing resource file.

To process all database tables, use:

```bash
php artisan aura:database-to-resources
```

This command reads the connection's table listing, skips Laravel and Aura system tables, and delegates each remaining table to `aura:transform-table-to-resource`. Existing resource files are left in place by the delegated command.

## Discovery and registration

Aura automatically discovers app resources under the configured resource path. The default settings are:

```php
// config/aura-settings.php
'paths' => [
    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
    ],
],
```

Discovery is recursive. Aura maps each PHP file's relative path to the configured namespace, loads classes that exist, and keeps only subclasses of `Aura\Base\Resource`. You do not add a registration entry for a normal app resource.

Aura's built-in resources come from `config('aura.resources')`. `User`, `Role`, `Permission`, `Option`, and `Attachment` are always registered. `Team` and `TeamInvitation` are registered when `config('aura.teams')` is true.

For a resource supplied by a package or plugin, register the class from a service provider:

```php
use Aura\Base\Facades\Aura;

public function boot(): void
{
    Aura::registerResources([
        \Acme\Billing\Resources\Invoice::class,
    ]);
}
```

The `resources` path settings have no `register` list. Use `Aura::registerResources()` for classes outside the discovered app path.

For a normal registered resource, Aura registers these route names under `config('aura.path')`:

```text
aura.{slug}.index
aura.{slug}.create
aura.{slug}.edit
aura.{slug}.view
```

The Attachment resource uses the dedicated media page instead of the generic resource page set. Navigation also filters resources by authorization and the resource's `showInNavigation` setting.

![Resource index](/images/docs/creating-resources/resource-index.png)

## Define fields

`getFields()` returns field configuration arrays. The common keys are:

| Key | Effect |
|-----|--------|
| `name` | Label shown in forms, tables, and detail pages. |
| `slug` | Storage key and attribute name, such as `$project->status`. |
| `type` | Fully qualified field class. |
| `validation` | Laravel validation rule string or array. |
| `instructions` | Help text rendered below the field. |
| `default` | Value inserted into a new form. |
| `searchable` | Include the field in table search and global search. |
| `on_index` | Show the field as an index table column. |
| `on_forms` | Show the field on create and edit forms. |
| `on_create` / `on_edit` | Restrict the field to one form. |
| `on_view` | Show the field on the detail page. |
| `style` | For example, `['width' => '50']` sets a 50 percent form width. |
| `conditional_logic` | Show or hide the field based on conditions. |
| `disabled` | A boolean or a closure receiving the resource. |

`on_index`, `on_forms`, `on_create`, `on_edit`, and `on_view` are enabled unless you set the relevant key to `false`. A resource can opt out of global search with `public static $globalSearch = false`. Field-specific keys such as `options`, `resource`, and `enable_time` are documented in [Fields](/docs/fields).

Here is a choice field with a default value:

```php
[
    'name' => 'Status',
    'slug' => 'status',
    'type' => 'Aura\\Base\\Fields\\Status',
    'default' => 'draft',
    'on_index' => true,
    'options' => [
        ['key' => 'draft', 'value' => 'Draft', 'color' => 'bg-gray-100 text-gray-800'],
        ['key' => 'active', 'value' => 'Active', 'color' => 'bg-green-100 text-green-800'],
    ],
],
```

### Panels, tabs, groups, and repeaters

`Panel` and `Tab` are layout fields. They are declared inline, and following fields belong to that panel or tab until the next wrapper. Their display flags can cascade to their child fields unless a child sets its own value.

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Details',
            'slug' => 'details',
            'type' => 'Aura\\Base\\Fields\\Panel',
            'style' => ['width' => '75'],
        ],
        // Name and Notes are inside the Details panel.
        [
            'name' => 'Sidebar',
            'slug' => 'sidebar',
            'type' => 'Aura\\Base\\Fields\\Panel',
            'style' => ['width' => '25'],
        ],
        // Status is inside the Sidebar panel.
    ];
}
```

![Resource form with two panels](/images/docs/creating-resources/resource-form.png)

`Group` and `Repeater` are input fields. They can contain child fields and their values are stored under their own slug. The migration and factory commands include them because `inputFields()` includes both types.

### Validation

The `validation` value is passed to Laravel's validator under the field slug. Aura applies it to create and edit forms:

```php
'validation' => 'required|max:255',
// or
'validation' => ['required', 'unique:posts,slug'],
```

Validation errors use the Livewire key `form.fields.{slug}`. Child fields in a `Repeater` or `Group` use a nested key such as `{group_slug}.*.{slug}`.

### Conditional logic

`conditional_logic` accepts an array of conditions. Aura must pass every condition before it renders the field:

```php
[
    'name' => 'Archived at',
    'slug' => 'archived_at',
    'type' => 'Aura\\Base\\Fields\\Date',
    'conditional_logic' => [
        ['field' => 'status', 'operator' => '==', 'value' => 'archived'],
    ],
],
```

Supported operators are `==`, `!=`, `>`, `>=`, `<`, and `<=`. The special field name `role` checks the current user's role. Super admins pass role conditions automatically.

You can also supply a closure. Aura calls it with the resource and the current form state:

```php
'conditional_logic' => [
    fn ($model, $form) => $model->exists,
],
```

The Resource Editor cannot open a resource whose field definitions contain closures.

## Generate a factory

Pass a resource class to create a factory scaffold:

```bash
php artisan aura:create-resource-factory 'App\Aura\Resources\Project'
```

Without an argument, the command prompts you to search the registered resources. It creates `database/factories/ProjectFactory.php` and builds its `definition()` from the resource's input fields. Panels and tabs are excluded because they are not input fields. Faker values are selected by field type. A `BelongsTo` field with a `resource` key uses that resource's factory; a `BelongsTo` field without one gets a random number and may need manual adjustment. Select and radio fields use placeholder options and should be edited to match the field's real values.

The command prints a `newFactory()` method. Add an import for the generated factory, then add the method to the resource:

```php
use Database\Factories\ProjectFactory;

protected static function newFactory()
{
    return ProjectFactory::new();
}
```

## Generate permissions

After the resource is discoverable, generate its standard permissions:

```bash
php artisan aura:create-resource-permissions
```

The command creates or updates `view`, `viewAny`, `create`, `update`, `restore`, `delete`, `forceDelete`, and `scope` permissions for eligible registered resources. Slugs use the form `view-project`, `viewAny-project`, and so on. In teams mode, permissions are written for the current user's current team by default. Pass `--team=ID` to choose a team explicitly. Teams-off mode omits the team column.

Assign the permissions to a role in the admin panel. A Super Admin can use the generated resource without a separate permission assignment.

## Customize resource pages

Each normal resource resolves its index, create, edit, and view pages through static component hooks. Override a hook to use an application Livewire component while keeping Aura's route names and URLs:

```php
public static function indexComponent(): string
{
    return \App\Livewire\IndexProject::class;
}
```

The other hooks are `createComponent()`, `editComponent()`, and `viewComponent()`. The `aura:customize` command can create the component, copy its Blade view, and add the hook:

```bash
php artisan aura:customize project index --mode=full
```

Its signature is `aura:customize {resource?} {type?*} {--mode=} {--force}`. `type` accepts `index`, `create`, `edit`, or `view`. `full` creates a component and view, `view` copies a Blade view and wires `{type}View()`, and `component` creates only the Livewire component. See [Customizing views](/docs/customizing-views).

## Create and edit resources from the admin panel

The `aura.features.create_resource` setting adds a Create Resource entry to the settings navigation for Super Admins. It runs `aura:resource`, clears the cache, and redirects to the Resource Editor.

The `aura.features.resource_editor` setting controls the editor route at `/resources/{slug}/editor` under `config('aura.path')`. The editor requires the `local` or `testing` environment, the feature setting, and a Super Admin. It only edits app resources, and it refuses resources whose field definitions contain closures. Use the CLI workflow for production resource definitions.

## Related documentation

- [Resources](/docs/resources) for resource properties and methods.
- [Fields](/docs/fields) for field types and per-type options.
- [Custom tables](/docs/custom-tables) for dedicated-table storage.
- [Meta fields](/docs/meta-fields) for shared `posts` and `meta` storage.
- [Customizing views](/docs/customizing-views) for replacing page components and views.
- [Roles and permissions](/docs/roles-permissions) for assigning access.
- [Resource Editor](/docs/resource-editor) for the local editor workflow.
