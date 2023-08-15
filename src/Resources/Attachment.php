<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Jobs\GenerateImageThumbnail;
use Eminiarts\Aura\Resource;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Resource
{
    use DispatchesJobs;

    public static $contextMenu = false;

    public static ?string $name = 'Media';

    public static ?string $slug = 'attachment';

    public static ?int $sort = 2;

    public static string $type = 'Attachment';

    protected static ?string $group = 'Aura';

    public function defaultPerPage()
    {
        return 25;
    }

    public function defaultTableView()
    {
        return 'grid';
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Attachment',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'panel1',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Preview',
                'type' => 'Eminiarts\\Aura\\Fields\\Embed',
                'validation' => '',
                'on_index' => false,
                'slug' => 'embed',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Details',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'panel2',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Url',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'url',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => '',
                'on_index' => false,
                'slug' => 'thumbnail_url',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Mime Type',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'mime_type',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'size',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'enable_time' => true,
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Updated at',
                'slug' => 'updated_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'enable_time' => true,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>';
    }

    public function getReadableFilesizeAttribute()
    {
        $bytes = $this->size;

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getReadableMimeTypeAttribute()
    {
        $mimeTypeToReadable = [
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'application/pdf' => 'PDF',
            'application/docx' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.ms-powerpoint' => 'PPT',
            'application/vnd.ms-word' => 'DOC',
            'video/quicktime' => 'MOV',
            'video/mp4' => 'MP4',
            'video/x-msvideo' => 'AVI',
            'video/x-ms-wmv' => 'WMV',
            'audio/mpeg' => 'MP3',
            'audio/mp3' => 'MP3',
            'audio/x-mpeg' => 'MP3',
            'audio/x-mp3' => 'MP3',
            'audio/mpeg3' => 'MP3',
            'audio/x-mpeg3' => 'MP3',
            'audio/mpg' => 'MP3',
            'audio/x-mpg' => 'MP3',
            'audio/x-mpegaudio' => 'MP3',
        ];

        return $mimeTypeToReadable[$this->mime_type] ?? $this->mime_type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function import($url, $folder = 'attachments')
    {
        // Download the image
        $imageContent = file_get_contents($url);

        // Generate a unique file name
        $fileName = uniqid().'.jpg';

        // Save the image to the desired storage
        $storagePath = "{$folder}/{$fileName}";
        Storage::disk('public')->put($storagePath, $imageContent);

        // Get the image size and mime type
        $imageSize = Storage::disk('public')->size($storagePath);
        $imageMimeType = Storage::disk('public')->mimeType($storagePath);

        // Create a new Attachment instance
        $attachment = self::create([
            'url' => $storagePath,
            'name' => $fileName,
            'title' => $fileName,
            'size' => $imageSize,
            'mime_type' => $imageMimeType,
        ]);

        return $attachment;
    }

    public function isImage()
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    public function path($size = null)
    {
        if ($size) {
            $url = Str::after($this->url, 'media/');

            return asset('storage/'.$size.'/'.$url);
        }

        return asset('storage/'.$this->url);
    }


    public function filePath($size = null)
    {
        // Base storage directory
        $basePath = storage_path('app/public');

        if ($size) {
            $relativePath = Str::after($this->url, 'media/');
            return $basePath . '/' . $size . '/' . $relativePath;
        }

        return $basePath . '/' . $this->url;
    }

    public function tableGridView()
    {
        return 'aura::attachment.grid';
    }

    public function tableRowView()
    {
        return 'aura::attachment.row';
    }

    public function thumbnail()
    {
        $mimeTypeToThumbnail = [
            'image/jpeg' => $this->url,
            'image/png' => $this->url,
            'application/pdf' => 'pdf.jpg',
            'application/docx' => 'docx.jpg',
        ];

        return $mimeTypeToThumbnail[$this->mime_type] ?? 'default-thumbnail.jpg';
    }

    public function thumbnail_path()
    {
        return asset('storage/'.$this->thumbnail_url);
    }

    protected static function booted()
    {
        parent::booted();

        static::saved(function (Attachment $attachment) {
            // Check if the attachment has a file

            // Dispatch the GenerateThumbnail job
            if ($attachment->isImage()) {
                GenerateImageThumbnail::dispatch($attachment);
            }
        });
    }
}
