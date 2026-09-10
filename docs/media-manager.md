# Media Library

Aura stores uploaded files as `Attachment` resources. The Media Library is both the media subsystem and the standalone admin page labelled "Media". These Livewire components provide its UI:

- `MediaUploader` is the drag-and-drop uploader and upload queue.
- `MediaManager` is the Media Picker, opened from an Image or File field to choose existing attachments.
- `AttachmentDetails` is the Details Panel, which previews one attachment and edits its metadata.

![Media Library Overview](/images/docs/media-manager/media-manager-overview.png)

Uploads and thumbnails use the disk and base path configured under `media` in `config/aura.php`. The defaults use Laravel's `public` disk under `media/`. See [Configuration and current limits](#configuration) before choosing another disk.

<a id="overview"></a>
<a id="architecture"></a>

## The attachment resource

Attachments are a built-in resource (`Aura\Base\Resources\Attachment`) labelled Media in the admin. The default resource uses the shared `posts` table with `type = 'Attachment'`. Its input field values are stored as rows in the `meta` table, not as a JSON blob. The base `title`, `slug`, `type`, `user_id`, and `team_id` values remain columns on `posts`. See [Meta fields](/docs/meta-fields) for the storage rules.

An upload writes `title` to the `posts` row. It writes `name`, `url`, `size`, and `mime_type` to meta, plus `width` and `height` when the uploaded file is an image. `alt_text` is a defined meta field that starts empty. `thumbnail_url` is also defined, but the upload and thumbnail code never fills it automatically.

The media index is available at the `aura.attachment.index` route (`/{aura-path}/attachment`, default `/admin/attachment`), rendered in a grid view at 25 per page.

Key methods and attributes on an attachment instance:

```php
use Aura\Base\Resources\Attachment;

$attachment = Attachment::find($id);

$attachment->name;                 // display filename (meta)
$attachment->title;                // posts.title, set to the original filename on upload
$attachment->alt_text;             // editable alt text (meta)
$attachment->url;                  // storage path, e.g. "media/photo.jpg" (meta)
$attachment->mime_type;            // e.g. "image/jpeg" (meta)
$attachment->size;                 // bytes (meta)
$attachment->width;                // pixels, images only (meta)
$attachment->height;               // pixels, images only (meta)

$attachment->readable_filesize;    // "2.5 MB", "150 KB"
$attachment->readable_mime_type;   // "JPEG", "PDF", "MP4", ...
$attachment->isImage();            // true when mime_type starts with "image/"

$attachment->path();               // public URL to the original file
$attachment->thumbnail('md');      // aura.image URL at the "md" dimension
$attachment->filePath();           // absolute path for the default public disk
```

The Details Panel labels its first input "Title", but it saves that value to the `name` meta field. It does not update `posts.title`. `filePath()` is a legacy helper that assumes `storage/app/public`; it does not resolve the configured disk. Use `path()` for a public URL and `Storage::disk(config('aura.media.disk'))->path(...)` on disks that expose local paths.

<a id="file-management"></a>

## The Media Library index page

The index page renders the `MediaUploader` in table mode plus the Details Panel:

