<?php

use Aura\Base\Resources\Attachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

function seedLibraryAttachment(string $name, string $mime, ?Carbon $createdAt = null): Attachment
{
    // A real file must exist — the browser loads thumbnails for images.
    $fixture = str_starts_with($mime, 'image/') ? 'photo.jpg' : 'doc.pdf';
    Storage::disk('public')
        ->put('media/'.$name, (string) file_get_contents(__DIR__.'/fixtures/'.$fixture));

    $attachment = Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 4096,
        'mime_type' => $mime,
    ]);

    if ($createdAt) {
        $attachment->timestamps = false;
        $attachment->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        $attachment->timestamps = true;
    }

    return $attachment;
}

test('view switching, search, and the type and date filters narrow the library', function () {
    seedLibraryAttachment('sunset.jpg', 'image/jpeg');
    seedLibraryAttachment('report.pdf', 'application/pdf');
    seedLibraryAttachment('vintage.jpg', 'image/jpeg', Carbon::now()->subMonths(2));

    $page = visit('/admin/attachment');

    $page->assertSee('sunset.jpg')->assertSee('report.pdf');

    // Grid → list → grid (buttons carry sr-only labels).
    $page->click('List View')->wait(1);

    $page->assertSee('sunset.jpg');

    $page->click('Grid View')->wait(1);

    // Search narrows by name.
    $page->type('table-search', 'sunset')->wait(2);

    $page->assertSee('sunset.jpg')->assertDontSee('report.pdf');

    // Type pills (fresh page so previous narrowing is reset).
    $page->navigate('/admin/attachment');
    $page->click('Images')->wait(2);

    $page->assertSee('sunset.jpg')->assertDontSee('report.pdf');

    $page->click('Documents')->wait(2);

    $page->assertSee('report.pdf')->assertDontSee('sunset.jpg');

    $page->click('All')->wait(2);

    // Month dropdown: pick the month of the backdated upload.
    $month = Carbon::now()->subMonths(2)->format('Y-m');

    $page->select('[data-month-filter]', $month)->wait(2);

    $page->assertSee('vintage.jpg')->assertDontSee('sunset.jpg');
});

test('the details panel edits persist and delete removes the attachment', function () {
    $first = seedLibraryAttachment('alpha.jpg', 'image/jpeg');
    $second = seedLibraryAttachment('beta.jpg', 'image/jpeg');

    $page = visit('/admin/attachment');

    $page->click('[data-attachment-card="'.$second->id.'"]')->wait(1);

    $page->assertVisible('[data-attachment-details]')
        ->assertSee('Details')
        ->assertVisible('[data-details-copy-url]');

    // Rename via the panel; auto-save flashes "Saved".
    $page->fill('details-title', 'Renamed beta')->wait(2);

    $page->assertSee('Saved');

    expect(Attachment::query()->find($second->id)->name)->toBe('Renamed beta');

    // The rename survives a full reload.
    $page->navigate('/admin/attachment');
    $page->wait(1);

    $page->assertSee('Renamed beta');

    // Prev/next navigation between the two attachments.
    $page->click('[data-attachment-card="'.$second->id.'"]')->wait(1);

    $page->assertValue('details-title', 'Renamed beta');

    $page->click('[aria-label="Next attachment"]')->wait(1);

    $page->assertValue('details-title', 'alpha.jpg');

    // Delete from the panel.
    $page->script('window.confirm = () => true');
    $page->click('[data-details-delete]')->wait(2);

    expect(Attachment::query()->find($first->id))->toBeNull();
});

test('bulk selection deletes multiple attachments', function () {
    $first = seedLibraryAttachment('one.jpg', 'image/jpeg');
    $second = seedLibraryAttachment('two.jpg', 'image/jpeg');
    $third = seedLibraryAttachment('three.jpg', 'image/jpeg');

    $page = visit('/admin/attachment');

    $page->check('checkbox_grid_'.$first->id)->wait(1);
    $page->check('checkbox_grid_'.$second->id)->wait(1);

    // The selection lives client-side until the next Livewire request —
    // the bulk action itself carries it to the server.
    $page->click('Actions')->wait(1);
    $page->click('Delete')->wait(2);

    expect(Attachment::query()->find($first->id))->toBeNull()
        ->and(Attachment::query()->find($second->id))->toBeNull()
        ->and(Attachment::query()->find($third->id))->not->toBeNull();
});
