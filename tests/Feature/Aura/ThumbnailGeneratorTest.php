<?php

use Aura\Base\Services\ThumbnailGenerator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * End-to-end cover for ThumbnailGenerator on a faked public disk. Also pins the
 * makeDirectory() call, which used to be invoked with the pre-Laravel-11
 * three-argument signature.
 */
function putFakeJpeg(string $path, int $width = 400, int $height = 300): void
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 10, 120, 200));

    ob_start();
    imagejpeg($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    Storage::disk('public')->put($path, $contents);
}

beforeEach(function () {
    Storage::fake('public');
    config()->set('aura.media.disk', 'public');
});

test('it writes a width-only thumbnail to the configured disk', function () {
    putFakeJpeg('media/photo.jpg');

    $path = app(ThumbnailGenerator::class)->generate('media/photo.jpg', 200);

    expect($path)->toBe('thumbnails/media/200_auto_photo.jpg');
    Storage::disk('public')->assertExists($path);
});

test('it writes a fixed width x height thumbnail', function () {
    config()->set('aura.media.dimensions', [['name' => 'sq', 'width' => 200, 'height' => 200]]);
    putFakeJpeg('media/photo.jpg');

    $path = app(ThumbnailGenerator::class)->generate('media/photo.jpg', 200, 200);

    expect($path)->toBe('thumbnails/media/200_200_photo.jpg');
    Storage::disk('public')->assertExists($path);
});

test('it reuses an existing thumbnail instead of regenerating', function () {
    putFakeJpeg('media/photo.jpg');

    $generator = app(ThumbnailGenerator::class);
    $path = $generator->generate('media/photo.jpg', 200);
    $first = Storage::disk('public')->get($path);

    expect($generator->generate('media/photo.jpg', 200))->toBe($path)
        ->and(Storage::disk('public')->get($path))->toBe($first);
});

test('it rejects dimensions that are not configured', function () {
    putFakeJpeg('media/photo.jpg');

    expect(fn () => app(ThumbnailGenerator::class)->generate('media/photo.jpg', 137))
        ->toThrow(NotFoundHttpException::class);
});

test('it returns the original path when the requested width exceeds the source', function () {
    config()->set('aura.media.dimensions', [['name' => 'xl', 'width' => 2000]]);
    putFakeJpeg('media/photo.jpg');

    expect(app(ThumbnailGenerator::class)->generate('media/photo.jpg', 2000))
        ->toBe('media/photo.jpg');
});