- **Drag and drop** files anywhere on the page, or use the **Upload Files** button, to upload.
- **Quick filters** (media-type pills and an upload-month dropdown) narrow the grid. See [Quick filters](#quick-filters).
- **Clicking a card** opens the Details Panel for that attachment.
- **Bulk delete** is available through row selection (the `deleteSelected` bulk action) and the per-row `deleteAttachment` action.

<a id="configuration"></a>

## Configuration and current limits

Media options live under `media` in `config/aura.php`:

```php
'media' => [
    'disk'                   => 'public',
    'path'                   => 'media',
    'quality'                => 80,
    'restrict_to_dimensions' => true,
    'max_file_size'          => 10000, // KB per file
    'generate_thumbnails'    => true,
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

`max_file_size` is read as kilobytes by server validation and by the browser's size pre-check. The published default is 10,000 KB. PHP's `upload_max_filesize` and `post_max_size` can impose a lower limit. The browser queue accepts at most 20 files in one selection or drop, but the server does not impose a count rule on a batch.

`disk` and `path` control the logical storage key used for uploads and generated thumbnails. `Attachment::path()` returns `asset('storage/...')` for the `public` disk and calls the configured disk's `url()` method for other disks. Configure a public URL on a non-public disk. Run `php artisan storage:link` for the default local `public` disk.

The `filePath()` helper still points at `storage/app/public`, even when `media.disk` is changed. `thumbnail_path()` uses the configured disk, but only for a value you set in the `thumbnail_url` field. Generated thumbnails are returned by `thumbnail()` through the `aura.image` route and do not populate `thumbnail_url`.

The media routes use Aura's admin middleware. `Attachment\Index` authorizes `viewAny` before rendering the page. When teams are enabled, `TeamScope` limits default Attachment queries to the authenticated user's current Team. Teams-off mode removes that team predicate. `ScopedScope` can also limit a non-Super Admin with the `scope` permission to rows whose `user_id` matches the current user. Row and bulk mutations still authorize the requested resource ability.

<a id="media-fields"></a>

## Image and File fields

Both fields use `MediaUploader`. A typical resource definition is:

```php
public static function getFields(): array
{
    return [
        [
            'name' => 'Featured image',
            'type' => 'Aura\\Base\\Fields\\Image',
            'slug' => 'featured_image',
            'max_files' => 1,
        ],
        [
            'name' => 'Gallery',
            'type' => 'Aura\\Base\\Fields\\Image',
            'slug' => 'gallery',
            'max_files' => 10,
        ],
        [
            'name' => 'Downloads',
            'type' => 'Aura\\Base\\Fields\\File',
            'slug' => 'downloads',
        ],
    ];
}
```

The selected value is normally an array of attachment IDs. Image and File field setters JSON-encode array values for storage. The field views resolve the configured attachment resource when they render selected files.

```php
$ids = $post->gallery; // [123, 124, 125]
$images = Attachment::whereKey($ids)->get();
```

The Image field exposes these editor options:

| Option | Current behavior |
| --- | --- |
| `max_files` | The grid, table, and upload auto-selection limit selected IDs in the browser. `MediaManager::select()` does not repeat this count check on the server. |
| `use_media_manager` | Defined in the field editor, but not read by the uploader or field view. |
| `min_files` | Defined in the field editor, but not enforced. |
| `allowed_file_types` | Defined in the field editor, but not used by upload validation. The uploader uses its fixed MIME allow-list. |

The File field adds no media-specific options. Both fields use the same uploader and upload allow-list.

<a id="file-upload"></a>

## Uploading files

`MediaUploader` (`Aura\Base\Livewire\MediaUploader`) uses Livewire's `WithFileUploads` trait. Its Alpine queue uploads files in one batch one at a time, and reports progress and validation failures per file.

```blade
<livewire:aura::media-uploader
    :field="$field"
    :selected="$selected"
    :for="get_class($this->model)"
    :table="false"
    :button="true"
/>
```

The media index uses `table="true"` and renders the Upload Files button. Set `upload="true"` with `table="false"` for a standalone upload button. The field view uses `button="true"` and accepts drag-and-drop onto the component. The hidden file input is rendered for the table and standalone upload modes.

### The upload queue

When files are added by drop or file picker, the Alpine queue in `media-uploader.blade.php`:

1. Runs client-side pre-checks on each file and records failures without uploading them.
2. Uploads the remaining files sequentially through `@this.uploadMultiple('media', [file], ...)`, with one progress bar per file.
3. Marks successful rows as Uploaded and removes them after four seconds. Failed rows keep the server message until the user dismisses them or chooses Clear finished.

Client pre-checks are convenience checks. The server validates every file again. They use the values returned by `uploadPolicy()`:

- Extensions in the blocked list, including `svg`, are rejected.
- Files larger than the configured `max_file_size` are rejected.
- No more than 20 files may be queued in one batch.

**Server validation is authoritative.** `MediaUploader::updatedMedia()` re-validates every file and re-checks the blocked extensions before storing:

```php
$this->validate([
    'media.*' => [
        'required',
        'max:'.$this->maxFileSizeKilobytes(),
        // SVG is intentionally excluded because it can embed script content.
        'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp4,mov,avi,mp3,wav',
        'not_in:php,phtml,php3,php4,php5,phar,sh,exe,bat,cmd,com,scr,vbs,js,jar,svg',
    ],
]);
```

Validation failures return to the queue as failed rows. Each accepted file is stored with `$media->store(config('aura.media.path', 'media'), config('aura.media.disk', 'public'))`. The stored filename is generated by Laravel. Aura then creates an attachment through `config('aura.resources.attachment')`:

```php
app(config('aura.resources.attachment'))::create([
    'url'       => $url,                            // e.g. "media/{hashed-name}.jpg"
    'name'      => $media->getClientOriginalName(),
    'title'     => $media->getClientOriginalName(),
    'size'      => $media->getSize(),
    'mime_type' => $media->getMimeType(),
    // For images, width and height come from getimagesize().
    // 'width' => 1920, 'height' => 1080,
]);
```

### Commit-on-Select vs inline direct commit

How an upload updates a field's value depends on the mode:

- **Inline (`table = false`)**: a successful upload commits directly. The uploader dispatches `updateField` with the new IDs merged into the current selection. This is the mode used when you drop files onto an Image or File field.
- **Picker (`table = true`)**: an upload does not commit the parent field. It dispatches `media-uploaded`; the nested table refreshes, highlights the new cards, and auto-selects them. The parent field is written when the user confirms with Select.

Every successful batch also dispatches `media-uploaded` and populates the public `uploadResult` property (`successful`, `message`, `ids`), which the queue reads to advance to the next file. Inline mode additionally dispatches `updateField`.

### Component properties

| Property | Purpose |
|----------|---------|
| `field` | The field definition array. When set and `table` is false, a successful upload dispatches `updateField` with the selected IDs. |
| `for` | The **parent resource class** the field belongs to (e.g. `App\Aura\Resources\Post`). Passed to the Media Picker as its `model` so it can resolve the field via `fieldBySlug()`. |
| `selected` | Currently selected attachment IDs. |
| `model` | The table model property. `mount()` currently replaces it with the class in `namespace`, which defaults to `Aura\Base\Resources\Attachment`. |
| `button` | When true, renders a **Media Library** button that opens the Media Picker modal. Defaults to `false`. |
| `table` | Render the full Media Library table (grid, quick filters). Also switches uploads to commit-on-Select. |
| `upload` | Render a standalone **Upload Files** button without the table. Defaults to `false`. |
| `disabled` | Disable all upload interactions and render a disabled button. |

The hidden file input is only rendered when `upload` or `table` is true. In a field-edit view (`button` only), drag-and-drop still works on the component, and the Media Library button opens the Media Picker.

<a id="media-selection"></a>

## Selecting existing media

`MediaManager` (`Aura\Base\Livewire\MediaManager`) backs the **Media Picker**, the modal for choosing from already-uploaded files. The field's **Media Library** button opens it via the standard modal dispatch:

```blade
wire:click="$dispatch('openModal', {
    component: 'aura::media-manager',
    arguments: {
        model: {{ json_encode($for) }},   // parent resource class
        slug: '{{ $field['slug'] }}',       // field slug
        selected: {{ json_encode($selected) }},
    }
})"
```

`MediaManager::mount($slug, $selected, $modalAttributes)` resolves the field with `app($model)->fieldBySlug($slug)`, normalizes selected IDs to strings, and authorizes them through `MediaAuthorization`. The picker view nests `MediaUploader` in table mode. The nested Table component builds the Attachment query and paginates it using the resource's default per-page value, which is 25 for the built-in Attachment resource. Selection limits come from the resolved field's `max_files` and are enforced in the grid and table JavaScript.

Inside the picker, uploads **auto-select** the new files but do not write the field value. When you confirm, `MediaManager::select()` dispatches a single `updateField` event with the field slug and chosen IDs:

```php
$this->dispatch('updateField', data: [
    'slug'  => $this->fieldSlug,
    'value' => $selected, // array of string IDs
]);
```

The PHP component does not dispatch a "selection complete" event or close the modal. The Select button closes the dialog in the browser after `select()` resolves.

The Media Picker component is swappable. Plugins can override `aura.components.media-manager` in `config/aura.php` to point at their own Livewire component (the config key keeps the `media-manager` name):

```php
// config/aura.php
'components' => [
    'media-manager' => \App\Livewire\MyMediaPicker::class,
],
```

<a id="details-panel"></a>

## The details panel

`AttachmentDetails` (`Aura\Base\Livewire\AttachmentDetails`) renders the **Details Panel** for a single attachment. It appears in two surfaces, controlled by the locked `surface` property:

- `surface="index"`: a slide-in drawer on the Media Library page, including destructive actions.
- `surface="picker"`: a sidebar inside the Media Picker without the delete action.

It opens on the `open-attachment-details` event, which carries the clicked `id` and the ordered `ids` of the currently listed attachments (for prev/next):

```js
Livewire.dispatch('open-attachment-details', { id: 123, ids: rows.map(Number) })
```

The panel provides:

- **Preview**: image, `<video>`, `<audio>`, or a file-type icon depending on the MIME type.
- **Title**: `wire:model.live.debounce.600ms`; `updatedTitle()` validates `required|string|max:255` and saves to the `name` meta field. A Saved badge flashes on success.
- **Alt text**: `updatedAltText()` validates `nullable|string|max:500` and saves to `alt_text`.
- **Facts**: uploaded date, type (`readable_mime_type`), size (`readable_filesize`), and dimensions when both `width` and `height` exist.
- **File URL**: a read-only field with a Copy button.
- **Download**: a link to the original file URL.
- **Delete**: available only on the index surface. It authorizes `delete`, removes the Attachment record, refreshes the table, and opens the next or previous row. It closes when no row remains.

Navigation between attachments:

- Prev and Next buttons, plus `ArrowLeft` and `ArrowRight` while the panel is open and no input or textarea is focused.
- Escape closes the panel on the index surface.

Every read, update, and delete goes through the resource policy (`Gate::authorize('view' | 'update' | 'delete', $attachment)`), so the panel respects the same authorization as the rest of Aura.

<a id="quick-filters"></a>

## Quick filters and metadata

The Media Library grid ships two quick filters built on the generic table mechanism:

- Media-type pills use `Attachment::MEDIA_TYPES`: `All`, `Images`, `Video`, `Audio`, and `Documents`. Documents means MIME types that are not images, video, or audio.
- The upload-month dropdown uses distinct `YYYY-MM` values from `Attachment::uploadMonths()`, newest first.

Both are wired to the table's generic quick-filter API, so this is the pattern to reuse for any resource:

- `Table` exposes `array $quickFilters` and `setQuickFilter(string $key, string|int|float|bool|array|null $value)`. Passing `null` or `''` clears a key and resets pagination.
- `Attachment::indexQuery(Builder $query, ?Table $table = null)` reads those keys. It filters `mime_type` by prefix and filters `created_at` to the selected month. It uses a meta subquery when `mime_type` is a meta field and a column comparison for custom-table resources without meta.

To add quick filters to your own resource, override `indexQuery()` to interpret whatever keys your UI sets via `setQuickFilter()`. See [Table](/docs/table) for the table component.

## Attachment metadata and querying

For the default Attachment resource, `mime_type`, `size`, `url`, `name`, `alt_text`, `width`, and `height` are meta fields. `where('mime_type', ...)` therefore targets no `posts` column. Use Aura's meta scopes:

```php
use Aura\Base\Resources\Attachment;

