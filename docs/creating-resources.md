# Creating resources

A resource is a PHP class that describes a content type. It declares the fields that appear in Aura's forms, tables, and detail pages, and it determines where those values are stored. Resources extend `Aura\Base\Resource`.

This guide walks through generating a resource, defining its fields, and saving the first record. Later sections cover storage, existing tables, registration, factories, permissions, and page customization. See [Resources](/docs/resources) for the complete property and method reference, and [Fields](/docs/fields) for field-specific options.

## Generate a resource

Run the generator from the Laravel application that uses Aura:

```bash
php artisan aura:resource Project
```

By default, this creates `app/Aura/Resources/Project.php`. You can change the directory and namespace through `aura-settings.paths.resources`. Add the `--custom` option to generate a resource that uses its own database table, as described below.

The generated class defines the resource's names and icon, with empty methods for widgets and fields. Start by adding your fields to `getFields()`:

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

The example omits the generated `getWidgets()` and `getIcon()` methods. Leave them in your file unless you need to customize the widgets or icon.

### Names and generated values

The generator derives the following values from the resource name:

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

Each field needs its fully qualified class name in the `type` option. A short name such as `'Text'` is not enough for Aura to resolve the class through Laravel's container. Fields appear on create, edit, and detail pages by default. Use the display options described below to hide them on specific pages.

After saving the class, Aura discovers it on the next application boot. Sign in as a user who can create the resource and open the create route. With the default `aura.path` value, the route for the example above is:

```text
/admin/project/create
```

The URL uses the resource's slug, which is singular in this example. Submitting the form creates the record and redirects to its edit page. If the resource is missing from the navigation, open the route directly and check the user's `viewAny` and `create` permissions. Changing `aura.path` changes the route prefix.

With the default storage settings, the resource uses Aura's shared `posts` and `meta` tables. You do not need a new migration for each field in this mode. See [Meta fields](/docs/meta-fields) for the storage rules.

## Choose a storage model

By default, resources share Aura's tables. Base attributes such as the title and content can use columns in `posts`, while other input fields use `meta`. These defaults correspond to `$customTable = false` and `$usesMeta = true`.

### Use a custom table

Pass `--custom` when generating the class:

```bash
php artisan aura:resource Project --custom
```

The generated class selects a dedicated table and disables meta storage:

```php
public static $customTable = true;

public static bool $usesMeta = false;

protected $table = 'projects';
```

With these settings, each input field's slug names a column in the custom table. Aura makes those attributes fillable when it constructs the resource, so you do not need a separate `$fillable` list.

Panels and tabs control layout and do not become columns. Groups and repeaters store input, so the migration and factory commands include them. Their field classes determine how to encode their values.

Add fields to the resource, then generate and review the migration before running it:

```bash
php artisan aura:create-resource-migration 'App\Aura\Resources\Project'
php artisan migrate
```

The migration command uses the table named in the resource. To generate a migration for a different table, pass `--table`:

```bash
php artisan aura:create-resource-migration 'App\Aura\Resources\Project' --table=projects
```

The generated migration includes a primary key, a column for each input field, a user ID, and timestamps. It also includes a team ID when teams are enabled. Layout fields are skipped, and each input field class determines its column type and whether it allows null values.

The command reads these fields through `inputFields()`. It reuses an existing migration whose name contains `create_{table}_table`, so inspect the file after every run.

The command does not run the migration automatically. Review the columns, then run `php artisan migrate`. Open `/admin/project/create` to save the first record. In this example, the form writes the name and notes to the projects table. With meta storage disabled, it creates no meta rows.

