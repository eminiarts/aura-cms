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

> 📹 **Video Placeholder**: [Overview of Media Manager interface showing upload process, file organization, and integration with resource forms]

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

> **Note**: The `max_files` limit is configured per-field using the `max_files` option on Image or File fields, not globally.

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
    'for' => $fieldSlug,         // Field identifier
    'selected' => $selectedIds,  // Currently selected attachment IDs
    'button' => false,           // Show as button vs dropzone
    'table' => true,             // Show table of uploads
])
```

The component uses Livewire's `WithFileUploads` trait and stores files to the `media` folder on the `public` disk.

### Upload Process

1. **Validation**: File type, size, and count checks
2. **Storage**: Files saved to configured disk
3. **Database**: Attachment record created
4. **Processing**: Thumbnail generation queued
5. **Response**: Attachment IDs returned

### Upload Validation

```php
// MediaUploader.php validation (Livewire file upload limit)
$this->validate([
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

> 📹 **Video Placeholder**: [Demonstration of file upload process including drag-and-drop, progress indicators, and error handling]

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

The `MediaManager` Livewire component provides a selection interface:

```php
// Open media manager modal
$this->dispatch('openModal', 
    component: 'aura::media-manager',
    arguments: [
        'model' => get_class($this->model), // Resource class name
        'slug' => 'gallery',                 // Field slug
        'selected' => $this->selected,       // Currently selected IDs
        'modalAttributes' => [
            'multiple' => true,
            'maxFiles' => 10,
        ],
    ]
);

// Listen for selection
// The component dispatches 'updateField' with selected IDs
// and 'media-manager-selected' when complete
```

### Selection Features

- **Grid View**: Default display mode showing thumbnails
- **Pagination**: 25 items per page
- **Multi-select**: Select multiple attachments
- **Preview**: Image thumbnails for visual files
- **Integration**: Syncs selection with parent form via Livewire events

> 📹 **Video Placeholder**: [Media Manager modal interface showing selection process, search, filtering, and multi-select functionality]

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
use Intervention\Image\Facades\Image;

class ThumbnailGenerator extends BaseThumbnailGenerator
{
    public function generate(string $path, int $width, ?int $height = null): string
    {
        // Call parent for standard processing
        $thumbnailPath = parent::generate($path, $width, $height);
        
        // Additional processing
        $image = Image::make(Storage::disk('public')->path($thumbnailPath));
        
        // Add watermark
        if ($width > 600) {
            $watermark = Image::make(public_path('watermark.png'));
            $image->insert($watermark, 'bottom-right', 10, 10);
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
