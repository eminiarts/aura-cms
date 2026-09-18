# Meta fields

Aura stores field values in table columns or in a shared meta table. By default, resources use the shared posts table for core columns and meta storage for other declared fields. Meta storage must be enabled for those fields to persist.

Meta storage does not add a column for every field. It lets a resource keep fields that are not part of its table schema without another migration. For fields that need database indexes, joins, or aggregate queries, use columns in a [custom table](/docs/custom-tables).

## Storage flags

Two static properties decide the storage mode:

```php
class Article extends Resource
{
    // This property is untyped in Resource and must stay untyped in subclasses.
    public static $customTable = false;

    // Store fields that are not base-table columns in meta.
    public static bool $usesMeta = true;
}
```

Choose the resource's table with `$customTable`, and enable meta storage separately with `$usesMeta`. When meta is enabled, the resource has a meta relationship and stores non-column fields there. To check these settings in code, call `usesCustomTable()` or `usesMeta()`.

| `$customTable` | `$usesMeta` | Storage |
|---|---|---|
| `false` | `true` | Base columns in `posts`; other declared input fields in `meta`. |
| `false` | `false` | Base `posts` columns only. The generic meta path is disabled. |
| `true` | `true` | Original `$fillable` columns in the custom table; other declared input fields in `meta`. |
| `true` | `false` | The custom table only. Every declared input field needs a physical column. |

The example above shows the defaults. To store a custom-table resource entirely in columns, set `$usesMeta = false` and create a column for every input field.

These rules cover ordinary field storage. A field's `saved()` hook can store a relationship in a pivot table or elsewhere. See [Serialization and save hooks](#serialization-and-save-hooks) for the relationship fields that use this approach.

## Core columns and field placement

The package migration creates these columns on the shared `posts` table:

| Column | Definition |
|---|---|
| `id` | Primary key. |
| `title` | Nullable text. |
| `content` | Nullable long text. |
| `type` | Required string that identifies the resource type. |
| `status` | Nullable string with a `publish` default. |
| `slug` | Nullable indexed string. |
| `user_id` | Nullable indexed foreign-id column. |
| `parent_id` | Nullable indexed foreign-id column. |
| `order` | Nullable integer. |
| `team_id` | Nullable foreign-id column when teams are enabled. |
| `created_at`, `updated_at` | Timestamp columns. |
| `deleted_at` | Soft-delete timestamp. |

Aura sets `type`, `content`, `user_id`, `team_id`, and `slug` during the save pipeline when the posts-backed resource needs them. A custom-table resource does not receive the posts `type` and `content` setup.

When meta is enabled, Aura uses the resource's original `$fillable` list to decide which fields belong in table columns. It captures that list as `baseFillable` when constructing the resource, then adds the declared input-field slugs to Eloquent's fillable list. This lets you assign field values without changing which fields use columns. It does not create database columns.

Use these instance methods when you need to inspect the routing decision:

```php
$article = new Article;

$article->isMetaField('subtitle'); // true when subtitle is a non-column input field and meta is enabled
$article->isTableField('title');   // true when title is in baseFillable
$article->getBaseFillable();       // the original fillable list captured by Resource
```

These methods classify declared field slugs without inspecting the database schema. For a custom-table resource with meta disabled, every declared input field counts as a table field. You must create the corresponding columns yourself.

## Define and save meta fields

Fields are plain arrays returned by `getFields()`. Use the fully qualified field class name in the `type` value.

```php
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
            ],
            [
                'name' => 'Subtitle',
                'slug' => 'subtitle',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Metadata',
                'slug' => 'metadata',
                'type' => 'Aura\\Base\\Fields\\Json',
            ],
        ];
    }
}
```

With the default settings, the title uses a column in the posts table. The subtitle and metadata use meta storage because they are not in the original fillable list.

