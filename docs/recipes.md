# Recipes

These recipes show how to build and query resources using Aura's current resource API. Define fields as plain arrays returned by `getFields()`. Each field's `type` identifies its class, either by its fully qualified name or a `::class` constant.

The examples assume that Aura is installed and that the configured resource directory is `app/Aura/Resources`. See [Installation](/docs/installation) and [Creating resources](/docs/creating-resources) for setup.

## Choose storage before writing queries

By default, a resource stores its values across the shared `posts` and `meta` tables:

```php
public static $customTable = false;

public static bool $usesMeta = true;
```

The shared `posts` migration has these columns:

```text
id, title, content, type, status, slug, user_id, parent_id, order,
created_at, updated_at, deleted_at
```

When teams are enabled, the migration also adds a `team_id` column. Fields listed in the resource's original `$fillable` property use columns in the resource table. With meta enabled, Aura stores the remaining input fields as rows in the meta table. The temporary `fields` array carries values during saving and is removed before the SQL write.

Use `isTableField()` and `isMetaField()` when code needs to make the same decision as Aura:

```php
$resource = new BlogPost;

$resource->isTableField('status');       // true
$resource->isMetaField('published_at'); // true
```

Query each value according to where it is stored. Use ordinary Eloquent methods for table columns and Aura's meta scopes for meta values:

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

The `whereMeta()` scope accepts a key and value, a key with an operator and value, or an array of key/value pairs. Use `whereMetaContains()` to search a JSON array stored in one meta value. These scopes require a resource that uses meta.

For membership and alternative conditions, see `whereInMeta()`, `whereNotInMeta()`, and `orWhereMeta()` in the [meta query reference](/docs/meta-fields).

To compare dates in SQL, store and query them in a format that sorts chronologically. The date field defaults to `d.m.Y`, which does not sort in date order. This definition stores year-first dates while keeping the day-first display:

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

Resource queries respect Aura's type, team, and ownership scopes. Custom tables still need a `team_id` column when teams are enabled. A role with the `scope-{resource-slug}` permission can restrict results to records owned by the user through the `user_id` column. Keep these scopes in place. See [Roles and permissions](/docs/roles-permissions) for the policy and role rules.

## Blog posts with tags, status, and an image

Generate the two resource classes, then replace their field definitions with these examples:

```bash
php artisan aura:resource Category
php artisan aura:resource BlogPost
```

A tags field needs a related resource class. In this example, categories are marked as a taxonomy so Aura can offer taxonomy filtering in the table.

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

Keep the singular and plural name properties untyped to match their declarations in the base resource. The other typed properties in this example also match their base declarations.

The title, content, and status use columns in the posts table. The published date, featured flag, and image use meta storage. Categories use the `post_relations` pivot table.

When saving tags, Aura resolves the submitted IDs through the related resource's scoped query. It creates a category from a text label only when the field allows creation and the current user has permission to create categories.

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

On a saved post, `$post->categories` returns a collection of category models. The field's computed value, `$post->fields['categories']`, contains only their IDs. Each tags relation uses its field slug, so two tag fields can point to the same resource without sharing their selections.

Set `'searchable' => true` on fields that users should be able to search in the resource table. Global search uses the same definitions and checks each field's storage to query either a table column or meta. To exclude a resource from global search, declare `public static $globalSearch = false`. Built-in global search also skips several internal resource slugs, including `product`.

## Render an image field

An image field stores a JSON array of attachment IDs. To get an image URL, look up the attachment through the configured attachment resource:

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

The default media configuration includes five sizes: `xs`, `sm`, `md`, `lg`, and `thumbnail`. If the attachment is not an image or the requested size is not configured, `thumbnail($size)` returns the original path. In Blade, check for an ID and resolve the attachment with its scopes intact:

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

For a field with multiple images, look up each attachment ID. Aura's built-in table uses the same attachment resource and batches these lookups through `PreloadsTableDisplay`.

## Use relationship fields

Relationship fields differ in how they store values and support queries. Choose one based on whether you need related models, a list of children, or a single ID.

### Searchable selections with `AdvancedSelect`

An advanced select field can store relationships in the pivot table and return the selected models. Enable polymorphic relations, allow multiple selections, and request objects as shown below:

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

With polymorphic relations enabled, you can filter with `whereHas('related_products')`, eager load with `with('related_products')`, or read the related models from the record. When the return type is `object`, multiple selections return a collection. A single selection returns one model or `null`.

Set `polymorphic_relation => false` if you only need an ID or JSON list in meta. That mode does not provide an Eloquent relation for filtering or eager loading.

