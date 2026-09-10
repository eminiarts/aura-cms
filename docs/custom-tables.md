# Custom tables

A custom-table Resource stores its Eloquent rows in a dedicated database table. Set the resource's boolean `$customTable` flag to `true` and set the protected Eloquent `$table` property to the table name.

These settings do not create a table, copy existing records, or change a schema. Editing `getFields()` in a PHP file also does not run a migration.

## Storage modes

The storage flags come from `AuraResourceMeta`:

~~~php
public static $customTable = false;
public static bool $usesMeta = true;
~~~

Aura exposes them through `usesCustomTable()` and `usesMeta()`. There is no `$usesCustomMeta` flag in the current package. A `$customMeta` property on an application resource is not read by the storage trait.

The flags select these paths:

| `$customTable` | `$usesMeta` | Field storage |
|---|---|---|
| `false` | `true` | Base fillable fields use the shared `posts` columns. Other input fields use `meta`. This is the default. |
| `false` | `false` | Base fillable fields use `posts` columns. Other input fields have no meta destination. |
| `true` | `true` | The resource's `$table` stores its base fillable fields. Other input fields use `meta`. |
| `true` | `false` | Every input field is treated as a column on `$table`. Aura does not write field values to `meta`. |

`isMetaField($slug)` and `isTableField($slug)` report the selected path for an input slug. The storage matrix is covered by `tests/Feature/Resource/StorageMatrixTest.php`.

For a `Resource`, the constructor records the class's original `$fillable` list as `baseFillable`, then merges input field slugs into Eloquent's runtime fillable list. In custom-table plus meta mode, only the original `baseFillable` entries are table fields. Merging a field slug into Eloquent's fillable list does not create a database column.

In custom-table plus no-meta mode, every input slug is a table field. Each slug that you save must have a matching physical column. The generated custom resource stub uses this mode and does not add a `$fillable` property.

## Define a custom-table resource

This example stores all three input fields in `products`:

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

The table must contain `name`, `description`, and `status`, along with the standard columns required by the migration you run. Add Eloquent `$casts` when a column needs a cast such as `boolean`, `array`, or `decimal:2`.

To keep optional fields in `meta`, leave `$usesMeta` set to `true` and declare the column-backed fields in `$fillable`:

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

Here `name` and `status` are table columns. `seo_description` is a `meta` value.

## Generate a resource stub

Use the `--custom` option:

~~~bash
php artisan aura:resource Product --custom
~~~

The current stub writes:

~~~php
public static $customTable = true;
public static bool $usesMeta = false;
protected $table = 'products';
~~~

It leaves `getFields()` empty and does not create a migration or database table. Add the field definitions, review the table name, and generate a migration.

## Generate the table migration

Run:

~~~bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
~~~

The command uses the resource's loaded `getTable()` value. Use `--table=` when the table name must be supplied explicitly:

~~~bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product" --table=inventory_items
~~~

The command creates or rewrites a `create_{table}_table` migration. It reads `inputFields()`, so grouping fields such as `Panel` and `Tab` are skipped. `Heading` keeps the base field type `input` and therefore produces a column. The command adds:

- an `id` column;
- one column for each input field;
- `user_id`;
- `team_id` when `config('aura.teams')` is enabled;
- nullable `created_at` and `updated_at` timestamp columns.

Each field class supplies its `tableColumnType` and `tableNullable` values:

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

The `team_id` line is present only when teams are enabled. The generator does not add indexes, defaults, foreign-key constraints, cascade rules, soft deletes, or the `$table->timestamps()` helper. Edit the migration when your schema needs them, then run:

~~~bash
php artisan migrate
~~~

The resource migration command does not run `migrate` for you. For an existing table, write a new versioned migration instead of rewriting a migration that has already run.

## Resource Editor schema helpers

The `custom_tables_for_resources` feature is disabled by default:

~~~php
// config/aura.php
'features' => [
    'custom_tables_for_resources' => false,
],
~~~

These listeners react to the `SaveFields` event emitted by the local Resource Editor. They do not react to a plain edit of `getFields()` in source code:

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

Without the argument, it prompts for a resource. It then:

1. edits the resource class file;
2. sets `$customTable = true`;
3. sets `$table` to the snake-case plural of the resource class basename;
4. calls `aura:create-resource-migration` with that table name;
5. asks whether to run the migration;
6. asks whether to start the data-transfer command.

For a normal resource file that has no explicit `$customTable` declaration, the command also writes `$usesMeta = false`. If the file already declares `$customTable`, the command changes that declaration but does not choose a new `$usesMeta` value. Inspect the resulting class and migration.

The command edits PHP source during the current Artisan process. Reload the application process before relying on its optional transfer prompt. The transfer is opt-in and is not a lossless, transactional conversion.

## Transfer existing data

The separate transfer command accepts a resource class:

~~~bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
~~~

Without the argument, it prompts for a resource. It selects rows from `posts` whose `type` matches the resource, loads matching `meta` rows, and calls the resource's `create()` method with timestamps, user and team IDs, meta values, and the post row attributes exposed by the Eloquent model.

This command inserts new rows. It does not preserve the original post IDs, make repeated runs idempotent, or wrap the whole transfer in a transaction. The target table must contain every attribute that reaches `create()`. A generated custom-table migration commonly lacks shared post columns such as `title`, `content`, `type`, or `slug`, so inspect and adapt the migration or write an application-specific transfer before using this command. Back up the database and test on a copy first.

Setting `$customTable` or changing field definitions never starts this transfer automatically.

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
- A custom-table plus no-meta resource needs a physical column for every saved input slug.
- A custom-table plus meta resource uses only its original `$fillable` entries as table fields. Other input slugs go to `meta`.
- Generated migrations contain basic nullable columns. Add the constraints, indexes, defaults, casts, and data transformations your application needs.
- The posts-to-custom transfer commands need a reviewed target schema and a tested data plan. They do not remove the old `posts` or `meta` rows.

## Related

- [Meta fields](/docs/meta-fields) explains the shared key/value table.
- [Resources](/docs/resources) covers resource and field definitions.
- [Fields](/docs/fields) lists field classes and their behaviour.
