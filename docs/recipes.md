# Recipes

These examples use the current `Aura\Base\Resource` contract. Resource field definitions are plain arrays returned by `getFields()`. The `type` value is a field class, written as a fully qualified class name or a `::class` constant.

The examples assume that Aura is installed and that the configured resource directory is `app/Aura/Resources`. See [Installation](/docs/installation) and [Creating resources](/docs/creating-resources) for setup.

## Choose storage before writing queries

A normal Resource uses the shared `posts` table and the `meta` table:

```php
public static $customTable = false;

public static bool $usesMeta = true;
```

The shared `posts` migration has these columns:

```text
id, title, content, type, status, slug, user_id, parent_id, order,
created_at, updated_at, deleted_at
```

It adds `team_id` when `config('aura.teams')` is enabled. A field whose slug is in the Resource's original `$fillable` list is stored in the Resource table. With `$usesMeta = true`, other input field slugs are stored as rows in `meta`. The `fields` array used during the save pipeline is a transport value and is removed before the SQL write.

Use `isTableField()` and `isMetaField()` when code needs to make the same decision as Aura:

```php
$resource = new BlogPost;

$resource->isTableField('status');       // true
$resource->isMetaField('published_at'); // true
```

The query must use the matching storage path. `where()` and `orderBy()` address table columns. The meta scopes address `meta` rows:

```php
use App\Aura\Resources\BlogPost;

$featured = BlogPost::query()
    ->where('status', 'published')
    ->whereMeta('is_featured', true)
    ->orderByDesc('created_at')
    ->get();

$withAnyTagId = BlogPost::query()
    ->whereMetaContains('related_ids', 42)
    ->get();
```

`whereMeta()` accepts `($key, $value)`, `($key, $operator, $value)`, or an array of key/value pairs. `whereMetaContains()` is for a JSON array stored in one meta value. `whereInMeta()`, `whereNotInMeta()`, and `orWhereMeta()` are also available. These scopes use the Resource's `meta()` relation, so they are only available when the Resource uses meta.

For a date that must be compared in SQL, choose an ordered storage format and query the same format. The `Date` field defaults to `d.m.Y`, which is not an ordered database representation:

```php
[
    'name' => 'Published date',
    'slug' => 'published_at',
    'type' => 'Aura\\Base\\Fields\\Date',
    'format' => 'Y-m-d',
    'display_format' => 'd.m.Y',
    'validation' => 'nullable|date',
]
```

```php
$published = BlogPost::query()
    ->where('status', 'published')
    ->whereMeta('published_at', '<=', today()->toDateString())
    ->get();
```

Resource queries also carry Aura's normal type, team, and scoped ownership behavior. A custom table used with teams still needs a `team_id` column. A role with the `scope-{resource-slug}` permission can add a `user_id` condition through `ScopedScope`. Do not remove those scopes just to make a query return more rows. See [Roles and permissions](/docs/roles-permissions) for the policy and role rules.

## Blog posts with tags, status, and an image

Generate the two resource classes, then replace their field definitions with these examples:

```bash
php artisan aura:resource Category
php artisan aura:resource BlogPost
```

`Tags` needs a related `resource` class. Marking the category Resource as a taxonomy lets Aura treat the field as a taxonomy field in the table filters.

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Category extends Resource
{
    public static string $type = 'Category';

    public static ?string $slug = 'category';

    public static $taxonomy = true;

    protected static ?string $group = 'Blog';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Slug',
                'slug' => 'slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'based_on' => 'title',
            ],
        ];
    }
}
```

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class BlogPost extends Resource
{
    public static string $type = 'BlogPost';

    public static ?string $slug = 'blogpost';

    public static $singularName = 'Blog post';

    public static $pluralName = 'Blog posts';

    protected static ?string $group = 'Blog';

    protected static ?int $sort = 10;

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Content',
                'slug' => 'content',
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'validation' => 'nullable',
                'searchable' => true,
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'default' => 'draft',
                'on_index' => true,
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft', 'color' => 'bg-gray-100 text-gray-800'],
                    ['key' => 'published', 'value' => 'Published', 'color' => 'bg-green-100 text-green-800'],
                ],
            ],
            [
                'name' => 'Published date',
                'slug' => 'published_at',
                'type' => 'Aura\\Base\\Fields\\Date',
                'format' => 'Y-m-d',
                'display_format' => 'd.m.Y',
                'validation' => 'nullable|date',
                'on_index' => true,
            ],
            [
                'name' => 'Featured',
                'slug' => 'is_featured',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'default' => false,
                'on_index' => true,
            ],
            [
                'name' => 'Featured image',
                'slug' => 'featured_image',
                'type' => 'Aura\\Base\\Fields\\Image',
                'validation' => 'nullable',
                'max_files' => 1,
            ],
            [
                'name' => 'Categories',
                'slug' => 'categories',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => Category::class,
                'create' => true,
                'max_tags' => 10,
            ],
        ];
    }
}
```

