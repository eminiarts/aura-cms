<?php

namespace Aura\Base\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailGenerator
{
    /**
     * Generate a thumbnail for the given image path with specified dimensions
     */
    public function generate(string $path, int $width, ?int $height = null): string
    {
        // Get config values
        $quality = Config::get('aura.media.quality', 80) / 100;
        $restrictDimensions = Config::get('aura.media.restrict_to_dimensions', true);

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
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        // Check if the original image exists
        if (! Storage::disk('public')->exists($folderPath.$basename)) {
            throw new \Exception('Original image not found: '.$path);
        }

        // Create thumbnail
        $imageContents = Storage::disk('public')->get($path);
        $image = Image::make($imageContents);

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
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            // When both dimensions are specified
            if ($width > $originalWidth && $height > $originalHeight) {
                // If both requested dimensions are larger than original,
                // fit to the smallest possible size while maintaining aspect ratio
                $ratio = min($originalWidth / $width, $originalHeight / $height);
                $targetWidth = (int) ($width * $ratio);
                $targetHeight = (int) ($height * $ratio);
                $image->fit($targetWidth, $targetHeight);
            } else {
                // Otherwise use the requested dimensions
                $image->fit($width, $height);
            }
        }

        // Ensure the thumbnail directory exists
        if (! Storage::disk('public')->exists($thumbnailFolder)) {
            Storage::disk('public')->makeDirectory($thumbnailFolder, 0755, true);
        }

        // Save the thumbnail image with quality from config
        $image->encode('jpg', $quality * 100);
        Storage::disk('public')->put($thumbnailPath, (string) $image);

        return $thumbnailPath;
    }
}
