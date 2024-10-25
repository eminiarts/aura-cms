<?php

namespace Aura\Base\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ThumbnailGenerator
{
    /**
     * Generate a thumbnail for the given image path with specified dimensions
     */
    public function generate(string $path, int $width, ?int $height = null, float $quality = 0.80): string
    {
        // Parse the path to get the base name
        $pathInfo = pathinfo($path);
        $basename = $pathInfo['basename'];
        $folderPath = Str::beforeLast($path, '/').'/';
        $thumbnailFolder = 'thumbnails/'.$folderPath;

        // Determine thumbnail path based on provided width and/or height
        if ($width && !$height) {
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
        if (!Storage::disk('public')->exists($folderPath.$basename)) {
            throw new \Exception('Original image not found: ' . $path);
        }

        // Create thumbnail
        $image = Image::make(storage_path('app/public/'.$path));

        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Don't upscale images
        $targetWidth = min($width, $originalWidth);
        $targetHeight = $height ? min($height, $originalHeight) : null;

        if ($targetWidth && !$targetHeight) {
            // Skip resize if target width is larger than original
            if ($targetWidth < $originalWidth) {
                // Resize image to specified width, maintaining aspect ratio
                $image->resize($targetWidth, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        } else {
            // Only resize if target dimensions are smaller than original
            if ($targetWidth < $originalWidth || $targetHeight < $originalHeight) {
                // Resize and crop image to specified width and height
                $image->fit($targetWidth, $targetHeight);
            }
        }

        // Ensure the thumbnail directory exists
        if (!Storage::disk('public')->exists($thumbnailFolder)) {
            Storage::disk('public')->makeDirectory($thumbnailFolder);
        }

        // Save the thumbnail image with specified quality
        $image->save(storage_path('app/public/'.$thumbnailPath), $quality * 100);

        return $thumbnailPath;
    }
}