// All PDFs
Attachment::whereMeta('mime_type', 'application/pdf')->get();

// Match multiple values
Attachment::whereInMeta('mime_type', ['image/png', 'image/jpeg'])->get();

// JSON containment. This matches an exact value or membership in a
// stored JSON array. It is not a substring or LIKE match.
Attachment::whereMetaContains('mime_type', 'application/pdf')->get();
```

`whereMeta` accepts a SQL operator such as `LIKE`, which is how `indexQuery()` matches prefixes such as `image/%`. Use `isImage()` after loading a record. The built-in Tags field is commented out and is not active for attachments.

When a resource policy implements `Aura\Base\Contracts\ScopesMediaVisibility`, Aura uses that scope while validating selected attachment IDs. The default TeamScope still protects ordinary queries. The current Table query does not call this custom visibility hook while listing rows, so custom policies should not assume that the Media Library grid applies their extra row filter. See the evidence report for the source locations and a focused reproduction.

<a id="programmatic-usage"></a>
<a id="performance-optimization"></a>
<a id="image-processing"></a>

## Thumbnails

Aura generates thumbnails on demand through the `aura.image` route. Saving an image also dispatches `GenerateImageThumbnail`, which can pre-generate the configured sizes when a queue worker processes it.

### On-demand URLs

`Attachment::thumbnail($size)` looks up `$size` in `config('aura.media.dimensions')` and returns an `aura.image` route URL. It does not read a stored thumbnail path. `thumbnail_url` is a defined field, but the upload and thumbnail code never fills it. `thumbnail_path()` builds a URL from that field, so it is only useful when your application sets the field itself.

```php
$attachment->thumbnail('xs');        // width 200
$attachment->thumbnail('sm');        // width 600 (default)
$attachment->thumbnail('md');        // width 1200
$attachment->thumbnail('lg');        // width 2000
$attachment->thumbnail('thumbnail'); // 600x600, cropped

