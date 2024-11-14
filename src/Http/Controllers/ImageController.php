<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Http\Request;

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

        // ray($thumbnailPath);

        // Return the thumbnail image
        return response()->file(storage_path('app/public/'.$thumbnailPath));
    }
}
