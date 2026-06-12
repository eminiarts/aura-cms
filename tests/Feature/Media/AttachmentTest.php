<?php

use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Attachment Creation Tests
test('creates an attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->name)->toBe('Test Attachment');
    expect($attachment->url)->toBe('test-url');
    expect($attachment->mime_type)->toBe('image/jpeg');
    expect((int) $attachment->size)->toBe(12345);
});

test('creates attachment with nested fields array', function () {
    $this->actingAs(User::first());

    Storage::fake('avatars');
    Storage::fake('tmp-for-tests');

    $file = UploadedFile::fake()->image('avatar.png');

    $this->assertDatabaseMissing('posts', [
        'type' => 'Attachment',
    ]);

    $attachment = Attachment::create([
        'name' => $file->getClientOriginalName(),
        'title' => $file->getClientOriginalName(),
        'fields' => [
            'url' => 'avatar.png',
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ],
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Attachment',
    ]);
});

test('attachment uses correct type for database storage', function () {
    $attachment = Attachment::create([
        'name' => 'Test',
        'url' => 'test.jpg',
    ]);

    $this->assertDatabaseHas('posts', [
        'id' => $attachment->id,
        'type' => 'Attachment',
    ]);
});

// Image Detection Tests
test('detects jpeg as image', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('detects png as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/png',
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('detects gif as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/gif',
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('detects webp as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/webp',
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('detects svg as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/svg+xml',
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('pdf is not detected as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'application/pdf',
    ]);

    expect($attachment->isImage())->toBeFalse();
});

test('document is not detected as image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    expect($attachment->isImage())->toBeFalse();
});

test('mime type can be changed from image to non-image', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/jpeg',
    ]);

    expect($attachment->isImage())->toBeTrue();

    $attachment->mime_type = 'application/pdf';
    $attachment->save();

    expect($attachment->fresh()->isImage())->toBeFalse();
});

// Path Methods Tests
test('gets correct attachment path', function () {
    $attachment = new Attachment([
        'url' => 'test-url.jpg',
    ]);

    $expectedPath = asset('storage/'.$attachment->url);
    expect($attachment->path())->toBe($expectedPath);
});

test('gets correct thumbnail path', function () {
    $attachment = Attachment::create([
        'thumbnail_url' => 'thumbnails/test-url.jpg',
    ]);

    $thumbnailPath = asset('storage/'.$attachment->thumbnail_url);
    expect($attachment->thumbnail_path())->toBe($thumbnailPath);
});

// Readable File Size Tests
test('formats bytes as readable size', function () {
    $attachment = Attachment::create(['size' => 500]);
    expect($attachment->getReadableFilesizeAttribute())->toBe('500 B');
});

test('formats kilobytes as readable size', function () {
    $attachment = Attachment::create(['size' => 12345]);
    expect($attachment->getReadableFilesizeAttribute())->toBe('12 KB');
});

test('formats megabytes as readable size', function () {
    $attachment = Attachment::create(['size' => 5242880]); // 5 MB
    expect($attachment->getReadableFilesizeAttribute())->toBe('5 MB');
});

test('formats gigabytes as readable size', function () {
    $attachment = Attachment::create(['size' => 2147483648]); // 2 GB
    expect($attachment->getReadableFilesizeAttribute())->toBe('2 GB');
});

// Readable MIME Type Tests
test('returns JPEG for image/jpeg mime type', function () {
    $attachment = Attachment::create(['mime_type' => 'image/jpeg']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('JPEG');
});

test('returns PNG for image/png mime type', function () {
    $attachment = Attachment::create(['mime_type' => 'image/png']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('PNG');
});

test('returns PDF for application/pdf mime type', function () {
    $attachment = Attachment::create(['mime_type' => 'application/pdf']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('PDF');
});

test('returns DOCX for word document mime type', function () {
    $attachment = Attachment::create([
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('DOCX');
});

test('returns XLSX for excel document mime type', function () {
    $attachment = Attachment::create([
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('XLSX');
});

test('returns MP4 for video/mp4 mime type', function () {
    $attachment = Attachment::create(['mime_type' => 'video/mp4']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('MP4');
});

test('returns MP3 for audio/mpeg mime type', function () {
    $attachment = Attachment::create(['mime_type' => 'audio/mpeg']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('MP3');
});

test('returns raw mime type for unknown types', function () {
    $attachment = Attachment::create(['mime_type' => 'unknown/mime_type']);
    expect($attachment->getReadableMimeTypeAttribute())->toBe('unknown/mime_type');
});

// Table View Tests
test('uses correct default table view', function () {
    $attachment = new Attachment;
    expect($attachment->defaultTableView())->toBe('grid');
});

test('uses correct default per page value', function () {
    $attachment = new Attachment;
    expect($attachment->defaultPerPage())->toBe(25);
});

// Delete Tests
test('can delete attachment', function () {
    $attachment = Attachment::create([
        'name' => 'To Delete',
        'url' => 'delete-me.jpg',
    ]);

    $id = $attachment->id;
    $attachment->delete();

    $this->assertDatabaseMissing('posts', ['id' => $id]);
});

test('can delete multiple attachments', function () {
    $attachment1 = Attachment::create(['name' => 'First', 'url' => '1.jpg']);
    $attachment2 = Attachment::create(['name' => 'Second', 'url' => '2.jpg']);
    $attachment3 = Attachment::create(['name' => 'Third', 'url' => '3.jpg']);

    // deleteSelected is an instance method
    (new Attachment)->deleteSelected([$attachment1->id, $attachment2->id]);

    $this->assertDatabaseMissing('posts', ['id' => $attachment1->id]);
    $this->assertDatabaseMissing('posts', ['id' => $attachment2->id]);
    $this->assertDatabaseHas('posts', ['id' => $attachment3->id]);
});
