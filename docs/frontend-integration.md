# Frontend integration

Aura provides an authenticated admin panel and Eloquent resource classes. You build the public site or API in your Laravel application, including routes, authentication, publication rules, team selection, rate limiting, and media access.

This guide explains how to read Aura data in Blade views and API endpoints. The examples use a blog post resource stored in Aura's shared table. Adjust the publication and team rules to match your application before exposing any record.

## Define a Resource

To define a resource, extend `Aura\\Base\\Resource` and return an array of fields from `getFields()`. Each field's `type` names its PHP class. Field definitions use arrays rather than a fluent builder.

For example, save this as `app/Aura/Resources/BlogPost.php`:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;
use Aura\Base\Resources\Tag;

class BlogPost extends Resource
{
    public static ?string $slug = 'blog-post';

    public static string $type = 'BlogPost';

    protected static bool $title = true;

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Excerpt',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'slug' => 'excerpt',
                'searchable' => true,
            ],
            [
                'name' => 'Featured image',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'featured_image',
                'max' => 1,
            ],
            [
                'name' => 'Categories',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'slug' => 'categories',
                'resource' => Tag::class,
            ],
            [
                'name' => 'Published at',
                'type' => 'Aura\\Base\\Fields\\Datetime',
                'slug' => 'published_at',
            ],
            [
                'name' => 'Meta title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'meta_title',
            ],
            [
                'name' => 'Meta description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'meta_description',
            ],
        ];
    }
}
```

This example uses the default shared `posts` table and meta storage. Categories use Aura's built-in tag resource, which you can replace with your own.

## Understand storage

The way a resource stores its fields determines how you query them. By default, resources share the `posts` table, whose migration defines these columns:

```text
id, title, content, type, status, slug, user_id, parent_id, order,
created_at, updated_at, deleted_at
```

When teams are enabled, the table also has a `team_id` column. Queries that support installations with and without teams must check this setting before referencing the column.

Fields whose slugs match the base model's fillable columns use those columns. Other input fields use the polymorphic `meta` table when meta storage is enabled. In this example, the excerpt, featured image, publication date, and SEO fields use meta storage. Categories use the `post_relations` pivot table because tags are a relationship field.

You can inspect a field's storage at runtime:

```php
$post = new BlogPost;

$post->isMetaField('status');          // false
$post->isTableField('status');         // true
$post->isMetaField('featured_image');  // true
$post->isTableField('featured_image'); // false
```

A resource can use its own table by setting `public static $customTable = true`. Fields listed in the model's fillable columns then use that table. Meta storage remains enabled by default for other input fields. Set `public static bool $usesMeta = false` only when the custom table migration defines a column for every input field slug.

## Protect public reads

Aura protects admin routes with the configured `aura-admin` middleware, which includes `web` and `auth`. Resource queries also apply model scopes. For resources stored in the posts table, these filter by resource type, team when enabled, and scoped permissions for authenticated Aura users.

These scopes do not decide which records are public. The team scope adds no team condition for unauthenticated requests, so filtering by published status alone can return records from every team. Your application must also filter by the site's team. Keep Aura's scopes and add these public filters to the query. Do not call `withoutGlobalScopes()` for a public listing.

Use application middleware to identify the site from the request host. It should reject unknown or unpublished sites and, when teams are enabled, set a server-controlled `public_team_id` request attribute. Do not accept a team ID from a query string or request body. Add a policy if individual records need further visibility checks.

The following query is for the shared `posts` storage profile. `status = 'publish'` is the default posts status. If your application uses another status value or a meta publication field, replace that condition with the value declared by your application.

```php
use App\Aura\Resources\BlogPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

function publicBlogPosts(Request $request): Builder
{
    $teamId = $request->attributes->get('public_team_id');

    abort_unless(! config('aura.teams') || $teamId !== null, 404);

    $model = new BlogPost;

    return $model->newQuery()
        ->where($model->qualifyColumn('status'), 'publish')
        ->whereNotNull($model->qualifyColumn('slug'))
        ->when(config('aura.teams'), function (Builder $query) use ($teamId): void {
            $query->where(
                $query->getModel()->qualifyColumn('team_id'),
                $teamId,
            );
        })
        ->orderByDesc($model->qualifyColumn('created_at'));
}
```

The query checks whether teams are enabled before filtering by team, and retains Aura's resource type scope. The site middleware and published-status filter determine which records are public. To preview drafts, use a separate authenticated route and policy without removing model scopes.

Apply the same team and publication rules to related resources. Eager loading does not authorize access to them. For example, restrict the category query to the public site's team:

```php
$posts = publicBlogPosts($request)
    ->with([
        'categories' => function (Builder $query) use ($request): void {
            $teamId = $request->attributes->get('public_team_id');

            if (config('aura.teams')) {
                $query->where($query->getModel()->qualifyColumn('team_id'), $teamId);
            }
        },
    ])
    ->paginate(12);
```

For related resources with custom tables, filter only columns defined by their migrations. Add any visibility rules beyond team membership to your query or policy as well.

## Query base and meta fields

Use ordinary `where` clauses for physical columns. Use Aura's meta scopes for values stored in `meta`.

```php
use App\Aura\Resources\BlogPost;

