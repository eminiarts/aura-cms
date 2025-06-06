# Media Manager in Aura CMS

The Media Manager is a powerful component of Aura CMS that provides a centralized interface for managing all your media assets. It offers comprehensive features for uploading, organizing, and managing files such as images, documents, and other media types.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Media Fields](#media-fields)
- [File Management](#file-management)
- [Image Processing](#image-processing)
- [Integration](#integration)

## Overview

The Media Manager provides a user-friendly interface for:
- Uploading and organizing media files
- Managing file metadata
- Selecting media for resource fields
- Processing images and generating thumbnails
- Handling various file types

## Features

### File Upload

The Media Manager supports multiple file upload methods:

1. **Drag and Drop**
   - Drag files directly into the upload area
   - Supports multiple files simultaneously
   - Visual feedback during upload process

2. **File Selection**
   - Click to open file browser
   - Multiple file selection
   - Progress tracking for uploads

```php
// Maximum file upload configuration
'media' => [
    'max_file_size' => 102400, // 100MB
    'max_files' => 20,
]
```

### File Organization

Files can be organized using:
- Tags for categorization
- Search functionality
- Grid and list views
- Sorting and filtering options

### Media Selection

When used within resource forms:
1. Open Media Manager modal
2. Select single or multiple files
3. Reorder selected files
4. Remove files from selection

## Media Fields

### Image Field

The Image field type provides specialized handling for images:

```php
[
    'name' => 'Featured Image',
    'type' => 'Aura\\Base\\Fields\\Image',
    'slug' => 'featured_image',
    'validation' => 'required',
    'style' => [
        'width' => '100',
    ],
]
```

Configuration options:
- `use_media_manager`: Enable/disable Media Manager integration
- `min_files`: Minimum number of files allowed
- `max_files`: Maximum number of files allowed
- `allowed_file_types`: Comma-separated list of allowed extensions

### File Field

The File field type handles all types of files:

```php
[
    'name' => 'Documents',
    'type' => 'Aura\\Base\\Fields\\File',
    'slug' => 'documents',
    'validation' => 'required',
]
```

## File Management

### Storage Structure

Files are organized in the following structure:
```
storage/
└── app/
    └── public/
        ├── media/          # Original files
        ├── xs/            # Extra small thumbnails
        ├── sm/            # Small thumbnails
        ├── md/            # Medium thumbnails
        └── lg/            # Large thumbnails
```

### File Information

Each file stores the following metadata:
- Name
- Size
- MIME type
- Dimensions (for images)
- Upload date
- Custom tags

### File Operations

Available operations include:
- Upload new files
- Delete files
- Rename files
- Add/remove tags
- Reorder files
- Generate thumbnails

## Image Processing

### Automatic Thumbnail Generation

Images are automatically processed to generate thumbnails:

```php
// Configuration in config/aura.php
'media' => [
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
]
```

### Image URL Generation

Access different image sizes using the thumbnail method:

```php
$attachment->thumbnail('sm'); // Get small thumbnail URL
$attachment->thumbnail('lg'); // Get large thumbnail URL
```

## Integration

### Using in Resources

1. Add media fields to your resource:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Gallery',
            'type' => 'Aura\\Base\\Fields\\Image',
            'slug' => 'gallery',
            'use_media_manager' => true,
            'max_files' => 10,
        ]
    ];
}
```

2. Access media in your views:

```php
@foreach($resource->gallery as $image)
    <img src="{{ $image->thumbnail('md') }}" alt="{{ $image->name }}">
@endforeach
```

### Programmatic Usage

Import files programmatically:

```php
use Aura\Base\Resources\Attachment;

$attachment = Attachment::import('https://example.com/image.jpg', 'folder-name');
```
