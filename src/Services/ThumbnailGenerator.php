<?php

namespace Aura\Base\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailGenerator
{
    /**
     * Generate a thumbnail for the given image path with specified dimensions
     */
    public function generate(string $path, int $width, ?int $height = null): string
    {
        // Get config values
        $quality = (int) Config::get('aura.media.quality', 80);
        $restrictDimensions = Config::get('aura.media.restrict_to_dimensions', true);
        $disk = Config::get('aura.media.disk', 'public');

        // If dimensions are restricted, validate the requested dimensions
        if ($restrictDimensions) {
            $allowedDimensions = Config::get('aura.media.dimensions', []);
            $dimensionsAllowed = false;

            foreach ($allowedDimensions as $dimension) {
                if ($dimension['width'] === $width) {
                    // If height is provided in request, it must match config
                    if ($height !== null) {
                        if (isset($dimension['height']) && $dimension['height'] === $height) {
                            $dimensionsAllowed = true;
                            break;
                        }
                    } else {
                        // If no height provided in request, that's okay if config doesn't specify height
                        if (! isset($dimension['height'])) {
                            $dimensionsAllowed = true;
                            break;
                        }
                    }
                }
            }

            if (! $dimensionsAllowed) {
                throw new NotFoundHttpException('Requested thumbnail dimensions are not allowed.');
            }
        }

        // Parse the path to get the base name
        $pathInfo = pathinfo($path);
        $basename = $pathInfo['basename'];
        $folderPath = Str::beforeLast($path, '/').'/';
        $thumbnailFolder = 'thumbnails/'.$folderPath;

        // Determine thumbnail path based on provided width and/or height
        if ($width && ! $height) {
            $thumbnailPath = $thumbnailFolder.$width.'_auto_'.$basename;
        } else {
            $height = $height ?: $width;
            $thumbnailPath = $thumbnailFolder.$width.'_'.$height.'_'.$basename;
        }

        // Skip if thumbnail already exists
        if (Storage::disk($disk)->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        // Check if the original image exists
        if (! Storage::disk($disk)->exists($folderPath.$basename)) {
            throw new \Exception('Original image not found: '.$path);
        }

        // Create thumbnail via Intervention ImageManager (no Laravel facade package required)
        $imageContents = Storage::disk($disk)->get($path);
        $manager = new ImageManager(new Driver);
        $image = $manager->read($imageContents);

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if ($width && ! $height) {
            // When only width is specified, maintain aspect ratio and don't upscale
            if ($width > $originalWidth) {
                // If requested width is larger than original, keep original size
                return $path;
            }

            // Resize image to specified width, maintaining aspect ratio
            $image->scale($width);
        } else {
            // When both dimensions are specified
            if ($width > $originalWidth && $height > $originalHeight) {
                // If both requested dimensions are larger than original,
                // fit to the smallest possible size while maintaining aspect ratio
                $ratio = min($originalWidth / $width, $originalHeight / $height);
                $targetWidth = (int) ($width * $ratio);
                $targetHeight = (int) ($height * $ratio);
                $image->cover($targetWidth, $targetHeight);
            } else {
                // Otherwise use the requested dimensions
                $image->cover($width, $height);
            }
        }

        // Ensure the thumbnail directory exists
        if (! Storage::disk($disk)->exists($thumbnailFolder)) {
            Storage::disk($disk)->makeDirectory($thumbnailFolder, 0755, true);
        }

        // Save the thumbnail image with quality from config
        $encodedImage = $image->encodeByExtension('jpg', quality: $quality);
        Storage::disk($disk)->put($thumbnailPath, (string) $encodedImage);

        return $thumbnailPath;
    }
}