$published = publicBlogPosts($request)->get();

$matchingExcerpt = publicBlogPosts($request)
    ->whereMeta('excerpt', 'A short introduction')
    ->get();
```

`whereMeta()` supports a key and value, a key, operator, and value, or an array of key and value pairs. `whereMetaContains()` is for JSON values stored in a meta row. `whereNotInMeta()` and `whereInMeta()` cover set membership. Calling `whereMeta()` for `status` will not search the `posts.status` column.

To search fields declared with `'searchable' => true`, use the `searchIn` query-builder macro. It checks each field's storage and queries either its column or its meta value.

```php
$model = new BlogPost;
$searchable = $model->getSearchableFields()->pluck('slug')->all();

$posts = publicBlogPosts($request)
    ->when($request->filled('q'), function (Builder $query) use ($model, $searchable, $request): void {
        $query->searchIn($searchable, $request->input('q'), $model);
    })
    ->paginate(12);
```

Pass only field slugs that the resource declares as searchable. Keep the public publication and team conditions outside the search callback so a search term cannot bypass them.

## Read resolved field values

Read resolved field values from `$post->fields`, a collection keyed by field slug. Aura uses each field class to resolve its value, applies conditional visibility, and omits hidden fields. For meta fields, you can also read the same value as a property on the model.

```php
$post = publicBlogPosts($request)->firstOrFail();

$post->title;                    // a posts column
$post->excerpt;                  // a resolved meta value
$post->fields['meta_title'];     // the same value through the field map
$post->featured_image;           // an array of attachment IDs
$post->fields['categories'];     // an array of related IDs
$post->categories;               // an Eloquent collection of Tag models
```

Use the collection when you need several resolved values. Keep the raw meta relation out of public API responses, since its key/value layout is an internal storage detail.

## Work with relationship fields

Some relationship fields create Eloquent relationships that you can access by field slug.

Tags fields use a polymorphic many-to-many relationship through the `post_relations` pivot table. Has-many fields use that pivot too, unless you set the `column` option to use a normal Eloquent one-to-many relationship. You can eager load these relationships by field slug:

```php
$posts = publicBlogPosts($request)
    ->with('categories')
    ->get();

foreach ($posts->first()?->categories ?? [] as $category) {
    echo $category->title();
}
```

Belongs-to fields store a selected ID without creating an Eloquent relationship. Image and file fields also store values without exposing attachment relationships. For example, a belongs-to field named `author_id` does not support `with('author')`. Query the related resource using the stored ID and apply its public team and visibility rules.

## Resolve images and files

Image and file fields store JSON and return decoded values, usually arrays of attachment IDs. Query the configured attachment resource using the stored ID and apply your application's public media policy.

The following helper assumes that attachments in the selected team are public. Add any separate host publication condition before returning the attachment.

```php
use Aura\Base\Resources\Attachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

function publicAttachment(mixed $id, mixed $teamId): ?Attachment
{
    if ($id === null || (config('aura.teams') && $teamId === null)) {
        return null;
    }

    $model = new Attachment;

    return $model->newQuery()
        ->whereKey($id)
        ->when(config('aura.teams'), function (Builder $query) use ($teamId): void {
            $query->where(
                $query->getModel()->qualifyColumn('team_id'),
                $teamId,
            );
        })
        ->first();
}

$ids = Arr::wrap($post->featured_image);
$teamId = $request->attributes->get('public_team_id');
$image = publicAttachment($ids[0] ?? null, $teamId);
```

With the default `public` disk, `$image->path()` returns an `asset('storage/...')` URL. The application needs the normal Laravel `public/storage` link. For another configured disk, `path()` delegates to that disk's `url()` method. A private disk needs a host-owned download or image route that checks the same media policy and can issue a temporary URL.

`$image->thumbnail('md')` returns the named `aura.image` route. Aura registers that route under the authenticated admin middleware and authorizes `view` on the Attachment, so it is suitable for the admin UI. It is not a public image API. Use `path()` for files that the configured disk exposes publicly, or build a host route for public thumbnails.

The default thumbnail names are `xs` (200 pixels wide), `sm` (600), `md` (1200), `lg` (2000), and `thumbnail` (600 by 600). The names and dimensions come from `config('aura.media.dimensions')` and can be changed by the host application. `thumbnail()` falls back to the original path for non-image attachments or unknown sizes.

## Build a custom API

Create the endpoint in the host application. Aura does not provide `/api/posts` or a public equivalent. Return a Laravel API Resource so the response shape stays separate from Aura's model and meta storage.

Save the following as `app/Http/Resources/BlogPostResource.php`:

```php
<?php

namespace App\Http\Resources;

