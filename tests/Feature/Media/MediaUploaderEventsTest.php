<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Storage::fake('public');
});

test('uploading files dispatches media-uploaded with the created ids', function () {
    livewire(MediaUploader::class)
        ->set('media', [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.png'),
        ])
        ->assertHasNoErrors()
        ->assertDispatched('media-uploaded', function (string $event, array $params) {
            return $event === 'media-uploaded'
                && $params['ids'] === Attachment::pluck('id')->all();
        });

    expect(Attachment::count())->toBe(2);
});

test('does not dispatch media-uploaded when every file in the batch is blocked', function () {
    $phpFile = UploadedFile::fake()->create('evil.php', 100, 'application/x-php');

    livewire(MediaUploader::class)
        ->set('media', [$phpFile])
        ->assertHasErrors(['media.*'])
        ->assertNotDispatched('media-uploaded');

    expect(Attachment::count())->toBe(0);
});

test('refreshTable is still dispatched on successful upload', function () {
    livewire(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('photo.jpg')])
        ->assertHasNoErrors()
        ->assertDispatched('refreshTable');

    expect(Attachment::count())->toBe(1);
});
