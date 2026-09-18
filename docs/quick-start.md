# Quick start

In this guide, you will build a resource for managing movies, create your first record in the admin panel, and learn how Aura stores the data.

## Before you start

Start with a working Aura installation and sign in as the administrator created during setup. Follow [Installation](/docs/installation) if the admin panel is not ready yet.

This guide uses the current development version. The [local-checkout installation](/docs/installation#local-checkout) matches the resource and generator behavior shown here. The public beta predates some of these changes.

The examples use the default `/admin` path with teams enabled. See [Installation](/docs/installation#without-teams) for the public beta's teams-off setup.

## Generate a resource

Run the resource generator from the root of the Laravel application:

```bash
php artisan aura:resource Movie
```

The command creates `app/Aura/Resources/Movie.php`, a class that extends Aura's base resource. It includes the resource type and URL slug, along with methods for defining fields, widgets, and an icon. The namespace follows the `aura-settings.paths.resources` setting.

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

Each field is an associative array with a display name, a slug used to identify its value, and a field type. Supply these with the `name`, `slug`, and `type` keys. The type must be the field class's fully qualified name.

Fields appear in the index table, forms, and record view by default. To hide a field in one of these places, set the corresponding `on_index`, `on_forms`, or `on_view` option to `false`. Fields only participate in global search when you set `searchable` to `true`.

The tags field uses Aura's built-in tag resource. Administrators can select existing tags or enter a new label to create one. Creating tags requires both the `create` option and the user's permission.

Aura discovers resource classes in `app/Aura/Resources` automatically, so you do not need to register them in a service provider. The movie resource receives these routes under the configured admin prefix:

| Route name | Default URL |
| --- | --- |
| `aura.movie.index` | `/admin/movie` |
| `aura.movie.create` | `/admin/movie/create` |
| `aura.movie.edit` | `/admin/movie/{id}/edit` |
| `aura.movie.view` | `/admin/movie/{id}` |

Open `/admin/movie`, choose **Create**, enter a title, and save the record. The index table shows your movie with the fields you left visible.

## Group fields with panels

A panel groups the fields that follow it until the next panel. It controls the layout without storing a value:

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

Tabs, groups, and repeaters use the same array format. See [Fields](/docs/fields) for their options.

## Understand storage

The generated resource uses the shared `posts` table and the `meta` table. The resource defaults are equivalent to:

```php
public static $customTable = false;
public static bool $usesMeta = true;
```

With shared storage, Aura saves core fields such as the title, content, status, and slug in columns on the posts table. Other input fields, including this movie's overview, release date, and rating, go into the meta table as key/value rows. You can read both kinds of values as model attributes:

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

Relationship fields store links between records. Tags and polymorphic advanced select fields use Aura's `post_relations` table, rather than a column on the posts table or a meta value. Each relationship field needs a related class in its `resource` option.

Advanced select fields use polymorphic relationships by default. To change how they select and relate records, set the `multiple` and `polymorphic_relation` options explicitly. See [Fields](/docs/fields) for supported configurations.

## Use a custom table

Use the generator's `--custom` flag when the resource should have its own table:

```bash
php artisan aura:resource Product --custom
```

The generated resource uses its own table and disables meta storage:

```php
public static $customTable = true;
public static bool $usesMeta = false;
protected $table = 'products';
```

In the product resource, replace the empty `getFields()` method with fields for a name and rating:

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

The migration generator uses your field definitions, the resource's loaded table name, and the current teams setting. Alongside the field columns, it adds an ID, a user ID, timestamps, and a team ID when teams are enabled. Review the generated migration before running it.

Because meta storage is disabled, each ordinary input field needs a matching column in the custom table. Adding fields later requires another migration. Keep relationship fields on shared storage unless you implement their custom persistence yourself.

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

The default sort must use a real column such as `id` or `created_at`, because Aura qualifies it with the resource table name. You cannot use a meta field as the default sort column. Grid and Kanban views also require their own resource methods. See [Table](/docs/table) for details.

Set the navigation group and label with static resource properties:

```php
protected static ?string $group = 'Content';
protected static ?int $sort = 10;
protected static bool $showInNavigation = true;
public static $pluralName = 'Movies';
public static $globalSearch = true;
```

Aura derives the plural label from the resource type unless you supply one. Resources are included in global search by default, with results drawn from the fields you marked as searchable.

## Grant permissions

Global Admins and users with a Super Admin role can access all resources. Other roles need permission for each resource. After adding a resource, generate any missing permissions, especially for teams that already existed:

```bash
php artisan aura:create-resource-permissions
```

The command uses the current authenticated user's team by default. Pass `--team=123` to target a specific team. The generated permissions cover listing, viewing, creating, updating, deleting, and scoping records. Their names combine the action and resource, such as `create-movie`. Assign them through [Roles and permissions](/docs/roles-permissions).

Aura provides the admin routes and resource UI. It does not generate a public REST API. Add Laravel routes and controllers in the host application when a public API is needed.

## Continue

- [Installation](/docs/installation) for beta and development-checkout setup, scripted installs, and setup without teams
- [Resources](/docs/resources) for resource properties and lifecycle methods
- [Fields](/docs/fields) for all field types and relationship options
- [Custom tables](/docs/custom-tables) for dedicated schemas and migrations
- [Roles and permissions](/docs/roles-permissions) for team access control