use Aura\Base\Resources\Attachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teamId = $request->attributes->get('public_team_id');
        $imageId = Arr::wrap($this->featured_image)[0] ?? null;
        $image = $this->publicAttachment($imageId, $teamId);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'published_at' => $this->published_at,
            'image' => $image ? [
                'url' => $image->path(),
                'alt' => $image->alt_text ?: $this->title,
            ] : null,
            'categories' => $this->whenLoaded(
                'categories',
                fn () => $this->categories
                    ->map(fn ($category) => [
                        'id' => $category->getKey(),
                        'title' => $category->title(),
                    ])
                    ->values(),
            ),
        ];
    }

    protected function publicAttachment(mixed $id, mixed $teamId): ?Attachment
    {
        if ($id === null || (config('aura.teams') && $teamId === null)) {
            return null;
        }

        $model = new Attachment;

        return $model->newQuery()
            ->whereKey($id)
            ->when(config('aura.teams'), function (Builder $query) use ($teamId): void {
                $query->where(
                    $query->getModel()->qualifyColumn('team_id'),
                    $teamId,
                );
            })
            ->first();
    }
}
```

The API resource looks up the attachment using the same trusted team as the post. If your media policy uses a publication flag or a separate media resource, enforce that in `publicAttachment()` too. Avoid returning an Aura model's `toArray()` result directly. Its output can include the resolved fields collection and raw meta storage, which should not define your public response format.

Save the controller as `app/Http/Controllers/Api/BlogPostController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Aura\Resources\BlogPost;
use App\Http\Resources\BlogPostResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BlogPostController
{
    public function index(Request $request)
    {
        return BlogPostResource::collection(
            $this->publicQuery($request)
                ->with($this->publicRelations($request))
                ->paginate(min($request->integer('per_page', 12), 50))
        );
    }

    public function show(Request $request, string $slug): BlogPostResource
    {
        $post = $this->publicQuery($request)
            ->with($this->publicRelations($request))
            ->where('posts.slug', $slug)
            ->firstOrFail();

        return new BlogPostResource($post);
    }

    private function publicQuery(Request $request): Builder
    {
        $teamId = $request->attributes->get('public_team_id');

        abort_unless(! config('aura.teams') || $teamId !== null, 404);

        $model = new BlogPost;

        return $model->newQuery()
            ->where($model->qualifyColumn('status'), 'publish')
            ->whereNotNull($model->qualifyColumn('slug'))
            ->when(config('aura.teams'), function (Builder $query) use ($teamId): void {
                $query->where(
                    $query->getModel()->qualifyColumn('team_id'),
                    $teamId,
                );
            })
            ->when($request->filled('q'), function (Builder $query) use ($model, $request): void {
                $searchable = $model->getSearchableFields()->pluck('slug')->all();

                $query->searchIn($searchable, $request->input('q'), $model);
            })
            ->orderByDesc($model->qualifyColumn('created_at'));
    }

    private function publicRelations(Request $request): array
    {
        return [
            'categories' => function (Builder $query) use ($request): void {
                $teamId = $request->attributes->get('public_team_id');

                if (config('aura.teams')) {
                    $query->where($query->getModel()->qualifyColumn('team_id'), $teamId);
                }
            },
        ];
    }
}
```

Register these routes in the host application's `routes/api.php`:

```php
use App\Http\Controllers\Api\BlogPostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['resolve.public.site', 'throttle:api'])->group(function () {
    Route::get('/blog-posts', [BlogPostController::class, 'index']);
    Route::get('/blog-posts/{slug}', [BlogPostController::class, 'show']);
});
```

Implement the `resolve.public.site` middleware in your application. It must identify the site from the request host, reject unpublished sites, authorize public access, and set `public_team_id` when teams are enabled. For an authenticated API, use your application's authentication and policy middleware and keep the publication condition in the query.

The controller filters and sorts by the status, slug, creation date, and optional team columns in the shared posts table. For a custom table, adapt the query to the columns defined by its migration. Continue to use `whereMeta()` for fields stored as meta.

## Render in Blade

Pass a post from the same public query to the view. Pass an attachment only after the controller applies the public media policy.

```blade
{{-- resources/views/blog/show.blade.php --}}
<title>{{ $post->meta_title ?: $post->title }}</title>

<article>
    <h1>{{ $post->title }}</h1>

    @if ($image)
        <img
            src="{{ $image->path() }}"
            alt="{{ $image->alt_text ?: $post->title }}"
        >
    @endif

    <p>{{ $post->excerpt }}</p>
    <div>{{ $post->content }}</div>

    @foreach ($post->categories as $category)
        <span>{{ $category->title() }}</span>
    @endforeach
</article>
```

Blade escapes these values. If the content contains HTML you want to display, sanitize it in your application before rendering it with `{!! $html !!}`. Reading a resource property does not use the field's admin display renderer. Even though Aura's WYSIWYG field sanitizes HTML in its display method, your public view must handle sanitization itself.

Aura has no SEO helper. Read the ordinary `meta_title` and `meta_description` fields to render the corresponding HTML tags in your view. Apply the same publication rules to the page title, body, related resources, and media.

## Related documentation

- [Meta fields](/docs/meta-fields) explains meta storage and its query scopes.
- [Fields](/docs/fields) lists field classes and their options.
- [Media library](/docs/media-manager) explains attachments, storage, and thumbnails.
- [Resources](/docs/resources) explains resource definitions and field configuration.
