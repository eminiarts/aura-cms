<?php

namespace Aura\Base\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $path)
    {
        // Parse the path to remove any query parameters and get the base name
        $pathInfo = pathinfo($path);
        $basename = $pathInfo['basename'];

        // Extract the folder path and prepend 'thumbnails/' to it
        $folderPath = Str::beforeLast($path, '/').'/';
        $thumbnailFolder = 'thumbnails/'.$folderPath;

        // Get query parameters for width and height if set
        $width = $request->query('width');
        $height = $request->query('height');

        // Determine thumbnail path based on provided width and/or height
        if ($width && ! $height) {
            // If only width is set, keep original aspect ratio
            $thumbnailPath = $thumbnailFolder.$width.'_auto_'.$basename;
        } else {
            // If no width and no height is set, default to 200x200
            $width = $width ?: 200;
            $height = $height ?: 200;
            $thumbnailPath = $thumbnailFolder.$width.'_'.$height.'_'.$basename;
        }

        // Check if the thumbnail already exists
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return response()->file(storage_path('app/public/'.$thumbnailPath));
        }

        // Check if the original image exists
        if (! Storage::disk('public')->exists($folderPath.$basename)) {
            abort(404, 'Image not found.');
        }

        // Create thumbnail
        $image = Image::make(storage_path('app/public/'.$path));
        if ($width && ! $height) {
            // Resize image to specified width, maintaining aspect ratio
            $image->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            // Resize and crop image to specified width and height
            $image->fit($width, $height);
        }

        // Ensure the thumbnail directory exists
        if (! Storage::disk('public')->exists($thumbnailFolder)) {
            Storage::disk('public')->makeDirectory($thumbnailFolder);
        }

        // Save the thumbnail image
        $image->save(storage_path('app/public/'.$thumbnailPath));

        // Return the thumbnail image
        return $image->response();
    }
}