```php
$article = Article::create([
    'title' => 'A stored title',
    'subtitle' => 'A stored subtitle',
    'metadata' => ['source' => 'docs'],
]);

$article->refresh();

$article->title;                 // reads the posts column
$article->subtitle;              // reads the meta field
$article->fields['subtitle'];    // reads the computed fields collection
$article->fields['metadata'];    // the Json field returns an array
```

The normal write API is an attribute assignment followed by `save()`. `create()` and `update()` use the same save pipeline:

```php
$article->subtitle = 'Updated subtitle';
$article->metadata = ['source' => 'api'];
$article->save();
```

The `fields` attribute returns a computed collection. During a save, Aura temporarily holds field input in an array under that attribute and removes it before Eloquent writes to the database. It is not a database column.

## Read and write the meta relation

Use `getMeta()` to read meta values without including table columns. With no argument, it returns a Laravel collection keyed by meta key. Pass a key to read one field's value, or `null` if that key is absent.

Aura passes each stored value through the declared field class's `get()` method before returning it. This is why JSON and checkbox fields return arrays even though their database value is a JSON string.

```php
$allMeta = $article->getMeta();
$subtitle = $article->getMeta('subtitle');
$metadata = $article->getMeta('metadata'); // ['source' => 'api']
```

When meta is disabled, reading meta returns an empty collection, even when you pass a key. The resource's `meta()` method also returns no relationship.

To inspect the relationship's table and foreign key, use `getMetaTable()` and `getMetaForeignKey()` only on a resource with meta enabled. The defaults are `meta` and `metable_id`.

For low-level access, `meta()` is a polymorphic `morphMany` relation:

```php
$article->meta()->updateOrCreate(
    ['key' => 'subtitle'],
    ['value' => 'Written directly'],
);
```

Direct relation writes bypass the field class's `set()` hook. Pass the representation that belongs in `value`, such as `json_encode($array)` for a JSON field. Prefer assigning the field and calling `save()` when the field's normal conversion and save hooks should run.

The loaded `meta` relation is a `MetaCollection`. Its property proxy returns the raw stored value:

```php
$rawMetadata = $article->meta->metadata; // raw value from meta.value
$metadata = $article->getMeta('metadata'); // value after Json::get()
```

Resources with meta enabled eager-load the relationship and hide it from their default array and JSON output. If your resource replaces `$hidden`, that property determines which attributes and relationships are hidden.

## The meta table and morph identity

The package migration creates one shared `meta` table:

| Column | Definition |
|---|---|
| `id` | Primary key. |
| `metable_type` | The owning resource's morph class. |
| `metable_id` | The owning resource's primary key. |
| `key` | Nullable indexed string containing the field slug. |
| `value` | Nullable `longText` containing the stored value. |

The migration creates and indexes the two owner columns with Laravel's `morphs('metable')` method. It adds a composite index on those columns and the field key. On MySQL, it also creates the `idx_meta_metable_id_key_value` prefix index, covering the owner ID, field key, and first 255 characters of the value. The meta model does not use timestamps.

Meta rows belong to their resource through a polymorphic relationship. The meta model, `Aura\Base\Models\Meta`, defines a `metable()` relationship using `morphTo`. Resources define the inverse with `morphMany(Meta::class, 'metable')`. This lets different resource classes use the same numeric ID without mixing their meta rows.

The owner type is the resource's morph class, which you can read with `$article->getMorphClass()`. By default, this is the full class name. An application's morph map can replace it with an alias.

The meta model allows mass assignment of the field key, value, and both owner columns. It stores the value without casting it. The field class, or code that writes the relationship directly, must serialize the value.

<a id="serialization-and-save-hooks"></a>

## Serialization and save hooks

The save pipeline runs the field class hooks before it writes a value:

1. `set()` transforms the submitted value.
2. A field `saving()` hook can change the resource or skip further work.
3. A base-fillable field is put back on the model as a table attribute. Other fields are queued for the saved phase when meta is enabled.
4. After the resource row is saved, a field's `saved()` hook can persist the value. If the field has no such hook and meta is enabled, Aura creates or updates the meta row for that key.
5. Aura fires the raw Eloquent `metaSaved` model event after the queued values finish.

