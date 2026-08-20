<?php

use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Table\Table;
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

test('refreshTable is still dispatched when the batch creates no attachments', function () {
    livewire(MediaUploader::class)
        ->set('media', [])
        ->assertHasNoErrors()
        ->assertDispatched('refreshTable')
        ->assertNotDispatched('media-uploaded');
});

test('successful upload dispatches media-uploaded without a racing refreshTable', function () {
    livewire(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('photo.jpg')])
        ->assertHasNoErrors()
        ->assertDispatched('media-uploaded')
        ->assertNotDispatched('refreshTable');

    expect(Attachment::count())->toBe(1);
});

test('table keeps recent upload ids after media-uploaded so the grid badge survives refresh', function () {
    $first = Attachment::factory()->create();
    $second = Attachment::factory()->create();

    livewire(Table::class, [
        'query' => null,
        'model' => new Attachment,
    ])
        ->dispatch('media-uploaded', ids: [$first->id])
        ->assertSet('recentUploadIds', [(string) $first->id])
        ->dispatch('media-uploaded', ids: [$second->id])
        ->assertSet('recentUploadIds', [(string) $first->id, (string) $second->id])
        ->assertSeeHtml('data-uploaded-badge');
});
