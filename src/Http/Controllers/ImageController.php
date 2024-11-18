<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct(
        protected ThumbnailGenerator $thumbnailGenerator
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $path)
    {
        // Get query parameters for width and height if set
        $width = $request->query('width', 200);
        $height = $request->query('height');

        // Generate the thumbnail
        $thumbnailPath = $this->thumbnailGenerator->generate($path, $width, $height);

        // Get the file contents from storage
        if (!Storage::disk('public')->exists($thumbnailPath)) {
            abort(404);
        }

        // Get the file contents and mime type
        $contents = Storage::disk('public')->get($thumbnailPath);
        
        // Return the image with image/jpeg mime type since we're always saving as JPG
        return response($contents)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Length', strlen($contents));
    }
}
