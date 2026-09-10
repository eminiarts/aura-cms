# Frontend integration

Aura provides an authenticated admin panel and Eloquent Resource classes. It does not render a public site, generate public Resource routes, or ship a public REST API. Your Laravel application owns the public routes, authentication, publication rules, team selection, rate limiting, and media policy.

This guide explains how to read Aura data from a Blade view or a host application's API. The examples use a shared-table `BlogPost` Resource. Adjust the publication and team rules to match your application before exposing any record.

## Define a Resource

Resources extend `Aura\\Base\\Resource` and define fields as arrays returned by `getFields()`. The `type` value is a field class name. It is not a fluent builder.

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

This example assumes the default shared `posts` table and meta storage. The built-in `Tag` Resource is used for the `categories` field. A host application can replace it with its own Resource.

## Understand storage

The storage profile determines which query method to use. For a default shared-table Resource, these are the fixed columns in the `posts` migration:

```text
id, title, content, type, status, slug, user_id, parent_id, order,
created_at, updated_at, deleted_at
```

`team_id` is also a `posts` column when teams are enabled. It is absent from a teams-off installation. Do not reference it unconditionally in a query that must support both modes.

Fields whose slugs match the base fillable columns use those columns. Other ordinary input fields use the polymorphic `meta` table when the Resource uses meta storage. In the example, `excerpt`, `featured_image`, `published_at`, `meta_title`, and `meta_description` are meta fields. `categories` is stored in the `post_relations` pivot because `Tags` is a relationship field.

You can inspect a field's storage at runtime:

```php
$post = new BlogPost;

$post->isMetaField('status');          // false
$post->isTableField('status');         // true
$post->isMetaField('featured_image');  // true
$post->isTableField('featured_image'); // false
```

Custom-table Resources use a different profile. With `public static $customTable = true`, fields that are in the model's fillable columns use the custom table. Meta storage remains enabled by default, so other input fields can still use `meta`. Set `public static bool $usesMeta = false` only when every input field slug has a real column in the custom table migration.

## Protect public reads

Aura's admin routes use the configured `aura-admin` middleware, which includes `web` and `auth`. Resource models also add their normal model scopes. A posts-backed Resource receives the Resource type scope, the team scope when teams are enabled, and the scoped-permission scope for authenticated Aura users.

Those scopes do not define public publication. In particular, the team scope does not add a team condition to an unauthenticated request. A public request such as `BlogPost::query()->where('status', 'publish')` can therefore see rows from every team unless the host application adds the site team's ID. Keep Aura's scopes and add the host application's explicit public filters. Do not call `withoutGlobalScopes()` for a public listing.

The host application should have middleware that maps the request host to a trusted site context. That middleware should reject an unknown or unpublished site and expose a server-controlled `public_team_id` request attribute when teams are enabled. Do not accept a team ID from a query string or request body. A separate host policy can add record-level visibility rules.

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

The `config('aura.teams')` condition keeps `team_id` out of teams-off SQL. The query still gets Aura's Resource type scope. The host middleware and the `status` condition together define which records are public. Use a separate authenticated preview route and policy for drafts. Do not remove model scopes to implement preview.

For a related Resource, apply the same public team and publication policy. Eager loading is a performance feature, not an authorization check. For the built-in `Tag` Resource in this example, a public query can constrain the eager load as follows:

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

If a related Resource uses a custom table, filter only columns that its migration declares. If its public visibility is more restrictive than team membership, put that rule in the host query or policy as well.

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

For search across fields declared with `'searchable' => true`, use the `searchIn` query-builder macro. It checks `isMetaField()` and sends each slug to a physical-column or meta subquery.

```php
$model = new BlogPost;
$searchable = $model->getSearchableFields()->pluck('slug')->all();

$posts = publicBlogPosts($request)
    ->when($request->filled('q'), function (Builder $query) use ($model, $searchable, $request): void {
        $query->searchIn($searchable, $request->input('q'), $model);
    })
    ->paginate(12);
```

Pass only field slugs that the Resource declares as searchable. Keep the public publication and team conditions outside the search callback so a search term cannot bypass them.

## Read resolved field values

`$post->fields` is a collection keyed by field slug. Aura resolves each field through its field class, applies conditional visibility, and omits hidden fields. Direct property access uses the same resolved value for a meta-backed field.

```php
$post = publicBlogPosts($request)->firstOrFail();

$post->title;                    // a posts column
$post->excerpt;                  // a resolved meta value
$post->fields['meta_title'];     // the same value through the field map
$post->featured_image;           // an array of attachment IDs
$post->fields['categories'];     // an array of related IDs
$post->categories;               // an Eloquent collection of Tag models
```

Use the `fields` collection when you need several resolved values. Do not expose the raw `meta` relation as an API contract. Its key/value layout is an internal storage detail.

## Work with relationship fields

The field type controls whether a field slug is a dynamic Eloquent relationship.

`Tags` is a real `morphToMany` relationship through `post_relations`. `HasMany` is a relationship field too. A `HasMany` field with a `column` uses a normal Eloquent `hasMany`; without a `column`, it uses the `post_relations` pivot. These relations can be eager-loaded by their field slug:

```php
$posts = publicBlogPosts($request)
    ->with('categories')
    ->get();

foreach ($posts->first()?->categories ?? [] as $category) {
    echo $category->title();
}
```

`BelongsTo` stores a selected ID and does not create a dynamic Eloquent `belongsTo` relation. `Image` and `File` also store values, rather than exposing attachment relations. Do not write `with('author')` for a `BelongsTo` field named `author_id`. Resolve the ID through a query for the related Resource and apply that Resource's public team and visibility policy.

## Resolve images and files

`Image` and `File` fields store JSON and return decoded values, normally attachment ID arrays. Resolve an ID through the configured Attachment Resource after applying the host's public media policy.

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

The resource resolves the attachment through the same trusted team context as the post. If your media policy has a publication flag or a separate media Resource, enforce it in `publicAttachment()` too. Do not return `toArray()` on an Aura Resource and assume that it is a stable public schema. The default model can append the resolved `fields` collection, and the raw meta layout is not an API contract.

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

`resolve.public.site` is host-application middleware. It must map the request host to the intended site, reject an unpublished site, authorize the public site, and set `public_team_id` when teams are enabled. If the API is for authenticated users instead, use the host's authentication and policy middleware and keep the publication condition in the query.

The controller uses `posts.status`, `posts.slug`, and `posts.created_at`, plus `posts.team_id` when teams are enabled. These are valid columns for this shared-table example. A custom-table Resource needs a query written against the columns declared by its migration. Meta fields still require `whereMeta()` when meta storage is enabled.

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

Blade escapes these values. If `content` contains intended HTML, sanitize it in the host application and render only the sanitized result with `{!! $html !!}`. Reading a Resource property does not call a field's admin display renderer. Aura's `Wysiwyg` field has its own sanitizing display method, but a public Blade view should make its HTML decision explicit.

Aura has no SEO helper. `meta_title` and `meta_description` are ordinary fields, so read them and render the tags in the host view. A public page should use the same publication query for its `<title>`, body, related Resources, and media.

## Related documentation

- [Meta fields](/docs/meta-fields) explains meta storage and its query scopes.
- [Fields](/docs/fields) lists field classes and their options.
- [Media library](/docs/media-manager) explains attachments, storage, and thumbnails.
- [Resources](/docs/resources) explains Resource definitions and field configuration.
