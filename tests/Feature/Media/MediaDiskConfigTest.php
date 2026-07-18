<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Default behaviour must not change: uploads still land on the `public`
// disk under the `media/` folder.
test('media uploader stores on the public disk under media by default', function () {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('default.png');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();

    expect($attachment)->not->toBeNull();
    expect($attachment->url)->toStartWith('media/');

    Storage::disk('public')->assertExists($attachment->url);
});

// The configured disk + path are honored end-to-end: the file lands on the
// custom disk under the custom folder and never touches the public disk.
test('media uploader honors the configured disk and path', function () {
    config([
        'aura.media.disk' => 'custom-disk',
        'aura.media.path' => 'uploads',
    ]);

    Storage::fake('custom-disk');
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('custom.png');

    Livewire::test(MediaUploader::class)
        ->set('media', [$file])
        ->assertHasNoErrors();

    $attachment = Attachment::first();

    expect($attachment)->not->toBeNull();
    expect($attachment->url)->toStartWith('uploads/');

    Storage::disk('custom-disk')->assertExists($attachment->url);
    Storage::disk('public')->assertMissing($attachment->url);
});

// URL generation defers to the configured disk's public URL for non-public
// disks instead of assuming the `public/storage` symlink.
test('attachment path reflects the configured disk public url', function () {
    config([
        'aura.media.disk' => 'custom-disk',
        'aura.media.path' => 'uploads',
        'filesystems.disks.custom-disk' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/custom-disk'),
            'url' => 'http://localhost/media-cdn',
        ],
    ]);

    $attachment = Attachment::create([
        'url' => 'uploads/example.jpg',
        'mime_type' => 'application/pdf',
    ]);

    expect($attachment->path())->toBe('http://localhost/media-cdn/uploads/example.jpg');
});

// Default disk keeps the exact legacy asset() URL so existing installs are
// unaffected.
test('attachment path keeps the public storage url on the default disk', function () {
    $attachment = Attachment::create([
        'url' => 'media/example.jpg',
        'mime_type' => 'application/pdf',
    ]);

    expect($attachment->path())->toBe(asset('storage/media/example.jpg'));
});

// Serving / thumbnailing must read from the configured disk too.
test('the image route serves and stores a thumbnail on the configured disk', function () {
    config([
        'aura.media.disk' => 'custom-disk',
        'aura.media.path' => 'uploads',
        'aura.media.restrict_to_dimensions' => true,
        'aura.media.dimensions' => [['width' => 200]],
    ]);

    Storage::fake('custom-disk');

    $image = imagecreatetruecolor(320, 240);
    ob_start();
    imagejpeg($image);
    $bytes = ob_get_clean();
    Storage::disk('custom-disk')->put('uploads/route-test.jpg', $bytes);

    Attachment::create([
        'url' => 'uploads/route-test.jpg',
        'name' => 'route-test.jpg',
        'title' => 'route-test.jpg',
        'size' => strlen($bytes),
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->get('/admin/img/uploads/route-test.jpg?width=200');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('image/jpeg');

    // The generated thumbnail lives on the custom disk, not the public disk.
    Storage::disk('custom-disk')->assertExists('thumbnails/uploads/200_auto_route-test.jpg');
});
