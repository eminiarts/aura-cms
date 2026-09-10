# Quick start

Define a `Movie` resource, create a record in the admin panel, and see how Aura stores its field values.

## Before you start

Start with a working Aura installation and sign in as the administrator created during setup. Follow [Installation](/docs/installation) if the admin panel is not ready yet.

This guide uses the current development version. The [local-checkout installation](/docs/installation#local-checkout) matches the resource and generator behavior shown here. The public beta predates some of these changes.

The examples use the default `/admin` path and teams enabled. See [Installation](/docs/installation#without-teams) for the public beta's teams-off setup.

## Generate a resource

Run the resource generator from the root of the Laravel application:

```bash
php artisan aura:resource Movie
```

The command writes `app/Aura/Resources/Movie.php` with the namespace configured in `aura-settings.paths.resources`. The generated class extends `Aura\Base\Resource`, declares `$type` and `$slug`, and contains `getFields()`, `getWidgets()`, and `getIcon()` methods.

Keep the generated `getWidgets()` and `getIcon()` methods. Replace the empty `getFields()` method with the following definition:

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'searchable' => true,
        ],
        [
            'name' => 'Overview',
            'slug' => 'overview',
            'type' => 'Aura\\Base\\Fields\\Textarea',
            'on_index' => false,
        ],
        [
            'name' => 'Content',
            'slug' => 'content',
            'type' => 'Aura\\Base\\Fields\\Wysiwyg',
            'on_index' => false,
            'searchable' => true,
        ],
        [
            'name' => 'Release date',
            'slug' => 'release_date',
            'type' => 'Aura\\Base\\Fields\\Date',
            'validation' => 'nullable|date',
            'format' => 'Y-m-d',
            'display_format' => 'd.m.Y',
        ],
        [
            'name' => 'Rating',
            'slug' => 'rating',
            'type' => 'Aura\\Base\\Fields\\Number',
            'validation' => 'nullable|numeric|min:0|max:10',
        ],
        [
            'name' => 'Status',
            'slug' => 'status',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => [
                'draft' => 'Draft',
                'publish' => 'Published',
            ],
            'default' => 'draft',
        ],
        [
            'name' => 'Tags',
            'slug' => 'tags',
            'type' => 'Aura\\Base\\Fields\\Tags',
            'resource' => 'Aura\\Base\\Resources\\Tag',
            'create' => true,
            'on_index' => false,
        ],
    ];
}
```

Fields are plain associative arrays. Each field needs a `name`, `slug`, and fully qualified class name in `type`. The `on_index`, `on_forms`, and `on_view` flags default to `true`. Set one to `false` when the field should be hidden in that part of the admin UI. Set `searchable` to `true` for fields that should be included in global search.

The `Tags` field points at the package's `Aura\Base\Resources\Tag` resource. It lets an administrator select existing tags and, when `create` is true and the user has permission, create a tag from a new label.

Aura discovers resource classes in `app/Aura/Resources` automatically. No service provider entry is required. The generated `Movie` resource receives these routes under the configured admin prefix:

| Route name | Default URL |
| --- | --- |
| `aura.movie.index` | `/admin/movie` |
| `aura.movie.create` | `/admin/movie/create` |
| `aura.movie.edit` | `/admin/movie/{id}/edit` |
| `aura.movie.view` | `/admin/movie/{id}` |

Open `/admin/movie`, choose **Create**, enter a title, and save the record. The index table shows the fields whose `on_index` value is not `false`.

## Group fields with panels

`Panel` is a layout field. It groups the fields that follow it until the next panel and does not store a value itself:

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Details',
            'slug' => 'details',
            'type' => 'Aura\\Base\\Fields\\Panel',
            'style' => ['width' => '70'],
        ],
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
        ],
        [
            'name' => 'Overview',
            'slug' => 'overview',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ],
        [
            'name' => 'Sidebar',
            'slug' => 'sidebar',
            'type' => 'Aura\\Base\\Fields\\Panel',
            'style' => ['width' => '30'],
        ],
        [
            'name' => 'Status',
            'slug' => 'status',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => [
                'draft' => 'Draft',
                'publish' => 'Published',
            ],
        ],
    ];
}
```

`Tab`, `Tabs`, `Group`, and `Repeater` use the same array field format. See [Fields](/docs/fields) for the field-specific options.

## Understand storage

The generated resource uses the shared `posts` table and the `meta` table. The resource defaults are equivalent to:

```php
public static $customTable = false;
public static bool $usesMeta = true;
```

