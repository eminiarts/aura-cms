<?php

use Aura\Base\Resources\Attachment;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

// Regression: the image route used to look up attachments via a `url`
// column on posts, but `url` is a meta field — every thumbnail 404ed
// (silently on SQLite, which treats "url" as a string literal).
test('the image route serves a thumbnail for a meta-backed attachment', function () {
    Storage::fake('public');
    $image = imagecreatetruecolor(320, 240);
    ob_start();
    imagejpeg($image);
    $bytes = ob_get_clean();
    Storage::disk('public')->put('media/route-test.jpg', $bytes);

    $attachment = Attachment::create([
        'url' => 'media/route-test.jpg',
        'name' => 'route-test.jpg',
        'title' => 'route-test.jpg',
        'size' => strlen($bytes),
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->get('/admin/img/media/route-test.jpg?width=600');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('image/jpeg');
});

test('the image route 404s for an unknown path', function () {
    $this->get('/admin/img/media/nope.jpg?width=600')->assertNotFound();
});
