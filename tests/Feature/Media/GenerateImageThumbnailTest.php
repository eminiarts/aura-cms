<?php

use Aura\Base\Jobs\GenerateImageThumbnail;
use Aura\Base\Resources\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
    Queue::fake();
});

it('generates image thumbnail when attachment is created', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    // Assert that the Job was dispatched
    Queue::assertPushed(GenerateImageThumbnail::class);
});

it('does not generate thumbnail for non-image attachments', function () {
    $attachment = Attachment::create([
        'name' => 'Test PDF',
        'url' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 12345,
    ]);

    // Assert that no job was dispatched
    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

it('generates thumbnails with correct dimensions from config', function () {
    // Create a test image
    $file = UploadedFile::fake()->image('test.jpg', 2000, 2000);
    Storage::disk('public')->putFileAs('media', $file, 'test.jpg');

    $attachment = Attachment::create([
        'name' => 'Test Image',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
    ]);

    // Get configured dimensions
    $dimensions = config('aura.media.dimensions');

    // Test each configured dimension
    foreach ($dimensions as $dimension) {
        $response = $this->get(route('aura.image', [
            'path' => $attachment->url,
            'width' => $dimension['width'],
            'height' => $dimension['height'] ?? null,
        ]));

        $response->assertStatus(200);

        // Check if thumbnail was created in storage
        $thumbnailPath = 'thumbnails/media/';
        if (isset($dimension['height'])) {
            $thumbnailPath .= $dimension['width'].'_'.$dimension['height'].'_test.jpg';
        } else {
            $thumbnailPath .= $dimension['width'].'_auto_test.jpg';
        }

        Storage::disk('public')->assertExists($thumbnailPath);
    }
});

it('returns original image when requested dimensions are larger than original', function () {
    // Create a small test image
    $file = UploadedFile::fake()->image('small.jpg', 100, 100);
    Storage::disk('public')->putFileAs('media', $file, 'small.jpg');

    $attachment = Attachment::create([
        'name' => 'Small Image',
        'url' => 'media/small.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
    ]);

    // Request a larger size
    $response = $this->get(route('aura.image', [
        'path' => $attachment->url,
        'width' => 2000,
    ]));

    $response->assertStatus(200);

    // The thumbnail should not be created since we're returning the original
    Storage::disk('public')->assertMissing('thumbnails/media/2000_auto_small.jpg');
});

it('restricts thumbnail generation to configured dimensions when restriction is enabled', function () {
    // Create a test image
    $file = UploadedFile::fake()->image('test.jpg', 2000, 2000);
    Storage::disk('public')->putFileAs('media', $file, 'test.jpg');

    $attachment = Attachment::create([
        'name' => 'Test Image',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
    ]);

    // Request an unconfigured dimension
    $response = $this->get(route('aura.image', [
        'path' => $attachment->url,
        'width' => 999, // This size is not in config
    ]));

    // Should return 404 as the dimension is not allowed
    $response->assertStatus(404);
});
