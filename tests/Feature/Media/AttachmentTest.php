<?php

use Eminiarts\Aura\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Eminiarts\Aura\Resources\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

it('creates an attachment', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);


    // Access to undeclared static property is not possible.
    // If you need to debug the fieldsAttributeCache, you should instantiate the object and access the property on the instance.



    // ray($attachment->fieldsAttributeCache);

    expect($attachment)->toBeInstanceOf(Attachment::class);
    expect($attachment->name)->toEqual('Test Attachment');
});

it('checks if attachment is an image', function () {
    $attachment = Attachment::create([
        'name' => 'Test Attachment',
        'url' => 'test-url',
        'mime_type' => 'image/jpeg',
        'size' => 12345,
    ]);

    expect($attachment->isImage())->toBeTrue();
});

test('save Attachment Model when defined fields', function () {
    $this->actingAs($user = User::first());

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
            'bajram' => 'bajram',
        ],
    ]);

    $this->assertDatabaseHas('posts', [
        'type' => 'Attachment',
    ]);
});

it('checks mime_type of image and pdf', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertTrue($attachment->isImage());

    $attachment->mime_type = 'application/pdf';
    $attachment->save();

    $this->assertFalse($attachment->fresh()->isImage());
});

it('gets attachment path', function () {
    $attachment = new Attachment([
        'url' => 'test-url.jpg',
    ]);

    $path = asset('storage/'.$attachment->url);
    $this->assertEquals($path, $attachment->path());
});

it('gets readable file size', function () {
    $attachment = Attachment::create([
        'size' => 12345,
    ]);

    $this->assertEquals('12.06 KB', $attachment->getReadableFilesizeAttribute());
});

it('gets readable mime type', function () {
    $attachment = Attachment::create([
        'mime_type' => 'image/jpeg',
    ]);

    $this->assertEquals('JPEG', $attachment->getReadableMimeTypeAttribute());

    $attachment->mime_type = 'application/pdf';
    $this->assertEquals('PDF', $attachment->getReadableMimeTypeAttribute());

    $attachment->mime_type = 'unknown/mime_type';
    $this->assertEquals('unknown/mime_type', $attachment->getReadableMimeTypeAttribute());
});

it('gets thumbnail path', function () {
    $attachment = Attachment::create([
        'thumbnail_url' => 'thumbnails/test-url.jpg',
    ]);

    $thumbnailPath = asset('storage/'.$attachment->thumbnail_url);
    $this->assertEquals($thumbnailPath, $attachment->thumbnail_path());
});
