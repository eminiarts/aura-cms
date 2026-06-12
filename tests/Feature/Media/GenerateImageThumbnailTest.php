<?php

use Aura\Base\Jobs\GenerateImageThumbnail;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
    Queue::fake();

    // Set up test dimensions config
    config(['aura.media.dimensions' => [
        ['width' => 200],
        ['width' => 400],
        ['width' => 600],
        ['width' => 800, 'height' => 600],
        ['width' => 1200],
    ]]);
    config(['aura.media.restrict_to_dimensions' => true]);
});

// Job Dispatch Tests
test('dispatches GenerateImageThumbnail job when image attachment is created', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class);
});

test('dispatches job for png image attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test PNG',
        'url' => 'test.png',
        'mime_type' => 'image/png',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class);
});

test('dispatches job for gif image attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test GIF',
        'url' => 'test.gif',
        'mime_type' => 'image/gif',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class);
});

test('dispatches job for webp image attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test WebP',
        'url' => 'test.webp',
        'mime_type' => 'image/webp',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class);
});

test('does not dispatch job for pdf attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test PDF',
        'url' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 12345,
    ]);

    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

test('does not dispatch job for docx attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Doc',
        'url' => 'test.docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'size' => 12345,
    ]);

    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

test('does not dispatch job for video attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Video',
        'url' => 'test.mp4',
        'mime_type' => 'video/mp4',
        'size' => 12345,
    ]);

    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

test('does not dispatch job for audio attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Audio',
        'url' => 'test.mp3',
        'mime_type' => 'audio/mpeg',
        'size' => 12345,
    ]);

    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

test('does not dispatch job for zip attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Zip',
        'url' => 'test.zip',
        'mime_type' => 'application/zip',
        'size' => 12345,
    ]);

    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

// Job Content Tests
test('job receives correct attachment instance', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class, function ($job) use ($attachment) {
        return $job->attachment->id === $attachment->id;
    });
});

test('does not dispatch duplicate jobs for same attachment on create', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    // Only one job should be dispatched from create (using saved event)
    Queue::assertPushed(GenerateImageThumbnail::class, 1);
});

test('dispatches job on attachment update', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    // First job dispatched on create
    Queue::assertPushed(GenerateImageThumbnail::class, 1);

    // Update the attachment
    $attachment->update(['name' => 'Updated Name']);

    // Another job should be dispatched on update (uses saved event)
    Queue::assertPushed(GenerateImageThumbnail::class, 2);
});

// Note: The following tests require Intervention Image v3 Facade compatibility
// which is currently not available in the ThumbnailGenerator service.
// These tests are skipped until the service is updated to use Intervention Image v3.

test('restricts thumbnail generation to configured dimensions', function () {
    $file = UploadedFile::fake()->image('test.jpg', 2000, 2000);
    Storage::disk('public')->putFileAs('media', $file, 'test.jpg');

    $attachment = Attachment::create([
        'name' => 'Test Image',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
    ]);

    // Request an unconfigured dimension - this tests the dimension restriction logic
    // The 404 response is expected when dimension restriction is enabled
    $response = $this->get(route('aura.image', [
        'path' => $attachment->url,
        'width' => 999, // This size is not in config
    ]));

    // Should return 404 as the dimension is not allowed
    $response->assertStatus(404);
});

// Job Properties Tests
test('job has correct queue properties', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'media/test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class, function ($job) {
        // Verify the job has the attachment property set correctly
        return $job->attachment instanceof Attachment
            && $job->attachment->url === 'media/test.jpg';
    });
});

// Edge Cases
test('does not dispatch job for attachment without mime type', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'media/test.unknown',
        'size' => 12345,
        // No mime_type - isImage() will return false for null
    ]);

    // Should not push because isImage() checks for 'image/' prefix
    Queue::assertNotPushed(GenerateImageThumbnail::class);
});

test('dispatches job for svg attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test SVG',
        'url' => 'test.svg',
        'mime_type' => 'image/svg+xml',
        'size' => 1234,
    ]);

    Queue::assertPushed(GenerateImageThumbnail::class);
});
