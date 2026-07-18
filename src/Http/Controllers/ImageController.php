<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $attachmentClass = config('aura.resources.attachment');
        $model = app($attachmentClass);

        // 'url' lives in meta for the default posts-table attachment, but is
        // a real column on custom-table attachment resources.
        $attachment = $attachmentClass::query()
            ->when(
                $model->usesMeta() && $model->isMetaField('url'),
                fn ($query) => $query->whereHas('meta', fn ($meta) => $meta->where('key', 'url')->where('value', $path)),
                fn ($query) => $query->where('url', $path),
            )
            ->firstOrFail();

        Gate::authorize('view', $attachment);

        // Get query parameters for width and height if set
        $width = $request->query('width', 200);
        $height = $request->query('height');

        // Generate the thumbnail
        $thumbnailPath = $this->thumbnailGenerator->generate($path, $width, $height);

        $disk = config('aura.media.disk', 'public');

        // Get the file contents from storage
        if (! Storage::disk($disk)->exists($thumbnailPath)) {
            abort(404);
        }

        // Get the file contents and mime type
        $contents = Storage::disk($disk)->get($thumbnailPath);

        // Return the image with image/jpeg mime type since we're always saving as JPG
        return response($contents)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Length', strlen($contents));
    }
}
