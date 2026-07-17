<?php

use Aura\Base\Resources\Attachment;

test('uploading an image through the browser creates an attachment', function () {
    $this->actingAs(createSuperAdmin());

    $page = visit('/admin/attachment');

    browserAttachFiles($page, '#file-upload', __DIR__.'/fixtures/photo.jpg');

    $page->wait(3);

    $page->assertSee('photo');

    expect(Attachment::withoutGlobalScopes()->count())->toBe(1);
});
