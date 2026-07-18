<?php

use Aura\Base\Livewire\AttachmentDetails;
use Aura\Base\Resources\Attachment;

use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

function detailsAttachment(string $name = 'photo.jpg', string $mime = 'image/jpeg'): Attachment
{
    return Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 2048,
        'mime_type' => $mime,
    ]);
}

test('opening the panel loads the attachment', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertSet('attachmentId', $attachment->id)
        ->assertSet('title', 'photo.jpg')
        ->assertSee('Details')
        ->assertSee('2 KB');
});

test('editing the title persists to the attachment name', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('title', 'Renamed photo')
        ->assertHasNoErrors()
        ->assertDispatched('attachment-details-saved')
        ->assertDispatched('refreshTable');

    expect(Attachment::find($attachment->id)->name)->toBe('Renamed photo');
});

test('an empty title is rejected and not persisted', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('title', '')
        ->assertHasErrors(['title']);

    expect(Attachment::find($attachment->id)->name)->toBe('photo.jpg');
});

test('editing the alt text persists', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('altText', 'A test image')
        ->assertHasNoErrors()
        ->assertDispatched('attachment-details-saved');

    expect(Attachment::find($attachment->id)->alt_text)->toBe('A test image');
});

test('next and previous navigate the row ids', function () {
    $first = detailsAttachment('first.jpg');
    $second = detailsAttachment('second.jpg');
    $third = detailsAttachment('third.jpg');

    $ids = [$first->id, $second->id, $third->id];

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $second->id, ids: $ids)
        ->assertSet('title', 'second.jpg')
        ->call('next')
        ->assertSet('attachmentId', $third->id)
        ->assertSet('title', 'third.jpg')
        ->call('next')
        ->assertSet('attachmentId', $third->id)
        ->call('previous')
        ->assertSet('attachmentId', $second->id)
        ->call('previous')
        ->assertSet('attachmentId', $first->id)
        ->call('previous')
        ->assertSet('attachmentId', $first->id);
});

test('delete removes the attachment and advances to a sibling', function () {
    $first = detailsAttachment('first.jpg');
    $second = detailsAttachment('second.jpg');

    livewire(AttachmentDetails::class, ['surface' => 'index'])
        ->dispatch('open-attachment-details', id: $first->id, ids: [$first->id, $second->id])
        ->call('deleteAttachment')
        ->assertDispatched('refreshTable')
        ->assertSet('attachmentId', $second->id);

    expect(Attachment::find($first->id))->toBeNull()
        ->and(Attachment::find($second->id))->not->toBeNull();
});

test('delete is refused on the picker surface', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class, ['surface' => 'picker'])
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->call('deleteAttachment');

    expect(Attachment::find($attachment->id))->not->toBeNull();
});

test('the picker surface renders no delete button', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class, ['surface' => 'picker'])
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertDontSeeHtml('data-details-delete');
});

test('closing the panel resets and announces itself', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->call('close')
        ->assertSet('attachmentId', null)
        ->assertDispatched('attachment-details-closed');
});

test('image dimensions are shown when present', function () {
    $attachment = detailsAttachment();
    $attachment->update(['width' => 640, 'height' => 480]);

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertSee('640 × 480 px');
});
