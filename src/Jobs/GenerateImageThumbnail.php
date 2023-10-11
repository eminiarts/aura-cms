<?php

namespace Eminiarts\Aura\Jobs;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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
        // Skip in tests
        if (app()->environment('testing')) {
            return;
        }

        // Set the desired storage path for the thumbnail
        $thumbnailPath = 'thumbnails/'.basename($this->attachment->url);

        // If the thumbnail already exists, don't generate it again
        if (Storage::exists($thumbnailPath)) {
            return;
        }

        $settings = Aura::option('media');

        // Generate the thumbnails
        if ($settings && $settings['thumbnails']) {
            foreach ($settings['thumbnails'] as $size) {
                if (file_exists($this->attachment->path())) {
                    $image = Image::make($this->attachment->path());
                    $width = $size['width'];

                    $height = $size['height'];

                    $image->fit($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                    Storage::put('public/'.$size['name'].'/'.basename($this->attachment->url), (string) $image->encode());
                } else {
                    // throw new \Exception('File does not exist at path: ' . $this->attachment->path());
                }

            }
        }
    }
}
