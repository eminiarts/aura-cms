# Media Manager

The Media Manager is a comprehensive media asset management system in Aura CMS that provides a centralized interface for uploading, organizing, and managing all types of media files. Built with Laravel developers in mind, it offers seamless integration with resources and fields while providing powerful image processing capabilities.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Media Fields](#media-fields)
- [File Upload](#file-upload)
- [File Management](#file-management)
- [Image Processing](#image-processing)
- [Media Selection](#media-selection)
- [Programmatic Usage](#programmatic-usage)
- [Performance Optimization](#performance-optimization)
- [Advanced Customization](#advanced-customization)
- [Troubleshooting](#troubleshooting)

## Overview

The Media Manager provides:
- **Unified Interface**: Single location for all media assets
- **Multiple Upload Methods**: Drag-and-drop, file selection, programmatic
- **Automatic Processing**: Thumbnail generation, image optimization
- **Flexible Storage**: Local disk, S3, custom drivers
- **Rich Metadata**: File information, custom attributes, tags
- **Seamless Integration**: Works with Image and File fields


## Architecture

### Component Structure

The Media Manager consists of several key components:

```php
// Core Components
Aura\Base\Resources\Attachment           // Media resource model
Aura\Base\Livewire\MediaManager          // Media selection modal
Aura\Base\Livewire\MediaUploader         // Upload functionality  
Aura\Base\Livewire\Attachment\Index      // Attachment list view
Aura\Base\Services\ThumbnailGenerator    // Image processing service
Aura\Base\Jobs\GenerateImageThumbnail    // Background thumbnail job
```

### Storage Architecture

```
storage/app/public/
├── media/                       # Original uploaded files
│   ├── image1.jpg
│   ├── document.pdf
│   └── video.mp4
└── thumbnails/                  # Generated thumbnails
    └── media/
        ├── 200_auto_image1.jpg  # Width-only resize (aspect ratio preserved)
        ├── 600_auto_image1.jpg  # Medium width (aspect ratio preserved)
        └── 600_600_image1.jpg   # Fixed dimensions (cropped to fit)
```

The thumbnail filename format is `{width}_auto_{filename}` for width-only resizing or `{width}_{height}_{filename}` for fixed dimensions.

### Database Schema

The Attachment resource uses the `posts` table with specific fields:

```php
// Stored in posts table
[
    'type' => 'Attachment',
    'title' => 'image.jpg',
    'slug' => 'image-jpg-65abc123',
    'fields' => [
        'name' => 'image.jpg',
        'url' => 'media/image.jpg',
        'size' => 245678,
        'mime_type' => 'image/jpeg',
        'thumbnail_url' => 'thumbnails/media/600_auto_image.jpg',
    ]
]
```

## Configuration

### Basic Configuration

Configure media settings in `config/aura.php`:

```php
'media' => [
    // Storage configuration
    'disk' => 'public',        // Laravel filesystem disk
    'path' => 'media',         // Upload directory within disk
    
    // File upload limits
    'max_file_size' => 10000,  // KB (10MB)

    // Server-side owner/selection/details protocol
    'security' => [
        'cache_store' => 'aura-media-security', // Required dedicated database store
        'owner_token_ttl' => 900,
        'selection_ttl' => 15,
        'selection_retention' => 60,
    ],
    
    // Image processing
    'generate_thumbnails' => true,
    'quality' => 80,           // JPEG quality (1-100)
    'restrict_to_dimensions' => true, // Only allow configured thumbnail sizes
    
    // Thumbnail dimensions
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

> **Note**: The `max_files` selection limit is configured per Image or File
> field. Upload requests also have a non-configurable hard limit of 20 files.

### Storage Configuration

Configure storage disk in `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    
    // S3 configuration for production
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
    ],
],
```

### Queue Configuration

Thumbnail generation runs in background jobs:

```php
// .env file
QUEUE_CONNECTION=database  // or redis, sqs, etc.

// Run queue worker
php artisan queue:work
```

## Media Fields

### Image Field

The Image field provides specialized image handling:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Featured Image',
            'type' => 'Aura\\Base\\Fields\\Image',
            'slug' => 'featured_image',
            'validation' => 'required',
            'use_media_manager' => true,  // Enable media manager
            'min_files' => 1,
            'max_files' => 1,
            'allowed_file_types' => 'jpg,jpeg,png,webp',
            'instructions' => 'Upload a featured image (min 1200x600)',
        ],
        
        // Multiple images
        [
            'name' => 'Gallery',
            'type' => 'Aura\\Base\\Fields\\Image',
            'slug' => 'gallery',
            'use_media_manager' => true,
            'max_files' => 10,
            'instructions' => 'Upload up to 10 gallery images',
        ],
    ];
}
```

### File Field

The File field handles all file types:

```php
[
    'name' => 'Downloads',
    'type' => 'Aura\\Base\\Fields\\File',
    'slug' => 'downloads',
    'validation' => 'required',
    'use_media_manager' => true,
    'allowed_file_types' => 'pdf,doc,docx,zip',
    'max_files' => 5,
    'instructions' => 'Upload downloadable files',
]
```

### Field Value Structure

Media fields store attachment IDs as JSON:

```php
// Single file
$post->featured_image = 123;  // Attachment ID

// Multiple files
$post->gallery = [123, 124, 125];  // Array of IDs

// Access attachments
$image = Attachment::find($post->featured_image);
$galleryImages = Attachment::whereIn('id', $post->gallery)->get();
```

## File Upload

### Upload Component

The `MediaUploader` Livewire component handles file uploads:

```php
// In Blade template
@livewire('aura::media-uploader', [
    'field' => $field,           // Field definition array
    'fieldSlug' => $field['slug'],
    'resource' => $resource->getSlug(),
    'selected' => $selectedIds,  // Currently selected attachment IDs
    'button' => false,           // Show as button vs dropzone
    'table' => true,             // Show table of uploads
    'ownerToken' => $ownerToken, // Issued by the owning Aura component
])
```

The component uses Livewire's `WithFileUploads` trait and stores files on the
configured media disk/path. The owner token is server-issued, actor/team/owner
bound, and revalidated on mount and hydration. Field-bound uploaders also
re-resolve their registered resource and owned Image or File field before any
storage or database write; do not accept either context from arbitrary input.

### Upload Process

1. **Authorization**: Fresh owner authorization and Attachment `create`
2. **Validation**: File type, size, and count checks (hard limit: 20 files)
3. **Storage**: Files saved to the configured disk
4. **Database**: Attachment record created transactionally
5. **Processing**: Thumbnail generation queued
6. **Response**: Token-bound attachment IDs returned

If Attachment persistence fails, Aura removes the just-stored file. It does not
emit upload success for denied or orphaned rows.

### Upload Validation

```php
// MediaUploader.php validation (Livewire file upload limit)
$this->validate([
    'media' => 'required|array|max:20',
    'media.*' => 'required|max:102400', // 100MB max per file
]);

// The config max_file_size (in KB) is for reference/UI display
// Actual server limits are controlled by php.ini settings

// Custom validation in resource
public function rules()
{
    return [
        'featured_image' => 'required',
        'documents' => 'array|max:5',
    ];
}
```


## File Management

### Attachment Resource

The Attachment resource (`Aura\Base\Resources\Attachment`) provides comprehensive file management. It uses the standard `posts` table with `type = 'Attachment'`.

```php
use Aura\Base\Resources\Attachment;

// Query attachments
$images = Attachment::where('mime_type', 'like', 'image/%')->get();
$pdfs = Attachment::where('mime_type', 'application/pdf')->get();

// File information
$attachment = Attachment::find($id);
echo $attachment->name;                    // Original filename
echo $attachment->readable_filesize;       // "2.5 MB", "150 KB"
echo $attachment->readable_mime_type;      // "JPEG", "PDF", "MP4"
echo $attachment->path();                  // Full asset URL
echo $attachment->thumbnail('md');         // Medium thumbnail URL
echo $attachment->filePath();              // Absolute server path
echo $attachment->filePath('md');          // Absolute path to sized version

// Check file type
if ($attachment->isImage()) {
    // Handle image-specific logic
}
```

### File Operations

```php
// Delete file record (use deleteAttachment action for full cleanup)
$attachment->delete();

// Bulk delete
Attachment::whereIn('id', $ids)->delete();

// Update metadata
$attachment->update([
    'name' => 'new-name.jpg',
]);

// Access computed attributes
echo $attachment->readable_filesize;  // "2.5 MB"
echo $attachment->readable_mime_type; // "JPEG", "PDF", "MP4", etc.
echo $attachment->isImage();          // true/false
```

### Attachment Fields

The Attachment resource stores standard file metadata in the `fields` JSON column:

```php
// Standard fields stored automatically
[
    'name' => 'image.jpg',        // Original filename
    'url' => 'media/image.jpg',   // Storage path
    'size' => 245678,             // File size in bytes
    'mime_type' => 'image/jpeg',  // MIME type
    'thumbnail_url' => '...',     // Generated thumbnail path
]

// Access via model
$attachment->name;       // From fields
$attachment->url;        // From fields
$attachment->size;       // From fields
$attachment->mime_type;  // From fields
```

The Attachment resource also stores `title` at the model level (used for display).

## Image Processing

### Automatic Thumbnail Generation

Thumbnails are generated automatically via queued jobs when an image is saved:

```php
// Triggered automatically on save (in Attachment::booted())
static::saved(function (Attachment $attachment) {
    if ($attachment->isImage()) {
        GenerateImageThumbnail::dispatch($attachment);
    }
});
```

The job reads settings from `Aura::option('media')` which allows runtime configuration via the admin settings panel. If `generate_thumbnails` is disabled, no thumbnails are created.

### Manual Thumbnail Generation

```php
use Aura\Base\Services\ThumbnailGenerator;

$generator = app(ThumbnailGenerator::class);

// Generate specific size (cropped to fit)
$thumbnailPath = $generator->generate('media/image.jpg', 800, 600);
// Returns: 'thumbnails/media/800_600_image.jpg'

// Width-only (maintains aspect ratio, no upscaling)
$thumbnailPath = $generator->generate('media/image.jpg', 1200);
// Returns: 'thumbnails/media/1200_auto_image.jpg'
```

> **Note**: If `restrict_to_dimensions` is enabled in config, only dimensions defined in `dimensions` array are allowed. Requesting other dimensions will throw a `NotFoundHttpException`.

### Image URL Generation

```php
// Using thumbnail method with predefined sizes
$attachment->thumbnail('xs');   // 200px width
$attachment->thumbnail('sm');   // 600px width (default)
$attachment->thumbnail('md');   // 1200px width
$attachment->thumbnail('lg');   // 2000px width
$attachment->thumbnail('thumbnail'); // 600x600 cropped

// Get original file URL
$attachment->path();            // Full URL to original

// Get file path with specific size
$attachment->path('md');        // URL if thumbnail exists

// Using route for on-demand generation
route('aura.image', [
    'path' => $attachment->url,
    'width' => 800,
    'height' => 400, // Optional
]);
```

> **Note**: For non-image files, `thumbnail()` returns the original file path.

### Image Processing Features

- **Smart Resizing**: Maintains aspect ratio when only width is specified
- **No Upscaling**: Returns original path if requested size exceeds original dimensions
- **Format Optimization**: Converts to JPEG with configurable quality (set via `media.quality`)
- **Cached Thumbnails**: Existing thumbnails are returned without regeneration
- **Dimension Restrictions**: Optional security feature to only allow configured sizes

## Media Selection

### Media Manager Modal

Aura Image and File fields issue the owner context and open the media-manager
slot. Low-level integrations must use the same server protocol; a slug and array
of IDs are not sufficient authority:

```php
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;

$this->dispatch('openModal',
    component: ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
    arguments: [
        'model' => $this->model::class,
        'slug' => 'gallery',
        'selected' => $this->form['fields']['gallery'],
        'ownerToken' => $this->mediaOwnerToken('gallery'),
    ],
    modalAttributes: ['persistent' => true],
);
```

The modal host adds the fifth named input, `modalAttributes`. A media-manager
slot candidate must accept exactly the named inputs `model: string`,
`slug: string`, `selected: ?array`, `ownerToken: string`, and
`modalAttributes: array`, and must expose these public `void` actions:

```php
public function requestMediaSelection(array $value): void;

#[On('aura-media-selection-acknowledged')]
public function acknowledgeMediaSelection(
    string $ownerToken,
    string $requestToken,
    string $outcome,
    ?string $errorCode = null,
): void;

public function expireMediaSelection(string $requestToken): void;
```

`requestMediaSelection()` emits `aura-media-selection-requested` with exactly
`ownerToken`, `requestToken`, `slug`, and normalized `value`. The locked owner
listener validates the correlated token/value context, fresh authorization, and
field `max_files`; it then emits `aura-media-selection-acknowledged` with
`ownerToken`, `requestToken`, `outcome`, and nullable `errorCode` after durable
settlement. The manager rereads that record and closes only for `succeeded`.
Failed or expired requests roll back the form, suppress post-commit effects, keep
the modal open, and allow retry. A global close is also rejected while any
request for that owner is `pending` or `processing`, including during a
timeout/apply race.

Before a record can authorize a manager result or unlock the modal, Aura
validates its complete state tuple and cache fences. Pending records have no
claim, error, or completion times; processing records have one live claim and a
claim time; terminal records clear the claim and carry a compatible error and
completion time. Success and ordinary failure must complete before the deadline;
expiry uses only `selection_timeout` and completes at or after the deadline. The
opaque token, owner-wide index, and manager-scope pointer must also identify the
same record. Unknown states, malformed timestamps/errors, duplicate indexes, and
detached records fail closed rather than manufacturing success or timeout.

The former slug-only `updateField` and immediate `closeModal` sequence is not a
supported picker integration.

For backward compatibility, the declared `aura::media-manager` modal action may
receive either `resource` as a registered resource slug or `model` as the exact
class of a registered resource. It rejects arbitrary container classes and
issues the protected owner token server-side. Modal attributes remain top-level;
only `persistent`, `modalClasses`, and `slideOver` are accepted.

### Selection Features

- **Grid View**: Default display mode showing thumbnails
- **Pagination**: 25 items per page
- **Multi-select**: Select multiple attachments
- **Preview**: Image thumbnails for visual files
- **Integration**: Syncs selection with parent form via Livewire events

### Attachment Visibility Contract

The configured Attachment policy must implement
`Aura\Base\Contracts\ScopesMediaVisibility`:

```php
public function scopeMediaVisibility(
    Builder $query,
    Authenticatable $actor,
    Resource $resource,
): Builder;
```

Aura applies that same SQL builder scope to pagination and every explicit ID
lookup, then enforces `viewAny` and per-record `view`. If the policy does not
implement the interface, or one ID is missing/denied, the full explicit
selection is rejected. There is no per-model unscoped fallback. Actor and team
changes cause a fresh scope evaluation. The Attachment Details panel uses this
same path for client-supplied mount IDs, open/navigation loads, renders, and each
hydration request; it never falls back to an unscoped model lookup.

### Picker Details Snapshots

Opening attachment details from a picker uses a short-lived opaque snapshot. It
binds the actor, current team, owner token, picker/details component, field slug,
chosen attachment, ordered visible rows, and current selection. The details
broker consumes that snapshot once using compare-and-delete under a shared lock.
Aura stages an identical recovery copy before removing the active copy; that
recovery remains until lock release succeeds, and its final atomic deletion
elects the sole successful consumer. A staging, delete, or lock failure returns
no snapshot and the same valid token remains safely retryable. A successful
consume cannot be replayed. Browser-supplied attachment/row/selection fields do
not replace the server snapshot.

### Security Cache Store

Set `aura.media.security.cache_store` to a named, non-default store that resolves
through Laravel's actual cache manager to an exact built-in database store. Set
`cache.serializable_classes` to `false`. The configured cache and lock
connections must be the instances returned by Laravel's database manager, and
the cache and lock tables must be distinct physical tables that do not alias the
default cache's data or lock table. Database children of a default failover
store and custom defaults that resolve to database stores are checked; connection table prefixes
and alternate paths to the same SQLite inode are included in alias
detection. Table names must be unqualified lowercase base-table identifiers.
views, temporary tables, and synonyms fail closed. PostgreSQL search-path
resolution is verified natively; SQL Server relations are resolved through
`OBJECT_ID`. Aura maintains reserved persistent identity rows in both security
tables and validates them under the same transaction as each operation, making
same-name replacement fail closed without privileged database metadata access.
File, Redis,
Memcached, DynamoDB, failover,
process-local, custom, subclassed, and proxied stores fail closed.

Aura pins security operations to validated write PDO instances and
schema-qualified physical tables, disables reconnects on those private
connections, and rejects separate read or direct PDO targets. Session namespace
changes therefore cannot redirect an operation after validation. Relation
markers are checked before and after I/O, so injected same-connection DDL fails
closed before a result is returned.

All web and worker processes must use the same database and tables. Multi-node
deployments therefore need a shared network database; node-local SQLite does
not provide shared media security state.

Every cache `add`, `put`, `forget`, lock acquisition, and lock release result is
authoritative. False results and exceptions fail closed. An incomplete begin is
compensated. If all begin writes are durable before a release failure, an exact
authorized retry may recover only that same token after the scope, owner-wide
index, record, actor, team, manager, and value bindings match under both locks. A
failed details deletion is not reported as consumed, and a failed selection
settlement leaves the request active and the modal locked for retry or the
configured TTL/retention recovery path.

### Replacing the Media Manager

`media-manager` and `global-search` are Aura's only supported Livewire component
slots. Applications configure `aura.component-slots.media-manager`; plugins call
`Aura::registerComponentSlots('vendor/package', ['media-manager' => ...])` from
a non-deferred provider. Host choice wins over one distinct plugin candidate,
which wins over Aura's default. Conflicting plugin classes, invalid candidates,
and direct `Livewire::component()` claims on the compatibility aliases fail
boot. See [Livewire Components](livewire-components.md#supported-component-slots)
for the full registration and compatibility contract.


## Programmatic Usage

### Importing Files

```php
use Aura\Base\Resources\Attachment;

// Import from URL (downloads and stores the file)
$attachment = Attachment::import(
    'https://example.com/image.jpg',
    'attachments' // folder within public disk (default: 'attachments')
);
// File is stored at: storage/app/public/attachments/{unique_id}.jpg

// Import from uploaded file
$file = $request->file('upload');
$attachment = Attachment::create([
    'name' => $file->getClientOriginalName(),
    'title' => $file->getClientOriginalName(),
    'url' => $file->store('media', 'public'),
    'size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
]);

// Bulk import from URLs
$urls = [
    'https://example.com/image1.jpg',
    'https://example.com/image2.jpg',
];

$attachments = collect($urls)->map(function ($url) {
    return Attachment::import($url);
});
```

### Working with Attachments

```php
// In controllers
public function store(Request $request)
{
    $post = Post::create($request->validated());
    
    // Handle single image
    if ($request->hasFile('image')) {
        $attachment = $this->uploadFile($request->file('image'));
        $post->update(['featured_image' => $attachment->id]);
    }
    
    // Handle multiple files
    if ($request->hasFile('gallery')) {
        $ids = collect($request->file('gallery'))
            ->map(fn($file) => $this->uploadFile($file)->id)
            ->toArray();
        $post->update(['gallery' => $ids]);
    }
}

private function uploadFile($file)
{
    return Attachment::create([
        'name' => $file->getClientOriginalName(),
        'url' => $file->store('media', 'public'),
        'size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
    ]);
}
```

### API Endpoints

```php
// routes/api.php
Route::post('/media/upload', function (Request $request) {
    $request->validate([
        'file' => 'required|file|max:10240',
    ]);
    
    $attachment = Attachment::create([
        'name' => $request->file('file')->getClientOriginalName(),
        'url' => $request->file('file')->store('media', 'public'),
        'size' => $request->file('file')->getSize(),
        'mime_type' => $request->file('file')->getMimeType(),
    ]);
    
    return response()->json([
        'id' => $attachment->id,
        'url' => $attachment->path(),
        'thumbnail' => $attachment->thumbnail('sm'),
    ]);
});
```

## Performance Optimization

### Lazy Loading

```php
// In views
<img 
    src="{{ $attachment->thumbnail('xs') }}" 
    data-src="{{ $attachment->thumbnail('lg') }}"
    loading="lazy"
    class="lazyload"
>

// With Alpine.js
<div x-data="{ loaded: false }" x-intersect="loaded = true">
    <img 
        x-show="loaded"
        src="{{ $attachment->thumbnail('md') }}"
        alt="{{ $attachment->name }}"
    >
</div>
```

### Caching Strategies

```php
// Cache attachment queries
$attachments = Cache::remember('gallery-images', 3600, function () {
    return Attachment::where('fields->category', 'gallery')
        ->latest()
        ->take(20)
        ->get();
});

// Cache URLs
$thumbnailUrl = Cache::rememberForever(
    "attachment-{$id}-thumbnail-md",
    fn() => $attachment->thumbnail('md')
);
```

### CDN Integration

```php
// In Attachment model
public function cdnUrl($size = null)
{
    $url = $size ? $this->thumbnail($size) : $this->path();
    
    if (config('app.cdn_url')) {
        return str_replace(
            config('app.url'),
            config('app.cdn_url'),
            $url
        );
    }
    
    return $url;
}
```

### Batch Processing

```php
// Process thumbnails in batches
Attachment::where('mime_type', 'like', 'image/%')
    ->whereNull('fields->thumbnails_generated')
    ->chunk(100, function ($attachments) {
        foreach ($attachments as $attachment) {
            GenerateImageThumbnail::dispatch($attachment)
                ->onQueue('thumbnails');
        }
    });
```

## Advanced Customization

### Custom Attachment Resource

```php
namespace App\Aura\Resources;

use Aura\Base\Resources\Attachment as BaseAttachment;

class Attachment extends BaseAttachment
{
    public static function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Alt Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'alt_text',
                'validation' => 'required|max:255',
            ],
            [
                'name' => 'Copyright',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'copyright',
            ],
            [
                'name' => 'Category',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'category',
                'options' => [
                    'products' => 'Products',
                    'blog' => 'Blog',
                    'gallery' => 'Gallery',
                ],
            ],
        ]);
    }
    
    // Custom scopes
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }
    
    public function scopeByCategory($query, $category)
    {
        return $query->where('fields->category', $category);
    }
}
```

### Custom Upload Handler

```php
namespace App\Services;

use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;

class MediaUploadService
{
    public function upload(UploadedFile $file, array $metadata = [])
    {
        // Custom processing
        $this->validateFile($file);
        $this->scanForViruses($file);
        
        // Generate custom path
        $path = $this->generatePath($file);
        
        // Store with custom disk
        $stored = Storage::disk('s3')->putFileAs(
            $path,
            $file,
            $file->hashName()
        );
        
        // Create attachment
        return Attachment::create([
            'name' => $file->getClientOriginalName(),
            'url' => $stored,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'fields' => array_merge([
                'original_name' => $file->getClientOriginalName(),
                'hash' => md5_file($file->getRealPath()),
                'uploaded_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ], $metadata),
        ]);
    }
    
    private function generatePath(UploadedFile $file)
    {
        return sprintf(
            'media/%s/%s',
            now()->format('Y/m'),
            Str::random(8)
        );
    }
}
```

### Custom Image Processing

```php
namespace App\Services;

use Aura\Base\Services\ThumbnailGenerator as BaseThumbnailGenerator;
use Intervention\Image\Laravel\Facades\Image;

class ThumbnailGenerator extends BaseThumbnailGenerator
{
    public function generate(string $path, int $width, ?int $height = null): string
    {
        // Call parent for standard processing
        $thumbnailPath = parent::generate($path, $width, $height);
        
        // Additional processing
        $image = Image::read(Storage::disk('public')->path($thumbnailPath));
        
        // Add watermark
        if ($width > 600) {
            $watermark = Image::read(public_path('watermark.png'));
            $image->place($watermark, 'bottom-right', 10, 10);
        }
        
        // Apply filters
        $image->sharpen(5);
        
        // Save
        $image->save();
        
        return $thumbnailPath;
    }
}
```

## Troubleshooting

### Common Issues

**1. Thumbnails Not Generating**
```bash
# Check queue is running
php artisan queue:work

# Check logs
tail -f storage/logs/laravel.log

# Manually regenerate
php artisan aura:generate-thumbnails
```

**2. Upload Failures**
```php
// Check PHP settings
ini_get('upload_max_filesize');  // Default: 2M
ini_get('post_max_size');        // Default: 8M
ini_get('max_file_uploads');     // Default: 20

// Update in php.ini or .htaccess
upload_max_filesize = 100M
post_max_size = 100M
```

**3. Storage Permission Issues**
```bash
# Fix permissions
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public

# Create symbolic link
php artisan storage:link
```

**4. Memory Issues with Large Images**
```php
// Increase memory limit for image processing
ini_set('memory_limit', '256M');

// Or in job
public function handle()
{
    ini_set('memory_limit', '512M');
    // Process image...
}
```

### Debugging

```php
// Enable query logging
DB::enableQueryLog();
$attachments = Attachment::where('type', 'image')->get();
dd(DB::getQueryLog());

// Debug upload process
Log::channel('media')->info('Upload started', [
    'file' => $file->getClientOriginalName(),
    'size' => $file->getSize(),
    'mime' => $file->getMimeType(),
]);

// Test thumbnail generation
$attachment = Attachment::first();
$job = new GenerateImageThumbnail($attachment);
$job->handle(app(ThumbnailGenerator::class));
```

### Pro Tips

1. **Use Queues**: Always process thumbnails in background
2. **Optimize Images**: Consider using image optimization services
3. **CDN Integration**: Serve media from CDN in production
4. **Lazy Loading**: Implement lazy loading for better performance
5. **Clean Up**: Regularly clean orphaned files
6. **Monitor Storage**: Set up alerts for disk usage
7. **Validate Types**: Validate MIME types server-side
8. **Chunk Uploads**: For large files, use chunked uploads

The Media Manager provides a robust foundation for handling all media needs in your Aura CMS application. Its flexible architecture allows for easy customization while maintaining excellent performance and user experience.