// Resolves to a route such as:
// /admin/img/media/photo.jpg?width=1200
```

For non-image attachments `thumbnail()` returns the original file URL.

### The image route

`GET /{aura-path}/img/{path}` (`aura.image`, handled by `ImageController`) requires a matching Attachment record and `view` authorization. It reads `width` (default 200) and optional `height`, generates the thumbnail through `ThumbnailGenerator`, and streams it as `image/jpeg`.

`ThumbnailGenerator::generate(string $path, int $width, ?int $height = null): string` operates on the configured `config('aura.media.disk', 'public')` disk:

The generator uses Intervention Image 3 with its GD driver. Enable PHP GD in the host application. Aura does not require the optional Laravel image facade package for this path.

- Thumbnails are written to `thumbnails/{original-folder}/`, named `{width}_auto_{filename}` for width-only requests or `{width}_{height}_{filename}` for fixed dimensions.
- A width-only request does not upscale. If the requested width exceeds the source width, the generator returns the original path.
- Output is always encoded as JPEG at `config('aura.media.quality')` percent.
- With `restrict_to_dimensions` enabled, only width and height pairs listed in `config('aura.media.dimensions')` are allowed. An unconfigured request throws `NotFoundHttpException`. For example, `width=800&height=600` is rejected by the published default config.

### Background generation

When an image attachment is saved, `Attachment::booted()` dispatches `GenerateImageThumbnail`:

```php
static::saved(function (Attachment $attachment) {
    if ($attachment->isImage()) {
        GenerateImageThumbnail::dispatch($attachment);
    }
});
```

The job skips the `testing` environment, reads the media configuration, returns without work when `generate_thumbnails` is false, and calls `ThumbnailGenerator::generate()` for each configured dimension. It writes thumbnail files and does not update `thumbnail_url`. Run a queue worker when you want the pre-generation job to run:

```bash
php artisan queue:work
```

## Deleting attachments

Deletion removes the Attachment record and does not remove the underlying file from storage:

- The Details Panel Delete button is available on the index surface, authorizes `delete`, and removes the record.
- The row action `Attachment::deleteAttachment()` authorizes `delete`, removes the record, and redirects to the index.
- The bulk action `Attachment::deleteSelected($ids)` resolves the selected rows and authorizes each record before deleting it.

The built-in row and bulk methods perform the authorization checks before deleting. Use those methods from the table actions instead of calling an unscoped `whereIn(...)->delete()` query.

If the application needs orphaned files removed, delete them from the configured disk in its own cleanup flow. The package does not provide that cleanup.

<a id="troubleshooting"></a>

## Troubleshooting

- If an original file on the default local disk returns a broken URL, run `php artisan storage:link` and check that the attachment `url` starts with the configured `media.path`.
- If `aura.image` returns 404, check that the Attachment exists and the requested width and height pair appears in `aura.media.dimensions` while `restrict_to_dimensions` is enabled. The route also checks the viewer's `view` ability.
- If the uploader rejects a file, check `aura.media.max_file_size`, the PHP upload limits, and the fixed MIME allow-list. SVG is blocked by design.
- If queued thumbnails are missing, run a queue worker and check `aura.media.generate_thumbnails`. The image route can generate an allowed thumbnail on demand.
- For a non-public disk, define the disk's URL in `config/filesystems.php`. `path()` uses that URL for original files, while `filePath()` remains limited to the default local public path.

<a id="advanced-customization"></a>

## Overriding the attachment resource

To add custom fields such as copyright or category, extend the base resource and point `aura.resources.attachment` at your class. The uploader uses the configured class when it creates a record. `AttachmentDetails`, the Image field, and `ImageController` also resolve that configured class. The table model in `MediaUploader` currently defaults to the base `Aura\Base\Resources\Attachment` during `mount()`, so a custom subclass is not a complete drop-in for the index or picker table. See the evidence report for this source defect.

```php
namespace App\Aura\Resources;

use Aura\Base\Resources\Attachment as BaseAttachment;

class Attachment extends BaseAttachment
{
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Category',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'category',
                'options' => [
                    'products' => 'Products',
                    'blog'     => 'Blog',
                ],
            ],
        ]);
    }
}
```

```php
// config/aura.php
'resources' => [
    'attachment' => \App\Aura\Resources\Attachment::class,
],
```

Define a field before saving a value such as `category`. A custom setter can consume a non-field payload. Once the field exists, query it with the meta scopes:

```php
App\Aura\Resources\Attachment::whereMeta('category', 'blog')->get();
```

## Related

- [Fields](/docs/fields): Image and File field reference
- [Meta fields](/docs/meta-fields): how attachment metadata is stored and queried
- [Livewire components](/docs/livewire-components): component and modal patterns
- [Configuration](/docs/configuration): the full `config/aura.php` reference
