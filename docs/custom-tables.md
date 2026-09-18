# Custom tables

A resource can store its records in a dedicated database table instead of the shared posts table. To use a custom table, set `$customTable` to `true` and set the protected `$table` property to your table name.

You must create the table with a migration and transfer any existing records separately. Changing these settings or editing field definitions in PHP does not change the database schema.

## Storage modes

Two settings control where Aura stores field values. The storage trait, `AuraResourceMeta`, defines these defaults:

~~~php
public static $customTable = false;
public static bool $usesMeta = true;
~~~

You can read these settings with `usesCustomTable()` and `usesMeta()`. The package has no `$usesCustomMeta` setting and ignores a `$customMeta` property declared on your resource.

The settings work together as follows:

| `$customTable` | `$usesMeta` | Field storage |
|---|---|---|
| `false` | `true` | Base fillable fields use the shared `posts` columns. Other input fields use `meta`. This is the default. |
| `false` | `false` | Base fillable fields use `posts` columns. Other input fields have no meta destination. |
| `true` | `true` | The resource's `$table` stores its base fillable fields. Other input fields use `meta`. |
| `true` | `false` | Every input field is treated as a column on `$table`. Aura does not write field values to `meta`. |

To check where a particular input field will be stored, pass its slug to `isMetaField($slug)` or `isTableField($slug)`. The package tests these combinations in `tests/Feature/Resource/StorageMatrixTest.php`.

When a custom table also uses meta storage, only the fields in the resource's original `$fillable` list are stored as table columns. Aura saves that list as `baseFillable` before adding input field slugs to Eloquent's runtime fillable list. Those later additions allow mass assignment, but do not make a field a table column or create a column in the database.

When meta storage is disabled for a custom table, every input field you save needs a matching database column. Generated custom resources use this mode and do not declare a fillable list.

## Define a custom-table resource

This resource stores its name, description, and status in the products table:

~~~php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';

    public static ?string $slug = 'product';

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
                'validation' => 'required|max:255',
            ],
            [
                'name' => 'Description',
                'slug' => 'description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Select',
            ],
        ];
    }
}
~~~

Create a column for each field, along with the standard columns required by your migration. Use Eloquent's `$casts` property for values that need conversion, such as booleans, arrays, or decimals.

To keep optional fields in the shared meta table, leave `$usesMeta` set to `true`. Declare the fields that belong in your custom table in `$fillable`:

~~~php
class Product extends Resource
{
    public static $customTable = true;

    public static bool $usesMeta = true;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'status',
        'user_id',
        'team_id',
    ];

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'SEO description',
                'slug' => 'seo_description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
            ],
        ];
    }
}
~~~

In this example, the name and status are table columns. Aura stores the SEO description in the meta table.

## Generate a resource stub

Generate a resource for a custom table with the `--custom` option:

~~~bash
php artisan aura:resource Product --custom
~~~

The generated resource enables custom table storage and disables meta storage:

~~~php
public static $customTable = true;
public static bool $usesMeta = false;
protected $table = 'products';
~~~

The resource starts with no field definitions. The command does not create a migration or database table. Add your fields, review the table name, and generate a migration.

## Generate the table migration

Generate a migration from your resource's field definitions:

~~~bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
~~~

The command reads the table name from the loaded resource through `getTable()`. You can supply a different name with `--table=`:

~~~bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product" --table=inventory_items
~~~

The command creates or rewrites a `create_{table}_table` migration using the resource's input fields. It skips grouping fields such as panels and tabs. Headings still count as input fields and produce columns. The migration contains:

- A primary key named `id`.
- One column for each input field.
- A user reference named `user_id`.
- A team reference named `team_id` when `config('aura.teams')` is enabled.
- Nullable timestamps named `created_at` and `updated_at`.

Each field class defines the generated column type and whether it accepts null through `tableColumnType` and `tableNullable`:

| Field | Generated column | Nullable |
|---|---|---|
| `ID` | `bigIncrements` | no |
| `Number` | `integer` | yes |
| `Text`, `Select`, `Slug`, and fields without an override | `string` | yes |
| `Textarea`, `Wysiwyg` | `text` | yes |
| `Date` | `date` | yes |
| `Datetime` | `timestamp` | yes |
| `BelongsTo` | `bigInteger` | yes |

The field class name is `Aura\Base\Fields\Datetime`. The package does not define a `DateTime` field class.

For the first example, the generated schema is equivalent to:

