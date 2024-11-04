<?php

namespace Aura\Base\Jobs;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Attachment;
use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     */
    public function handle(ThumbnailGenerator $thumbnailGenerator)
    {
        // Skip in tests
        if (app()->environment('testing')) {
            return;
        }

        // Get the settings from aura and check which thumbnail sizes are enabled
        $settings = Aura::option('media');

        if (!$settings || !($settings['generate_thumbnails'] ?? false)) {
            return;
        }

        // Get the path from the fields array
        $relativePath = $this->attachment->fields['url'] ?? null;
        if (empty($relativePath)) {
            logger()->error('Empty attachment URL', [
                'attachment' => $this->attachment->toArray()
            ]);
            return;
        }

        logger()->info('Processing attachment', [
            'relativePath' => $relativePath
        ]);

        // Generate thumbnails for each configured size
        foreach ($settings['dimensions'] as $thumbnail) {
            try {
                logger()->info('Generating thumbnail', [
                    'relativePath' => $relativePath,
                    'size' => $thumbnail
                ]);

                $width = $thumbnail['width'] ?? null;
                $height = $thumbnail['height'] ?? null;

                if ($width === null) {
                    throw new \InvalidArgumentException("Width is not defined for thumbnail size: " . ($thumbnail['name'] ?? 'unknown'));
                }

                $thumbnailGenerator->generate(
                    $relativePath,
                    $width,
                    $height
                );

            } catch (\Exception $e) {
                logger()->error('Failed to generate thumbnail', [
                    'size' => $thumbnail,
                    'path' => $relativePath,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
