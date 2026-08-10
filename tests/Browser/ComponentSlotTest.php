<?php

use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\GalleryPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Pest\Browser\Api\PendingAwaitablePage;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

function browserSlotReplacement(PendingAwaitablePage $page, string $selector): ?string
{
    $encodedSelector = json_encode($selector);

    return $page->script(<<<JS
        (() => {
            const root = document.querySelector({$encodedSelector});
            const snapshot = JSON.parse(root.getAttribute('wire:snapshot'));

            return snapshot.data.componentSlotReplacement ?? null;
        })()
    JS);
}

function captureNextBrowserSlotEvent(PendingAwaitablePage $page, string $eventName): void
{
    $encodedEvent = json_encode($eventName);

    $page->script(<<<JS
        window.__core20CapturedEvents ??= {};
        window.addEventListener({$encodedEvent}, (event) => {
            window.__core20CapturedEvents[{$encodedEvent}] ??= [];
            window.__core20CapturedEvents[{$encodedEvent}].push(event.detail);
            event.stopImmediatePropagation();
        }, { capture: true, once: true });
    JS);
}

function capturedBrowserSlotEvents(PendingAwaitablePage $page, string $eventName): array
{
    $encodedEvent = json_encode($eventName);
    $json = $page->script(
        "JSON.stringify(window.__core20CapturedEvents?.[{$encodedEvent}] ?? [])"
    );

    return json_decode((string) $json, true) ?: [];
}

function browserConsoleLogCount(PendingAwaitablePage $page): int
{
    return (int) $page->script('window.__pestBrowser.consoleLogs.length');
}

function seedComponentSlotAttachment(string $name): Attachment
{
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

test('default slots render in the real shell and media modal', function () {
    $page = visit('/admin');

    $page->assertPresent('[data-aura-global-search]');
    expect(browserSlotReplacement($page, '[data-aura-global-search]'))->toBeNull();

    $page = visit('/admin/gallery-page/create');
    $page->click('[data-media-picker-button="gallery"]')->wait(2)
        ->assertVisible('[data-media-picker-root]')
        ->assertNoJavaScriptErrors();

    expect(browserSlotReplacement($page, '[data-media-picker-root]'))->toBeNull();
})->skip(
    fn () => filter_var(env('AURA_BROWSER_SLOT_REPLACEMENTS', false), FILTER_VALIDATE_BOOL),
    'The replacement-slot pass boots configured winners.',
);

test('all compatibility aliases survive a browser hydration request', function () {
    $page = visit('/__aura-component-slot-aliases');

    $page->assertVisible('[data-component-slot-aliases]')->wait(1);
    $initialConsoleLogs = browserConsoleLogCount($page);

    $json = $page->script(<<<'JS'
        (async () => {
            const wrappers = [...document.querySelectorAll('[data-slot-alias]')];

            for (const wrapper of wrappers) {
                const root = wrapper.querySelector('[wire\\:id]');
                await Livewire.find(root.getAttribute('wire:id')).$refresh();
            }

            return JSON.stringify(wrappers.map((wrapper) => {
                const root = wrapper.querySelector('[wire\\:id]');
                const snapshot = JSON.parse(root.getAttribute('wire:snapshot'));

                return {
                    alias: wrapper.dataset.slotAlias,
                    replacement: snapshot.data.componentSlotReplacement ?? null,
                };
            }));
        })()
    JS);
    $components = json_decode((string) $json, true);

    expect(array_column($components, 'alias'))->toBe([
        'global-colon',
        'global-dot',
        'media-colon',
        'media-dot',
    ]);

    if (filter_var(env('AURA_BROWSER_SLOT_REPLACEMENTS', false), FILTER_VALIDATE_BOOL)) {
        expect(array_column($components, 'replacement'))->toBe([
            'global-search',
            'global-search',
            'media-manager',
            'media-manager',
        ]);
    } else {
        expect(array_column($components, 'replacement'))->toBe([null, null, null, null]);
    }

    expect(browserConsoleLogCount($page))->toBe($initialConsoleLogs);
    $page->assertNoJavaScriptErrors();
});

test('configured replacements render through the real shell and modal transports', function () {
    $page = visit('/admin');

    expect(browserSlotReplacement($page, '[data-aura-global-search]'))->toBe('global-search');

    $page = visit('/admin/gallery-page/create');
    $page->click('[data-media-picker-button="gallery"]')->wait(2)
        ->assertVisible('[data-media-picker-root]')
        ->assertNoJavaScriptErrors();

    expect(browserSlotReplacement($page, '[data-media-picker-root]'))->toBe('media-manager');
})->skip(
    fn () => ! filter_var(env('AURA_BROWSER_SLOT_REPLACEMENTS', false), FILTER_VALIDATE_BOOL),
    'Runs in the replacement-slot browser pass.',
);

test('a timed out selection stays open and retry issues a new request token', function () {
    $attachment = seedComponentSlotAttachment('timeout.jpg');
    $event = 'aura-media-selection-requested';
    $page = visit('/admin/gallery-page/create');

    $page->click('[data-media-picker-button="gallery"]')->wait(2);
    $page->click('[data-attachment-card="'.$attachment->id.'"]')->wait(1);
    captureNextBrowserSlotEvent($page, $event);
    $page->click('[data-picker-select]')->wait(0.5)
        ->assertVisible('[data-media-picker-root]')
        ->assertSee('Applying…')
        ->assertDisabled('[data-picker-select]')
        ->wait(2)
        ->assertVisible('[data-picker-error]')
        ->assertSee('Please try again.')
        ->assertEnabled('[data-picker-select]');

    $firstRequest = capturedBrowserSlotEvents($page, $event)[0];

    captureNextBrowserSlotEvent($page, $event);
    $page->click('[data-picker-select]')->wait(0.5)
        ->assertSee('Applying…')
        ->assertDisabled('[data-picker-select]');

    $requests = capturedBrowserSlotEvents($page, $event);

    expect($requests)->toHaveCount(2)
        ->and($requests[1]['requestToken'])->not->toBe($firstRequest['requestToken']);

    $page->assertNoSmoke();
})->skip(
    fn () => (int) env('AURA_BROWSER_SELECTION_TTL', 0) !== 1,
    'Runs in the short-TTL browser pass.',
);

test('authorization denials stay silent in the global search browser flow', function () {
    GalleryPage::create(['title' => 'Browser authorization secret']);
    $limitedUser = createAdmin();

    $this->actingAs($limitedUser);

    $page = visit('/admin');
    $page->script("window.dispatchEvent(new CustomEvent('search'))");
    $page->wait(0.5)->fill('#docsearch-input', 'Browser authorization secret')->wait(2)
        ->assertDontSee('Browser authorization secret')
        ->assertNoSmoke();
});

test('authorization denial opening media creates no browser error', function () {
    $limitedUser = createAdmin();
    $role = $limitedUser->roles()->firstOrFail();
    $permissions = $role->permissions;
    $permissions['create-gallery-page'] = true;
    $permissions['viewAny-attachment'] = false;
    $role->update(['permissions' => $permissions]);
    Cache::flush();

    $this->actingAs($limitedUser);

    $page = visit('/admin/gallery-page/create');
    $page->click('[data-media-picker-button="gallery"]')->wait(2)
        ->assertNotPresent('[data-media-picker-root]')
        ->assertNoSmoke();
});
