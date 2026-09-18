<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * `aura.media.max_file_size` (kilobytes) is the single source for both the
 * server-side `max:` rule and the client-side hint in uploadPolicy(); the
 * uploader used to hardcode 102400 and ignore the config key entirely.
 */
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
});

test('the configured max file size drives the upload policy', function () {
    config()->set('aura.media.max_file_size', 512);

    expect(Livewire::test(MediaUploader::class)->instance()->uploadPolicy()['max_size_bytes'])
        ->toBe(512 * 1024);
});

test('an upload above the configured max file size is rejected', function () {
    config()->set('aura.media.max_file_size', 100);

    Livewire::test(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->create('big.pdf', 200, 'application/pdf')])
        ->assertHasErrors(['media.*']);

    expect(Attachment::count())->toBe(0);
});

test('an upload below the configured max file size is accepted', function () {
    config()->set('aura.media.max_file_size', 100);

    Livewire::test(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->create('small.pdf', 50, 'application/pdf')])
        ->assertHasNoErrors();

    expect(Attachment::count())->toBe(1);
});