If the table already exists and you edited the migration to describe its desired shape, sync it explicitly:

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_projects_table.php
```

The command adds missing columns as nullable. It keeps columns that are absent from the migration unless you pass `--drop`, and it asks for confirmation before dropping them unless you also pass `--force`. If the table does not exist, it runs the migrations instead.

### Mix table columns and meta fields

To combine custom table columns with meta storage, set `$usesMeta = true`. Fields in the resource's base fillable set then use the custom table, while other input fields use the meta table. The custom resource generator disables meta storage by default, so all input fields use columns until you change this setting.

## Generate a resource from an existing table

Use the single-table command when the table already exists:

```bash
php artisan aura:transform-table-to-resource blog_posts
```

This creates `app/Aura/Resources/BlogPost.php` unless the file already exists. The class extends Aura's base resource and uses the existing table, with meta storage disabled. Its route slug is `blog-post`, and it declares one field for every source column.

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

This runs the single-table generator for every table on the connection except Laravel and Aura system tables. Existing resource files remain unchanged.

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

Aura searches this directory and its subdirectories for resource classes. Class namespaces must match their paths relative to the configured directory, and each class must extend `Aura\Base\Resource`. You do not need to register these app resources manually.

Aura configures its built-in resources through `aura.resources`. Users, roles, permissions, options, and attachments are always registered. Teams and team invitations are registered when teams are enabled through `aura.teams`.

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

Use this service provider method for classes outside the configured app directory. The resource path settings do not accept a separate registration list.

For each normal registered resource, Aura adds the following named routes under the configured `aura.path` prefix:

```text
aura.{slug}.index
aura.{slug}.create
aura.{slug}.edit
aura.{slug}.view
```

Attachments use the dedicated media page instead of these generic resource pages. A resource appears in the navigation only when the user has access and its `showInNavigation` setting allows it.

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

All display options in the table are enabled by default. Set the relevant option to `false` to hide a field. To exclude the entire resource from global search, add `public static $globalSearch = false`. See [Fields](/docs/fields) for options specific to each field type, such as choices, related resources, and time inputs.

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

Panels and tabs organize the form without storing input. Declare them alongside other fields. The fields that follow belong to that panel or tab until the next layout wrapper. Child fields can inherit its display settings unless they define their own.

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

Groups and repeaters contain child fields and store their values under the group's or repeater's slug. They count as input fields, so the migration and factory commands include them.

### Validation

Aura applies Laravel validation rules to both create and edit forms. Add rules to a field's `validation` option, and Aura validates the value under that field's slug:

```php
'validation' => 'required|max:255',
// or
'validation' => ['required', 'unique:posts,slug'],
```

Validation errors use the Livewire key `form.fields.{slug}`. For child fields in a repeater or group, the field key is nested, such as `{group_slug}.*.{slug}`.

### Conditional logic

To show a field only when certain conditions are met, add a `conditional_logic` array. Aura renders the field only when every condition passes:

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

Without an argument, the command prompts you to search the registered resources. It creates `database/factories/ProjectFactory.php` with Faker values based on each input field's type. It excludes panels and tabs because they only control layout.

Review the generated values before using the factory. A belongs-to field uses the related resource's factory when its `resource` option is set. Otherwise, it gets a random number that may need adjustment. Select and radio fields use placeholders that you should replace with the field's actual choices.

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

For eligible registered resources, the command creates or updates permissions to view individual records, list records, create, update, restore, delete, permanently delete, and scope access. Their action names are `view`, `viewAny`, `create`, `update`, `restore`, `delete`, `forceDelete`, and `scope`. Permission slugs combine the action and resource slug, such as `view-project`.

With teams enabled, the command uses the current user's current team by default. Pass `--team=ID` to choose a team explicitly. With teams disabled, it omits the team column.

Assign the permissions to a role in the admin panel. A super admin can use the generated resource without a separate permission assignment.

## Customize resource pages

You can replace a resource's index, create, edit, or detail page with your own Livewire component. Override the corresponding static method on the resource to keep Aura's existing route names and URLs:

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

Choose a page with `index`, `create`, `edit`, or `view`. The command's full signature is `aura:customize {resource?} {type?*} {--mode=} {--force}`.

The mode determines what it generates. Use `full` for a component and Blade view, `view` to copy only the Blade view and configure the resource's `{type}View()` method, or `component` for only the Livewire component. See [Customizing views](/docs/customizing-views).

## Create and edit resources from the admin panel

Enable `aura.features.create_resource` to add a Create Resource entry to the settings navigation for super admins. Creating a resource there runs the generator, clears the cache, and opens the Resource Editor.

Enable the editor separately with `aura.features.resource_editor`. Its route is `/resources/{slug}/editor` under the configured admin prefix. Access requires a super admin and a `local` or `testing` environment.

The editor only supports app resources and cannot open field definitions that contain closures. Use the CLI workflow for production resource definitions.

## Related documentation

- [Resources](/docs/resources) for resource properties and methods.
- [Fields](/docs/fields) for field types and per-type options.
- [Custom tables](/docs/custom-tables) for dedicated-table storage.
- [Meta fields](/docs/meta-fields) for shared `posts` and `meta` storage.
- [Customizing views](/docs/customizing-views) for replacing page components and views.
- [Roles and permissions](/docs/roles-permissions) for assigning access.
- [Resource Editor](/docs/resource-editor) for the local editor workflow.
