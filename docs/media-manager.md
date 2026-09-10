# Media Library

The Media Library lets you upload files, choose existing files for a resource, and edit file metadata. You can open it from the **Media** page or from an image or file field.

Aura stores each uploaded file as an attachment resource. Three Livewire components provide the interface:

- `MediaUploader` handles drag-and-drop uploads and the upload queue.
- `MediaManager` opens a picker for choosing existing attachments.
- `AttachmentDetails` previews an attachment and lets you edit its metadata.

![Media Library Overview](/images/docs/media-manager/media-manager-overview.png)

Uploads and thumbnails use the disk and base path configured under `media` in `config/aura.php`. The defaults use Laravel's `public` disk under `media/`. See [Configuration and current limits](#configuration) before choosing another disk.

<a id="overview"></a>
<a id="architecture"></a>

## The attachment resource

The built-in attachment resource, `Aura\Base\Resources\Attachment`, appears as **Media** in the admin. It uses the shared `posts` table, with its type set to `Attachment`. Field values are stored as individual rows in the `meta` table. The title, slug, type, user ID, and team ID remain columns on the record itself. See [Meta fields](/docs/meta-fields) for the storage rules.

Uploads save the original filename as the record's title. The display filename, storage path, file size, and MIME type are saved as metadata. Images also store their width and height. Alt text starts empty. The resource defines a `thumbnail_url` field, but uploads and thumbnail generation do not fill it automatically.

The media index displays 25 attachments per page in a grid. Its named route is `aura.attachment.index`, and its URL is `/{aura-path}/attachment`, or `/admin/attachment` with the default configuration.

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

Editing **Title** in the details panel changes the display filename, stored in the `name` meta field. It does not change the record's `posts.title` column.

Use `path()` to get a public URL. For a local filesystem path, use `Storage::disk(config('aura.media.disk'))->path(...)` on a disk that supports it. The legacy `filePath()` helper always assumes `storage/app/public`, regardless of the configured disk.

<a id="file-management"></a>

## The Media Library index page

The index page combines the uploader, attachment grid, and details panel.

