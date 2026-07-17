<?php

use Aura\Base\Resources\Attachment;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('uploading a single image shows per-file progress and an uploaded indicator', function () {
    $page = visit('/admin/attachment');

    browserAttachFiles($page, '#file-upload', __DIR__.'/fixtures/photo.jpg');

    $page->wait(3);

    // The queue row reports the file as uploaded.
    $page->assertSee('Uploads')
        ->assertSee('photo.jpg')
        ->assertSee('Uploaded');

    // The attachment exists and appears in the grid with the fresh-upload badge.
    expect(Attachment::query()->count())->toBe(1);

    $page->assertVisible('[data-uploaded-badge]');
});

test('uploading mixed file types succeeds for each file', function () {
    $page = visit('/admin/attachment');

    browserAttachFiles($page, '#file-upload', [
        __DIR__.'/fixtures/photo.jpg',
        __DIR__.'/fixtures/graphic.png',
        __DIR__.'/fixtures/doc.pdf',
        __DIR__.'/fixtures/notes.txt',
        __DIR__.'/fixtures/archive.zip',
    ]);

    $page->wait(6);

    expect(Attachment::query()->count())->toBe(5);

    // Grid renders each with its readable type; non-images get icons, images thumbnails.
    $page->assertSee('photo.jpg')
        ->assertSee('graphic.png')
        ->assertSee('doc.pdf')
        ->assertSee('PDF')
        ->assertSee('ZIP');

    $types = Attachment::query()->get()->map(fn ($a) => $a->mime_type)->sort()->values()->all();

    expect($types)->toBe(['application/pdf', 'application/zip', 'image/jpeg', 'image/png', 'text/plain']);
});

test('a blocked file fails with a reason while the rest of the batch succeeds', function () {
    $page = visit('/admin/attachment');

    browserAttachFiles($page, '#file-upload', [
        __DIR__.'/fixtures/evil.php',
        __DIR__.'/fixtures/photo.jpg',
    ]);

    // Short wait: successful rows auto-dismiss after 4s, so assert before that.
    $page->wait(2);

    // The .php file is rejected client-side with a visible failed row…
    $page->assertSee('evil.php')
        ->assertSee('not allowed');

    // …while the image still uploads.
    $page->assertSee('Uploaded');

    $attachments = Attachment::query()->get();

    expect($attachments)->toHaveCount(1)
        ->and($attachments->first()->name)->toBe('photo.jpg');
});