### Inverse lists with `HasMany`

Use the `column` option when the child resource has a foreign-key column:

```php
[
    'name' => 'Reviews',
    'slug' => 'reviews',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => Review::class,
    'column' => 'product_id',
]
```

This defines a has-many relation through the child table's `product_id` column. It stores no value on the product row, and the child table must contain that column.

For an inverse relation through the pivot table, set `reverse => true` and use `reverse_slug` to identify the field on the other side. The separate `foreign_key` option controls the create link. It does not select the relationship column.

### Scalar IDs with `BelongsTo`

A belongs-to field stores one related ID. Its `resource` option determines which records appear in the picker and how the selection is displayed:

```php
[
    'name' => 'Author',
    'slug' => 'author_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => Author::class,
    'on_index' => true,
]
```

For a resource using the posts table with meta enabled, the author ID goes into meta unless it is in the original `$fillable` list. Read that ID and look up the related resource:

```php
use App\Aura\Resources\Author;

$author = $post->author_id
    ? Author::find($post->author_id)
    : null;

$posts = $author
    ? BlogPost::whereMeta('author_id', $author->id)->get()
    : collect();
```

A belongs-to field does not create an Eloquent relation named after the field. If you need to query a polymorphic relation, use an advanced select field with `multiple => false`.

The has-one field extends advanced select and supports a single related value. Its current editor view only renders the text "Has one", however. Until that view is replaced, use an advanced select field with `multiple => false` for a working selector.

## Build a custom-table catalog

Use a custom table when the fields should be physical columns and you need normal SQL indexes, constraints, or column queries. Generate the stub first:

```bash
php artisan aura:resource Product --custom
```

The generated stub uses the products table with meta storage disabled. Add your fields, then generate the migration. Review it before migrating:

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

With meta disabled, every input field needs a column matching its slug:

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

The migration command adds a primary key, input field columns, a user ID, and nullable timestamps. It also adds a team ID when teams are enabled. Add any indexes, foreign-key constraints, soft deletes, or defaults yourself before running the migration.

Query the catalog with ordinary Eloquent clauses:

```php
$products = Product::query()
    ->where('status', 'active')
    ->where('stock', '>', 0)
    ->orderBy('price_cents')
    ->get();
```

To combine a custom table with meta storage, set `$usesMeta = true` and list the table fields in the original `$fillable` property. Only those fields use table columns. The remaining input fields use meta. Aura does not recognize properties named `$customMeta` or `$usesCustomMeta`.

## Normalize or format a field value

For resources using the posts table, a field definition can normalize input with a `set` closure. Aura runs it before the field class's own setter hook. A `display` closure formats the value in tables and record pages without changing what is stored:

```php
[
    'name' => 'SKU',
    'slug' => 'sku',
    'type' => 'Aura\\Base\\Fields\\Text',
    'set' => static fn ($resource, $field, $value) => $value === null ? null : strtoupper(trim((string) $value)),
    'display' => static fn ($value, $resource) => e($value === null ? '' : (string) $value),
]
```

The display closure returns table markup. Escape untrusted text with `e()`, as shown above. Field classes can also implement hooks for reading, writing, saving, display, and API output: `get`, `set`, `saving`, `saved`, `display`, and `api`. These are conventions rather than abstract methods on the base field class. Use `saved()` for work that needs the saved row, such as syncing a relation.

Custom-table create and edit forms pass values directly to Eloquent, without the posts resource's temporary fields array. For these resources, use an Eloquent cast or mutator to normalize input. A field definition's setter closure does not replace one.

Before running a custom action, authorize it through Laravel's gate:

```php
use Illuminate\Support\Facades\Gate;

Gate::authorize('update', $post);
$post->update(['status' => 'published']);
```

After adding a resource, generate its standard permission rows:

```bash
php artisan aura:create-resource-permissions
```

Generated permissions include the resource's static slug, such as `update-blogpost`. The same naming applies to viewing lists, viewing records, creating, and deleting. In application code, continue to authorize through Laravel's policy abilities, such as `update` in the example above. Keep resource scopes intact and do not substitute raw permission strings for policy checks. See [Roles and permissions](/docs/roles-permissions) for custom abilities and scoped access.

## Related

- [Resources](/docs/resources) for the resource API and static properties
- [Fields](/docs/fields) for field options and relationship behavior
- [Meta fields](/docs/meta-fields) for the complete meta query scope reference
- [Custom tables](/docs/custom-tables) for migration and storage details
- [Media library](/docs/media-manager) for uploads and attachment configuration