- Drag files onto the page or click **Upload Files** to upload them.
- Filter the grid by media type or upload month. See [Quick filters](#quick-filters).
- Click an attachment card to open its details.
- Delete selected attachments with the bulk action, or delete one attachment with its row action. The corresponding resource methods are `deleteSelected()` and `deleteAttachment()`.

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

Media routes use Aura's admin middleware, and the index checks the resource's `viewAny` permission before rendering. When teams are enabled, attachment queries are limited to the signed-in user's current team. Disabling teams removes that restriction.

Users with the `scope` permission who are not super admins may also be limited to attachments they own. Row actions and bulk actions still check the resource permission for the requested change.

<a id="media-fields"></a>

## Image and File fields

Image and file fields let users upload files or choose attachments from the library. Both use the same uploader. Define them on a resource as follows:

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

A field's value is normally an array of attachment IDs. Both field types encode arrays as JSON for storage and use the configured attachment resource to display selected files.

```php
$ids = $post->gallery; // [123, 124, 125]
$images = Attachment::whereKey($ids)->get();
```

The image field exposes these editor options:

| Option | Current behavior |
| --- | --- |
| `max_files` | The grid, table, and upload auto-selection limit selected IDs in the browser. `MediaManager::select()` does not repeat this count check on the server. |
| `use_media_manager` | Defined in the field editor, but not read by the uploader or field view. |
| `min_files` | Defined in the field editor, but not enforced. |
| `allowed_file_types` | Defined in the field editor, but not used by upload validation. The uploader uses its fixed MIME allow-list. |

The file field adds no media-specific options. Both fields accept the same fixed list of upload types.

<a id="file-upload"></a>

## Uploading files

The uploader sends files one at a time and shows progress and validation errors for each file. The `Aura\Base\Livewire\MediaUploader` component uses Livewire's `WithFileUploads` trait with an Alpine upload queue.

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

When you drop files onto the uploader or choose them from a file picker, the queue checks each file in the browser first. Files that fail these checks stay in the queue with an error and are not uploaded.

The remaining files upload one at a time, each with its own progress bar. Successful rows show **Uploaded** and disappear after four seconds. Failed rows keep the server's error message until you dismiss them or choose **Clear finished**.

The queue is defined in `media-uploader.blade.php` and sends each file through Livewire's `uploadMultiple()` method.

Client pre-checks are convenience checks. The server validates every file again. They use the values returned by `uploadPolicy()`:

- Extensions in the blocked list, including `svg`, are rejected.
- Files larger than the configured `max_file_size` are rejected.
- No more than 20 files may be queued in one batch.

The server makes the final validation decision. Before storing a file, `MediaUploader::updatedMedia()` validates it again and checks its extension against the blocked list:

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

Validation failures appear as failed rows in the queue. Accepted files are stored on the configured media disk and path, with filenames generated by Laravel. Aura then creates a record using the configured attachment resource:

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

- In inline mode, dropping files onto an image or file field updates its selection as soon as the upload succeeds. With `table = false`, the uploader merges the new attachment IDs into the current selection and dispatches `updateField`.
- In picker mode, uploaded files appear in the refreshed grid and are selected automatically. With `table = true`, the parent field is updated only after the user clicks **Select**.

Every successful batch dispatches `media-uploaded`. It also fills the public `uploadResult` property with the `successful`, `message`, and `ids` values that the queue needs before moving to the next file.

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

Click **Media Library** on a field to choose files that have already been uploaded. This opens the picker, implemented by `Aura\Base\Livewire\MediaManager`, through the standard modal event:

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

When the picker opens, it looks up the field on its parent resource, converts the selected IDs to strings, and checks access to those attachments through `MediaAuthorization`.

The picker displays the uploader in table mode. Its attachment list uses the resource's default page size, which is 25 for the built-in resource. The browser enforces the field's `max_files` selection limit in both grid and table views.

Uploads select the new files automatically, but the field value changes only when you confirm. At that point, `MediaManager::select()` dispatches one `updateField` event with the field slug and chosen IDs:

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

The details panel previews one attachment and lets you edit its metadata. The `Aura\Base\Livewire\AttachmentDetails` component has two layouts, controlled by its locked `surface` property:

- `surface="index"` shows a slide-in drawer on the Media Library page, with a delete action.
- `surface="picker"` shows a sidebar in the picker, without a delete action.

It opens on the `open-attachment-details` event, which carries the clicked `id` and the ordered `ids` of the currently listed attachments (for prev/next):

```js
Livewire.dispatch('open-attachment-details', { id: 123, ids: rows.map(Number) })
```

The panel shows an image, video, audio player, or file-type icon according to the attachment's MIME type. It also shows the upload date, readable file type and size, and image dimensions when both width and height are available.

Edits to **Title** save automatically after a 600 ms debounce, and a **Saved** badge confirms success. The value is required, must be a string, and may contain up to 255 characters. It updates the display filename in the `name` meta field. Alt text is optional and accepts up to 500 characters, saved to `alt_text`.

You can copy the read-only file URL or download the original file. On the Media Library page, you can also delete the attachment. After checking permission and removing the record, the panel refreshes the table and opens the next or previous attachment. It closes if no attachments remain.

Navigation between attachments:

- Prev and Next buttons, plus `ArrowLeft` and `ArrowRight` while the panel is open and no input or textarea is focused.
- Escape closes the panel on the index surface.

The panel checks the resource policy for every read, edit, and deletion, using the corresponding `view`, `update`, or `delete` ability.

<a id="quick-filters"></a>

## Quick filters and metadata

The grid has two quick filters:

- Filter by All, Images, Video, Audio, or Documents. Documents includes any MIME type that is not an image, video, or audio. These choices are defined in `Attachment::MEDIA_TYPES`.
- Filter by upload month, with the newest month first. The available months come from `Attachment::uploadMonths()` as distinct `YYYY-MM` values.

Both are wired to the table's generic quick-filter API, so this is the pattern to reuse for any resource:

- `Table` exposes `array $quickFilters` and `setQuickFilter(string $key, string|int|float|bool|array|null $value)`. Passing `null` or `''` clears a key and resets pagination.
- `Attachment::indexQuery(Builder $query, ?Table $table = null)` reads those keys. It filters `mime_type` by prefix and filters `created_at` to the selected month. It uses a meta subquery when `mime_type` is a meta field and a column comparison for custom-table resources without meta.

To add quick filters to your own resource, override `indexQuery()` to interpret whatever keys your UI sets via `setQuickFilter()`. See [Table](/docs/table) for the table component.

## Attachment metadata and querying

The default attachment resource stores file metadata in the meta table. This includes the MIME type, size, storage path, display filename, alt text, and image dimensions. These values are not columns on the `posts` table, so a query such as `where('mime_type', ...)` will not work. Use Aura's meta scopes:

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

A policy can implement `Aura\Base\Contracts\ScopesMediaVisibility` to restrict which attachments may be selected. Aura applies this scope when it validates selected IDs, but the table does not apply it when listing attachments. The grid therefore does not inherit these custom visibility restrictions. Ordinary queries still use the default team scope.

<a id="programmatic-usage"></a>
<a id="performance-optimization"></a>
<a id="image-processing"></a>

## Thumbnails

Aura generates thumbnails on demand through the `aura.image` route. Saving an image also dispatches `GenerateImageThumbnail`, which can pre-generate the configured sizes when a queue worker processes it.

### On-demand URLs

Call `thumbnail($size)` with a name from `aura.media.dimensions` to get a URL for the image route. Aura generates the thumbnail when that URL is requested.

This method does not use the attachment's `thumbnail_url` field. The separate `thumbnail_path()` helper builds a URL from that field and is useful only if your application fills it itself.

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

The image route, `GET /{aura-path}/img/{path}`, requires a matching attachment and permission to view it. Its route name is `aura.image`, and `ImageController` handles the request. It accepts a width, which defaults to 200, and an optional height, then generates and streams a JPEG thumbnail.

`ThumbnailGenerator::generate()` reads and writes files on the configured media disk. It uses Intervention Image 3 with the GD driver, so enable PHP GD in your application. The optional Laravel image facade package is not required.

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

The job generates a thumbnail for each configured dimension. It skips this work in the testing environment or when `generate_thumbnails` is false. It writes the files without updating the attachment's `thumbnail_url` field.

Run a queue worker to process thumbnail jobs:

```bash
php artisan queue:work
```

## Deleting attachments

Deleting an attachment removes its database record. The original file remains in storage.

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

To add fields such as copyright or category, extend the base attachment resource and set `aura.resources.attachment` to your class. The uploader, details panel, image field, and image controller use this configured class.

The index and picker tables currently default to the built-in attachment class when the uploader mounts. Setting a custom resource does not replace that table model, so a subclass alone does not fully customize these lists.

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