~~~php
Schema::create('products', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name')->nullable();
    $table->text('description')->nullable();
    $table->string('status')->nullable();
    $table->bigInteger('user_id')->nullable();
    $table->bigInteger('team_id')->nullable();
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});
~~~

The team column appears only when teams are enabled. The generator does not add indexes, defaults, foreign key constraints, cascade rules, or soft deletes. It writes the timestamp columns separately instead of using `$table->timestamps()`. Review the migration and add anything your schema needs before running it:

~~~bash
php artisan migrate
~~~

Generating the migration does not apply it to the database. For an existing table, write a new versioned migration instead of rewriting one that has already run.

## Resource Editor schema helpers

The Resource Editor can generate schema changes when you save fields. This feature is disabled by default:

~~~php
// config/aura.php
'features' => [
    'custom_tables_for_resources' => false,
],
~~~

The `custom_tables_for_resources` setting controls how these changes are applied. Its listeners run when the local Resource Editor emits a `SaveFields` event. Editing field definitions directly in PHP does not trigger them.

| Value | Behaviour |
|---|---|
| `false` | No custom-table migration listener runs. |
| `'multiple'` | A field change writes an `update_{table}_table_*` migration and attempts to run `migrate`. |
| `true` or `'single'` | The listener rewrites the `create_{table}_table` migration and calls `aura:schema-update`. The command adds missing columns and, with the listener's options, drops columns removed from the generated schema. It does not change the type of an existing column. |

Review generated migrations before applying them. Use a normal, hand-written migration when a field change needs data conversion, a type change, a rename with data preservation, or a production rollout.

## Convert an existing posts resource

The conversion command accepts a resource class:

~~~bash
php artisan aura:migrate-from-posts-to-custom-table "App\Aura\Resources\Product"
~~~

If you omit the class, the command prompts you to choose a resource. It then:

1. Edits the resource class file.
2. Enables custom table storage by setting `$customTable = true`.
3. Sets `$table` to the snake-case plural of the resource's class name, without its namespace.
4. Generates a migration for that table using `aura:create-resource-migration`.
5. Asks whether to run the migration.
6. Asks whether to start the data transfer command.

When the resource file has no explicit custom table declaration, the command also disables meta storage. If the file already declares `$customTable`, the command updates it but leaves `$usesMeta` unchanged. Inspect the resulting class and migration to confirm the intended storage mode.

The command changes the PHP source while Artisan is already running. Reload the application process before relying on the optional transfer prompt. Data transfer requires your confirmation. It does not guarantee that all data is preserved and does not run in a single transaction.

## Transfer existing data

The separate transfer command accepts a resource class:

~~~bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
~~~

If you omit the class, the command prompts you to choose a resource. It finds records in the posts table whose type matches the resource and loads their meta values. It then creates records through the resource's `create()` method, passing timestamps, user and team IDs, meta values, and the post attributes exposed by Eloquent.

The transfer inserts new rows with new IDs. Running it again can create duplicates, and a failure can leave a partial transfer because the command does not use a single transaction.

The destination table must have a column for every attribute passed to `create()`. Generated migrations commonly omit shared post columns such as title, content, type, or slug. Review and adapt the migration, or write a transfer specific to your application. Back up the database and test on a copy first.

Enabling custom table storage or changing field definitions never starts a transfer automatically.

## Built-in resources

Aura's built-in resources show both storage modes:

| Resource | Table | `$customTable` | `$usesMeta` |
|---|---|---:|---:|
| `User` | `users` | `true` | `true` |
| `Team` | `teams` | `true` | `true` |
| `Role` | `roles` | `true` | `false` |
| `Permission` | `permissions` | `true` | `false` |
| `Option` | `options` | `true` | inherited `true` |

## Limitations

- Aura does not infer a database schema from a changed field definition at runtime.
- When meta storage is disabled, every input field you save needs a matching database column.
- When a custom table also uses meta storage, only fields in the original fillable list use table columns. Other input fields go to the meta table.
- Generated migrations contain basic nullable columns. Add the constraints, indexes, defaults, casts, and data transformations your application needs.
- Review the destination schema and test your data transfer plan before running the conversion commands. They leave the original posts and meta rows in place.

## Related

- [Meta fields](/docs/meta-fields) explains the shared key/value table.
- [Resources](/docs/resources) covers resource and field definitions.
- [Fields](/docs/fields) lists field classes and their behaviour.