Each field class converts its own values. JSON and checkbox fields encode arrays when writing and decode JSON strings when reading. Boolean fields convert values to booleans. The meta model stores the result as-is in its long-text value column.

Relationship fields can store their values elsewhere. Advanced select and tags fields use the `post_relations` pivot table for polymorphic relationships. If you set `polymorphic_relation => false` on an advanced select field, it stores and reads the selected IDs as JSON in meta instead.

To take over persistence for a declared field, add a `set{StudlySlug}Field()` method. A declared `price` field calls `setPriceField()` in the saved phase, after the resource row exists. The method must return the resource instance because the save pipeline keeps that return value.

```php
public function setPriceField($value)
{
    $this->meta()->updateOrCreate(
        ['key' => 'price'],
        ['value' => round((float) $value, 2)],
    );

    return $this;
}
```

If a setter handles a key that has no matching field class, Aura calls it during the `saving` phase instead. A new resource may not have an ID at that point.

Listen for the model event with its standard Eloquent event name:

```php
use App\Aura\Resources\Article;
use Illuminate\Support\Facades\Event;

Event::listen('eloquent.metaSaved: App\\Aura\\Resources\\Article', function (Article $article) {
    // The queued field save work has finished.
});
```

## Query meta values

Use Aura's meta query scopes to filter resources by stored meta values. The `AuraQueriesMeta` trait provides these scopes through the meta relationship. To filter a table column, use Eloquent's normal `where()` method.

```php
// Equality.
Article::whereMeta('subtitle', 'A stored subtitle')->get();

// Pass an Eloquent comparison operator as the middle argument.
Article::whereMeta('subtitle', 'like', 'Aura%')->get();

// One or more key/value pairs. Array pairs are ANDed.
Article::whereMeta([
    'featured' => true,
    'category' => 'news',
])->get();

// OR one condition with an existing query.
Article::whereMeta('featured', true)
    ->orWhereMeta('category', 'news')
    ->get();

// Match any value in a list.
Article::whereInMeta('category', ['news', 'updates'])->get();

// Exclude rows with a matching key and value.
Article::whereNotInMeta('category', ['internal', 'archived'])->get();
```

As shown above, `whereMeta()` and `orWhereMeta()` accept a key and value, an optional comparison operator between them, or an associative array.

The inclusion and exclusion scopes accept an array, a collection, or a single value. Aura treats a single value as a one-item list. The exclusion scope, `whereNotInMeta()`, uses Laravel's `whereDoesntHave`, so it also includes resources with no matching meta row.

For a JSON-encoded meta value, use `whereMetaContains()`:

```php
Article::whereMetaContains('topics', 'laravel')->get();
```

This scope uses Laravel's `whereJsonContains` against `meta.value` and checks both string and integer forms for numeric input. The stored value must be valid JSON, and the database driver must support JSON containment queries.

## Move data to another storage mode

Aura includes commands for legacy meta tables and posts-to-custom-table moves:

```bash
php artisan aura:migrate-post-meta-to-meta
php artisan aura:migrate-from-posts-to-custom-table "App\\Aura\\Resources\\Article"
php artisan aura:transfer-from-posts-to-custom-table "App\\Aura\\Resources\\Article"
```

The first command imports rows from the legacy `post_meta`, `team_meta`, and `user_meta` tables, if they exist. The second changes the resource class and generates a custom-table migration, then offers to run it and transfer data. The third copies the selected resource's posts and meta rows into its custom table.

Before switching a resource, review the generated schema and check whether its `$usesMeta` setting matches the storage you want.

See [Custom tables](/docs/custom-tables) for custom-table migrations and [Resources](/docs/resources) for the complete storage matrix.
