<?php

namespace Aura\Base\Resources;

use Aura\Base\Jobs\GenerateImageThumbnail;
use Aura\Base\Resource;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Resource
{
    use DispatchesJobs;

    public array $actions = [
        'deleteAttachment' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Post?',
            'confirm-content' => 'Are you sure you want to delete this post?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
    ];

    public array $bulkActions = [
        // 'deleteSelected' => 'Delete',
        // 'deleteSelected' => [
        //     'label' => 'Delete',
        //     'method' => 'collection',
        // ],
    ];

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

    public function deleteAttachment()
    {
        parent::delete();

        return redirect()->route('aura.resource.index', ['slug' => 'Attachment']);
    }

    public function deleteSelected($ids)
    {
        self::whereIn('id', $ids)->delete();

        // return redirect()->route('aura.resource.index', ['slug' => 'Attachment'])->with('success', $deletedCount . ' attachments have been deleted.');
    }

    public function filePath($size = null)
    {
        // Base storage directory
        $basePath = storage_path('app/public');

        if ($size) {
            $relativePath = Str::after($this->url, 'media/');

            return $basePath.'/'.$size.'/'.$relativePath;
        }

        return $basePath.'/'.$this->url;
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Attachment',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel1',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Preview',
                'type' => 'Aura\\Base\\Fields\\Embed',
                'validation' => '',
                'on_index' => false,
                'slug' => 'embed',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Details',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel2',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'searchable' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Url',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'searchable' => true,
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'url',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'validation' => '',
                'slug' => 'tags',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => '',
                'on_index' => false,
                'slug' => 'thumbnail_url',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Mime Type',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => 'required',
                'searchable' => true,
                'on_index' => true,
                'slug' => 'mime_type',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Aura\\Base\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'size',
                'style' => [
                    'width' => '33',
                ],
            ],
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Aura\\Base\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /> </svg>';
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

            $assetPath = 'storage/'.$size.'/'.$url;

            if (file_exists(public_path($assetPath))) {
                return asset($assetPath);
            }
        }

        return asset('storage/'.$this->url);
    }

    public function tableGridView()
    {
        return 'aura::attachment.grid';
    }

    public function tableView()
    {
        return 'aura::attachment.list';
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

        // when model saved and status is "eingang" and doctor_id is set, change status to "erstellt"
        // static::creating(function ($model) {
        //     if (! $model->team_id) {
        //         $model->team_id = 1;
        //     }
        // });

        static::saved(function (Attachment $attachment) {
            // Check if the attachment has a file

            // Dispatch the GenerateThumbnail job
            if ($attachment->isImage()) {
                GenerateImageThumbnail::dispatch($attachment);
            }
        });
    }
}