`AuraResourceIdentity` declares `$singularName` and `$pluralName` without property types, so keep those two declarations untyped. The typed `$type`, `$slug`, `$group`, and `$sort` declarations above match the base signatures.

The title, content, and status values in this example are columns on `posts`. `published_at`, `is_featured`, and `featured_image` are meta values. `categories` is saved through the `post_relations` pivot. Aura's `Tags::saved()` hook re-reads submitted IDs through the related Resource's scoped query. A text label becomes a Category only when `create` is enabled and the current user can create that Resource.

Query table and meta values together, then constrain the tag relation with `whereHas()`:

```php
use App\Aura\Resources\BlogPost;

$posts = BlogPost::query()
    ->where('status', 'published')
    ->whereMeta('published_at', '<=', today()->toDateString())
    ->whereMeta('is_featured', true)
    ->whereHas('categories', fn ($query) => $query->where('slug', 'laravel'))
    ->with('categories')
    ->latest('created_at')
    ->paginate(10);
```

On a saved post, `$post->categories` is a Collection of Category models. The computed `$post->fields['categories']` value is the array of related IDs used by the field. The `Tags` relation is identified by its field slug, so two tag fields pointing at the same Resource remain separate.

Set `'searchable' => true` on each field that should participate in the Resource table search. Global search uses the same field definitions and chooses a table column or a meta lookup with `isMetaField()`. A Resource can disable global search with `public static $globalSearch = false`. The built-in global search also skips several internal Resource slugs, including `product`.

## Render an image field

The `Image` field stores a JSON array of Attachment IDs. It does not store an image URL in the Resource value. Resolve the ID through the configured Attachment Resource:

```php
use Aura\Base\Resources\Attachment;

$attachmentClass = config('aura.resources.attachment', Attachment::class);
$ids = $post->featured_image ?? [];
$attachment = $ids[0] ?? null
    ? $attachmentClass::find($ids[0])
    : null;

if ($attachment) {
    $originalUrl = $attachment->path();
    $mediumUrl = $attachment->thumbnail('md');
}
```

The default media configuration defines `xs`, `sm`, `md`, `lg`, and `thumbnail` dimensions. `thumbnail($size)` falls back to `path()` when the Attachment is not an image or the requested size is not configured. In Blade, keep the ID check and the scoped Attachment lookup:

```blade
@php($ids = $post->featured_image ?? [])
@php
    $attachmentClass = config('aura.resources.attachment', \Aura\Base\Resources\Attachment::class);
    $image = isset($ids[0]) ? $attachmentClass::find($ids[0]) : null;
@endphp
@if ($image)
    <img src="{{ $image->thumbnail('md') }}" alt="{{ $post->title }}">
@endif
```

For a multi-image field, iterate over every ID and resolve each Attachment. The built-in table uses the same Attachment Resource and batches display lookups through `PreloadsTableDisplay`.

## Use relationship fields

Relationship field classes have different storage behavior. Choose the field from the query and value shape you need.

### Searchable selections with `AdvancedSelect`

Set `polymorphic_relation => true`, `multiple => true`, and `return_type => 'object'` when an `AdvancedSelect` field should use the `post_relations` pivot and return related models:

```php
[
    'name' => 'Related products',
    'slug' => 'related_products',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => Category::class,
    'multiple' => true,
    'return_type' => 'object',
    'polymorphic_relation' => true,
]
```

With `polymorphic_relation` enabled, `whereHas('related_products')`, `with('related_products')`, and `$record->related_products` use the pivot relation. With `return_type => 'object'` and `multiple => true`, the property is a Collection. With `multiple => false`, it is one model or `null`. Set `polymorphic_relation => false` only when an ID or JSON list in meta is enough. That mode has no Eloquent relation for `whereHas()` or `with()`.

### Inverse lists with `HasMany`

Use `column` when the child Resource has a real foreign-key column:

```php
[
    'name' => 'Reviews',
    'slug' => 'reviews',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => Review::class,
    'column' => 'product_id',
]
```

The field resolves to `Product::hasMany(Review::class, 'product_id')` and stores no value on the Product row. The child table must contain `product_id`. For a pivot-backed inverse, use `reverse => true` and set `reverse_slug` to the field slug used on the other side. `foreign_key` is used for the create link and does not choose the relationship column.

### Scalar IDs with `BelongsTo`

`BelongsTo` is an input field that stores one related ID. Its `resource` key supplies the picker and display target:

```php
[
    'name' => 'Author',
    'slug' => 'author_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => Author::class,
    'on_index' => true,
]
```

On a posts-backed Resource with meta enabled, `author_id` is a meta value unless it is in the Resource's original `$fillable` list. Read the ID and resolve it through the related Resource:

