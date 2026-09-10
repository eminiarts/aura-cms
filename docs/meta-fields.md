# Meta fields

Aura stores a resource's field values in real table columns or in rows in a shared `meta` table. A posts-backed resource uses the shared `posts` table for its core columns. Declared fields whose slugs are not base-table columns use meta storage when `$usesMeta` is enabled.

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

`$customTable` chooses the resource table. It does not enable or disable meta. `$usesMeta` controls whether the resource has a meta relationship and whether non-column fields use meta. Aura exposes the same decisions through `usesCustomTable()` and `usesMeta()`.

| `$customTable` | `$usesMeta` | Storage |
|---|---|---|
| `false` | `true` | Base columns in `posts`; other declared input fields in `meta`. |
| `false` | `false` | Base `posts` columns only. The generic meta path is disabled. |
| `true` | `true` | Original `$fillable` columns in the custom table; other declared input fields in `meta`. |
| `true` | `false` | The custom table only. Every declared input field needs a physical column. |

The defaults are `false` for `$customTable` and `true` for `$usesMeta`. A custom-table resource that should be column-only must set `$usesMeta = false` and create a column for every input field.

These rules describe ordinary field persistence. A field class with a `saved()` hook can route a relation to `post_relations` or another store, as described below.

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

At construction time, `Resource` stores the original `$fillable` array as `baseFillable`, then merges the declared input-field slugs into Eloquent's fillable list. The original `baseFillable` list decides whether a field is a table field when meta is enabled. The merge makes field input assignable. It does not create database columns.

Use these instance methods when you need to inspect the routing decision:

```php
$article = new Article;

$article->isMetaField('subtitle'); // true when subtitle is a non-column input field and meta is enabled
$article->isTableField('title');   // true when title is in baseFillable
$article->getBaseFillable();       // the original fillable list captured by Resource
```

`isMetaField()` and `isTableField()` classify declared field slugs. They do not inspect the database schema. In custom-table, no-meta mode, `isTableField()` returns true for every declared input slug, so the corresponding columns must exist.

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

With the default posts-backed flags, `title` is a `posts` column. `subtitle` and `metadata` are meta fields because they are not in the base fillable list.

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

The `fields` attribute is a computed collection. Aura uses a transient `fields` array while saving field input, then removes it before Eloquent writes SQL. There is no `fields` column in the package schema.

## Read and write the meta relation

`getMeta()` reads only meta rows. It does not include values from table columns. With no argument it returns an `Illuminate\Support\Collection` keyed by meta key. With a key it returns that field's value or `null` when the key is absent.

Aura passes each stored value through the declared field class's `get()` method before returning it. This is why JSON and checkbox fields return arrays even though their database value is a JSON string.

```php
$allMeta = $article->getMeta();
$subtitle = $article->getMeta('subtitle');
$metadata = $article->getMeta('metadata'); // ['source' => 'api']
```

When meta is disabled, `getMeta()` and `getMeta('any-key')` return an empty collection. `meta()` also returns no relationship in that mode. Call `getMetaTable()` and `getMetaForeignKey()` only on a meta-enabled resource. They return `meta` and `metable_id` for the package's default relation.

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

`Resource` eager-loads `meta` for meta-enabled resources and hides the relation from its default array and JSON output. A subclass that replaces `$hidden` controls that list itself.

## The meta table and morph identity

The package migration creates one shared `meta` table:

| Column | Definition |
|---|---|
| `id` | Primary key. |
| `metable_type` | The owning resource's morph class. |
| `metable_id` | The owning resource's primary key. |
| `key` | Nullable indexed string containing the field slug. |
| `value` | Nullable `longText` containing the stored value. |

`$table->morphs('metable')` creates and indexes `metable_type` and `metable_id`. The migration also adds a composite index on `metable_type`, `metable_id`, and `key`. On MySQL it adds the `idx_meta_metable_id_key_value` prefix index on `metable_id`, `key`, and the first 255 characters of `value`. The `Meta` model has timestamps disabled.

The `metable()` relation on `Aura\Base\Models\Meta` is a `morphTo`. Aura's resource relation is `morphMany(Meta::class, 'metable')`, so the same numeric ID can be used by different resource classes without mixing their rows. The value in `metable_type` is the resource's morph class, returned by `$article->getMorphClass()`. It is normally the full class name. An application's morph map can replace it with an alias.

The `Meta` model allows `key`, `value`, `metable_type`, and `metable_id` for mass assignment. It does not cast `value`. Serialization belongs to the field class or to code that writes the relation directly.

## Serialization and save hooks

The save pipeline runs the field class hooks before it writes a value:

1. `set()` transforms the submitted value.
2. A field `saving()` hook can change the resource or skip further work.
3. A base-fillable field is put back on the model as a table attribute. Other fields are queued for the saved phase when meta is enabled.
4. After the resource row is saved, a field `saved()` hook can persist the value. If it has no `saved()` hook, Aura calls `meta()->updateOrCreate(['key' => $key], ['value' => $value])` for a meta-enabled resource.
5. Aura fires the raw Eloquent `metaSaved` model event after the queued values finish.

The field class owns conversion. For example, `Json::set()` and `Checkbox::set()` JSON-encode arrays, and their `get()` methods decode JSON strings. `Boolean` converts values to booleans. The `Meta` model stores the resulting value as-is in its `longText` column.

Relation fields can use another storage path. `AdvancedSelect` and `Tags` use the `post_relations` pivot for their polymorphic relations. `AdvancedSelect` with `polymorphic_relation => false` stores its selected IDs as a JSON value in meta and reads that value back from meta.

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

Aura adds query scopes to resources through `AuraQueriesMeta`. The scopes compare the stored `meta.value` and use the `meta` relation. Query a real table column with Eloquent's normal `where()` instead.

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

`whereMeta()` and `orWhereMeta()` accept `key, value`, `key, operator, value`, or one associative array. `whereInMeta()` and `whereNotInMeta()` accept an array, a collection, or a scalar that Aura wraps in a one-item list. `whereNotInMeta()` uses `whereDoesntHave`, so a resource without a matching meta row also passes the scope.

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

`aura:migrate-post-meta-to-meta` imports rows from existing `post_meta`, `team_meta`, and `user_meta` tables when those tables exist. `aura:migrate-from-posts-to-custom-table` changes the resource class, generates a custom-table migration, and offers to run it and transfer data. `aura:transfer-from-posts-to-custom-table` copies rows for the selected resource from `posts` and `meta` into its custom table. Review the generated schema and the resource's `$usesMeta` value before switching a resource.

See [Custom tables](/docs/custom-tables) for custom-table migrations and [Resources](/docs/resources) for the complete storage matrix.
