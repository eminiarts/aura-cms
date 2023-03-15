<?php

namespace Eminiarts\Aura\Jobs;

use Illuminate\Bus\Queueable;
use Intervention\Image\Facades\Image;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Eminiarts\Aura\Resources\Attachment;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateImageThumbnail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The attachment to generate a thumbnail for.
     *
     * @var Attachment
     */
    public $attachment;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Set the desired storage path for the thumbnail
        $thumbnailPath = 'thumbnails/' . basename($this->attachment->url);

        // If the thumbnail already exists, don't generate it again
        if (Storage::exists($thumbnailPath)) {
            return;
        }

        $image = Image::make($this->attachment->path());

        $width = 300;
        $height = 300;

        $image->fit($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        Storage::put($thumbnailPath, (string) $image->encode());

        $this->attachment->update(['thumbnail_url' => Storage::url($thumbnailPath)]);
    }
}