```php
use App\Aura\Resources\Author;

$author = $post->author_id
    ? Author::find($post->author_id)
    : null;

$posts = $author
    ? BlogPost::whereMeta('author_id', $author->id)->get()
    : collect();
```

`BelongsTo` does not create a dynamic Eloquent relation named after the field. Use `AdvancedSelect` with `multiple => false` when you need a queryable polymorphic relation.

`HasOne` extends `AdvancedSelect` and has a single-value relation contract, but the current `aura::fields.has-one` editor view only renders the literal text "Has one". Use `AdvancedSelect` with `multiple => false` for a working selector until that view is replaced.

## Build a custom-table catalog

Use a custom table when the fields should be physical columns and you need normal SQL indexes, constraints, or column queries. Generate the stub first:

```bash
php artisan aura:resource Product --custom
```

The current custom stub sets `$customTable = true`, `$usesMeta = false`, and `$table = 'products'`. Add fields, then create and review the migration:

```bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
php artisan migrate
```

This example keeps every input field on the `products` table:

```php
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

    protected $casts = [
        'price_cents' => 'integer',
        'stock' => 'integer',
    ];

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
                'name' => 'SKU',
                'slug' => 'sku',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|max:100',
                'on_index' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Price in cents',
                'slug' => 'price_cents',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required|integer|min:0',
                'on_index' => true,
            ],
            [
                'name' => 'Stock',
                'slug' => 'stock',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required|integer|min:0',
                'on_index' => true,
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Select',
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft'],
                    ['key' => 'active', 'value' => 'Active'],
                ],
                'default' => 'draft',
                'on_index' => true,
            ],
        ];
    }
}
```

The migration must contain every input slug because `$usesMeta` is false:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('products', function (Blueprint $table): void {
    $table->id();
    $table->string('name');
    $table->string('sku')->nullable();
    $table->integer('price_cents')->default(0);
    $table->integer('stock')->default(0);
    $table->string('status')->default('draft');
    $table->foreignId('user_id')->nullable();

    if (config('aura.teams')) {
        $table->foreignId('team_id')->nullable();
    }

    $table->timestamps();
});
```

The migration command adds `id`, input field columns, `user_id`, `team_id` when teams are enabled, and nullable timestamps. It does not add indexes, foreign-key constraints, soft deletes, or defaults for you. Review the generated migration before running it.

Query the catalog with ordinary Eloquent clauses:

```php
$products = Product::query()
    ->where('status', 'active')
    ->where('stock', '>', 0)
    ->orderBy('price_cents')
    ->get();
```

If the custom table should use both columns and meta, set `$usesMeta = true` and declare the column-backed fields in the original `$fillable` property. In that mode, only those original fillable fields use the custom table. Other input fields use `meta`. Do not add a `$customMeta` or `$usesCustomMeta` property. Aura does not read either name.

## Normalize or format a field value

On a posts-backed Resource, Aura runs a field definition's `set` closure before the field class's own `set()` hook. A `display` closure formats a value for tables and record pages without changing the stored value:

```php
[
    'name' => 'SKU',
    'slug' => 'sku',
    'type' => 'Aura\\Base\\Fields\\Text',
    'set' => static fn ($resource, $field, $value) => $value === null ? null : strtoupper(trim((string) $value)),
    'display' => static fn ($value, $resource) => e($value === null ? '' : (string) $value),
]
```

The `display` closure returns table markup, so escape untrusted text with `e()` as the example does. The field hooks are conventions, not abstract methods on `Field`. Field classes may implement `get`, `set`, `saving`, `saved`, `display`, and `api`. Use a field class's `saved()` hook for work that needs the saved row, such as syncing a relation. Custom-table create and edit components pass field values directly to Eloquent. They do not pack them into the posts Resource `fields` payload, so a field definition's `set` closure is not a substitute for an Eloquent cast or mutator on a custom-table Resource. Use `Gate` in application code for the permission check before a custom action:

```php
use Illuminate\Support\Facades\Gate;

Gate::authorize('update', $post);
$post->update(['status' => 'published']);
```

Create the standard Resource permission rows after adding a Resource:

```bash
php artisan aura:create-resource-permissions
```

The generated grants use the Resource's static slug, for example `viewAny-blogpost`, `view-blogpost`, `create-blogpost`, `update-blogpost`, and `delete-blogpost`. A policy check still goes through Laravel's `view`, `viewAny`, `create`, `update`, and `delete` abilities. Do not replace those checks with a raw permission string or bypass the Resource's global scopes. See [Roles and permissions](/docs/roles-permissions) for custom abilities and scoped access.

## Related

- [Resources](/docs/resources) for the Resource contract and static properties
- [Fields](/docs/fields) for field options and relationship behavior
- [Meta fields](/docs/meta-fields) for the complete meta query scope reference
- [Custom tables](/docs/custom-tables) for migration and storage details
- [Media library](/docs/media-manager) for uploads and Attachment configuration