In the shared-table profile, Aura writes core slugs such as `title`, `content`, `status`, and `slug` to columns in `posts`. Other ordinary input field slugs, such as `overview`, `release_date`, and `rating`, are stored as key/value rows in `meta`. You can still read them as model attributes:

```php
use App\Aura\Resources\Movie;

$movie = Movie::firstOrFail();

$movie->title;                  // posts.title
$movie->overview;               // resolved from meta
$movie->fields;                 // the computed field map
$movie->tags;                   // a collection resolved through post_relations
```

Use normal Eloquent conditions for real table columns and Aura's meta scopes for meta values:

```php
$published = Movie::where('status', 'publish')->get();

$ratedNine = Movie::whereMeta('rating', '9')->get();

$releasedSince = Movie::whereMeta('release_date', '>=', '2026-01-01')->get();

$matching = Movie::whereMeta([
    'rating' => '9',
    'release_date' => '2026-01-01',
])->get();
```

Meta values are stored as text. Use a custom table when a field needs a native column type, database indexes, or normal `where()` queries.

Relationship fields have their own storage behavior. `Tags` and polymorphic `AdvancedSelect` fields write links to Aura's `post_relations` table. They do not create a `tags` or `actors` column in `posts` and they do not become ordinary meta key/value rows. A relationship field needs a `resource` class. `AdvancedSelect` uses a polymorphic relation by default; set its `multiple` and `polymorphic_relation` options explicitly when you need a different mode. See [Fields](/docs/fields) for the supported relationship configurations.

## Use a custom table

Use the generator's `--custom` flag when the resource should have its own table:

```bash
php artisan aura:resource Product --custom
```

The custom stub sets all three storage declarations:

```php
public static $customTable = true;
public static bool $usesMeta = false;
protected $table = 'products';
```

Replace `Product`'s empty `getFields()` method with this ordinary-column example:

```php
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
            'name' => 'Rating',
            'slug' => 'rating',
            'type' => 'Aura\\Base\\Fields\\Number',
            'validation' => 'nullable|numeric|min:0|max:10',
        ],
    ];
}
```

Generate a migration from the resource and run it:

```bash
php artisan aura:create-resource-migration 'App\Aura\Resources\Product'
php artisan migrate
```

`aura:create-resource-migration` reads `getFields()`, the resource's loaded table name, and the current teams setting. It adds the field columns plus `id`, `user_id`, `team_id` when teams are enabled, and timestamps. Review the generated migration before running it. With `$usesMeta = false`, ordinary input field slugs must be columns in the custom table, so later field additions require another migration. Keep relation fields on the shared storage profile unless you define their custom persistence yourself.

Custom-table fields use ordinary Eloquent queries:

```php
use App\Aura\Resources\Product;

$products = Product::where('rating', '>=', 8)->get();
```

Do not use `--custom-table`. The generator flag is `--custom`, and the custom stub already sets `$usesMeta = false`.

## Customize the index table and navigation

Override the table defaults on the resource:

```php
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
    return 'desc';
}

public function defaultTableView()
{
    return 'list';
}
```

The sort value is qualified with the resource table name, so it must be a real column such as `id` or `created_at`. A meta field slug cannot be the default sort column. Grid and Kanban views need the corresponding resource methods as well. See [Table](/docs/table).

Set the navigation group and label with static resource properties:

```php
protected static ?string $group = 'Content';
protected static ?int $sort = 10;
protected static bool $showInNavigation = true;
public static $pluralName = 'Movies';
public static $globalSearch = true;
```

The default plural label comes from the resource type. A resource is included in global search by default, but a field only participates when its definition has `'searchable' => true`.

## Grant permissions

Global Admins and users with a Super Admin role have blanket access to resources. Other roles need resource permissions. Generate missing permissions after adding a resource, especially when the team already existed:

```bash
php artisan aura:create-resource-permissions
```

The command uses the current authenticated user's team by default. Pass `--team=123` to target a specific team. It creates permissions such as `viewAny-movie`, `view-movie`, `create-movie`, `update-movie`, `delete-movie`, and `scope-movie`. Assign them through [Roles and permissions](/docs/roles-permissions).

Aura provides the admin routes and resource UI. It does not generate a public REST API. Add Laravel routes and controllers in the host application when a public API is needed.

## Continue

- [Installation](/docs/installation) for beta and development-checkout setup, scripted installs, and Teams-off mode
- [Resources](/docs/resources) for resource properties and lifecycle methods
- [Fields](/docs/fields) for all field types and relationship options
- [Custom tables](/docs/custom-tables) for dedicated schemas and migrations
- [Roles and permissions](/docs/roles-permissions) for team access control
