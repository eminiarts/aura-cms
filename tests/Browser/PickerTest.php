<?php

use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\GalleryPage;
use Illuminate\Support\Facades\Storage;
use Pest\Browser\Api\PendingAwaitablePage;

// GalleryPage is registered (with routes) in BrowserTestCase::setUp.
beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

function seedPickerAttachment(string $name): Attachment
{
    // A real file must exist — the browser loads thumbnails for images.
    Storage::disk('public')
        ->put('media/'.$name, (string) file_get_contents(__DIR__.'/fixtures/photo.jpg'));

    return Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 4096,
        'mime_type' => 'image/jpeg',
    ]);
}

/**
 * The picker's selection is client-side state until "Select" submits it —
 * read it straight from Alpine.
 */
function pickerSelection(PendingAwaitablePage $page): array
{
    $json = $page->script(
        "JSON.stringify(Alpine.\$data(document.querySelector('[data-attachment-table]')).selected)"
    );

    return array_map('intval', json_decode((string) $json, true) ?: []);
}

test('selecting attachments in the picker persists them on the resource', function () {
    $first = seedPickerAttachment('first.jpg');
    $second = seedPickerAttachment('second.jpg');

    $page = visit('/admin/gallery-page/create');

    $page->assertSee('Gallery');

    // Open the Media Picker for the multi-select Gallery field.
    $page->click('[data-media-picker-button="gallery"]')->wait(2);

    $page->assertSee('first.jpg')->assertSee('second.jpg');

    $page->click('[data-attachment-card="'.$first->id.'"]')->wait(1);
    $page->click('[data-attachment-card="'.$second->id.'"]')->wait(1);

    expect(pickerSelection($page))->toBe([$first->id, $second->id]);

    $page->click('Select')->wait(2);

    $page->press('Save')->wait(3);

    $saved = GalleryPage::query()->first();

    expect($saved)->not->toBeNull()
        ->and(collect($saved->gallery)->map(fn ($id) => (int) $id)->sort()->values()->all())
        ->toBe([$first->id, $second->id]);
});

test('a single-select field swaps the selection instead of adding', function () {
    $first = seedPickerAttachment('first.jpg');
    $second = seedPickerAttachment('second.jpg');

    $page = visit('/admin/gallery-page/create');

    // The Hero field is limited to one file; its picker enforces single-select.
    $page->click('[data-media-picker-button="hero"]')->wait(2);

    $page->assertSee('Max: 1');

    $page->click('[data-attachment-card="'.$first->id.'"]')->wait(1);

    expect(pickerSelection($page))->toBe([$first->id]);

    $page->click('[data-attachment-card="'.$second->id.'"]')->wait(1);

    // Still exactly one selected — the second click replaced the first.
    expect(pickerSelection($page))->toBe([$second->id]);

    $page->click('Select')->wait(2);

    $page->press('Save')->wait(3);

    $saved = GalleryPage::query()->first();

    expect($saved)->not->toBeNull()
        ->and(collect($saved->hero)->map(fn ($id) => (int) $id)->values()->all())->toBe([$second->id]);
});

test('uploading inside the picker auto-selects the new attachment', function () {
    $page = visit('/admin/gallery-page/create');

    $page->click('[data-media-picker-button="gallery"]')->wait(2);

    browserAttachFiles($page, '#file-upload', __DIR__.'/fixtures/photo.jpg');

    $page->wait(3);

    $attachment = Attachment::query()->first();

    expect($attachment)->not->toBeNull();

    // Auto-select lands in the MediaManager's entangled `selected` — the
    // scope the Select button submits. The table's Alpine scope (what
    // pickerSelection reads) syncs through a separate selectedRows roundtrip
    // and races on loaded runners, so assert at the outcome level instead:
    // the fresh upload is selected without any manual click; Select + Save
    // proves it flowed into the field.
    $page->click('Select')->wait(2);

    $page->press('Save')->wait(3);

    $saved = GalleryPage::query()->first();

    expect($saved)->not->toBeNull()
        ->and(collect($saved->gallery)->map(fn ($id) => (int) $id)->all())->toBe([$attachment->id]);
});

test('the picker details sidebar edits alt text and offers no delete', function () {
    $attachment = seedPickerAttachment('describe-me.jpg');

    $page = visit('/admin/gallery-page/create');

    $page->click('[data-media-picker-button="gallery"]')->wait(2);

    $page->hover('[data-attachment-card="'.$attachment->id.'"]')->wait(1);

    $opacity = $page->script(
        'getComputedStyle(document.querySelector(\'[data-attachment-info="'.$attachment->id.'"]\')).opacity'
    );

    expect((string) $opacity)->toBe('1');

    $page->click('[data-attachment-info="'.$attachment->id.'"]')->wait(1);

    $page->assertVisible('[data-attachment-details]')
        ->assertNotPresent('[data-details-delete]');

    $page->fill('details-alt-text', 'A described image')->wait(2);

    $page->assertSee('Saved');

    expect(Attachment::query()->find($attachment->id)->alt_text)->toBe('A described image');
});
