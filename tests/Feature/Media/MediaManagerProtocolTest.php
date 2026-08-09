<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionRejected;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\GalleryPage;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->actor = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
    $this->attachment = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $this->ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'owner-component',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $this->arguments = [
        'model' => Post::class,
        'slug' => 'image',
        'selected' => [(string) $this->attachment->getKey()],
        'ownerToken' => $this->ownerToken,
        'modalAttributes' => [
            'persistent' => false,
            'modalClasses' => 'max-w-7xl',
            'slideOver' => false,
        ],
    ];
});

test('manager request remains open and exposes a correlated pending effect', function () {
    $requestToken = null;

    Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertSet('pending', true)
        ->assertSet('selectionError', null)
        ->assertNotDispatched('closeModal')
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return $payload['ownerToken'] === $this->ownerToken
                && $payload['slug'] === 'image'
                && $payload['value'] === [(string) $this->attachment->getKey()];
        });

    expect($requestToken)->toBeString()->toHaveLength(43);
});

test('authoritative success closes only on the third manager acknowledgement request', function () {
    $requestToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertNotDispatched('closeModal')
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return true;
        });

    app(MediaSelectionBroker::class)->processForOwner(
        requestToken: $requestToken,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'image',
        value: [(string) $this->attachment->getKey()],
        actor: $this->actor,
        mutation: fn (): Closure => static fn (): null => null,
    );

    $manager->call(
        'acknowledgeMediaSelection',
        $this->ownerToken,
        $requestToken,
        'succeeded',
        null,
    )
        ->assertSet('pending', false)
        ->assertSet('selectionError', null)
        ->assertDispatched('closeModal');

    $manager->call(
        'acknowledgeMediaSelection',
        $this->ownerToken,
        $requestToken,
        'succeeded',
        null,
    )->assertNotDispatched('closeModal');
});

test('forged success cannot close while the broker remains pending', function () {
    $requestToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return true;
        });

    $manager->call(
        'acknowledgeMediaSelection',
        $this->ownerToken,
        $requestToken,
        'succeeded',
        null,
    )
        ->assertSet('pending', true)
        ->assertNotDispatched('closeModal');
});

test('authoritative failure stays open and permits retry with a new request token', function () {
    $firstToken = null;
    $retryToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$firstToken): bool {
            $firstToken = $payload['requestToken'];

            return true;
        });

    app(MediaSelectionBroker::class)->processForOwner(
        $firstToken,
        $this->ownerToken,
        'owner-component',
        'image',
        [(string) $this->attachment->getKey()],
        $this->actor,
        fn (): Closure => throw new MediaSelectionRejected('selection_rejected'),
    );

    $manager->call(
        'acknowledgeMediaSelection',
        $this->ownerToken,
        $firstToken,
        'failed',
        'selection_rejected',
    )
        ->assertSet('pending', false)
        ->assertSet('selectionError', 'selection_rejected')
        ->assertNotDispatched('closeModal')
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$retryToken): bool {
            $retryToken = $payload['requestToken'];

            return true;
        });

    expect($retryToken)->not->toBe($firstToken);
});

test('timeout remains open while an already successful timeout race closes', function () {
    $timeoutToken = null;
    $timedOut = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$timeoutToken): bool {
            $timeoutToken = $payload['requestToken'];

            return true;
        });

    Carbon::setTestNow(now()->addSeconds(16));

    $timedOut->call('expireMediaSelection', $timeoutToken)
        ->assertSet('pending', false)
        ->assertSet('selectionError', 'selection_timeout')
        ->assertNotDispatched('closeModal');

    Carbon::setTestNow();
    $successToken = null;
    $succeeded = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$successToken): bool {
            $successToken = $payload['requestToken'];

            return true;
        });
    app(MediaSelectionBroker::class)->processForOwner(
        $successToken,
        $this->ownerToken,
        'owner-component',
        'image',
        [(string) $this->attachment->getKey()],
        $this->actor,
        fn (): Closure => static fn (): null => null,
    );
    Carbon::setTestNow(now()->addSeconds(16));

    $succeeded->call('expireMediaSelection', $successToken)
        ->assertSet('pending', false)
        ->assertDispatched('closeModal');
});

test('manager rejects guests foreign selected ids and browser mutation of locked context', function () {
    auth()->logout();

    Livewire::test(MediaManager::class, $this->arguments)->assertForbidden();

    $this->actingAs($this->actor);
    $manager = Livewire::test(MediaManager::class, $this->arguments);

    expect(fn () => $manager->set('ownerToken', 'forged'))
        ->toThrow(Exception::class)
        ->and(fn () => $manager->set('model', Attachment::class))
        ->toThrow(Exception::class)
        ->and(fn () => $manager->set('slug', 'other'))
        ->toThrow(Exception::class)
        ->and(fn () => $manager->set('pending', false))
        ->toThrow(Exception::class)
        ->and(fn () => $manager->call('requestMediaSelection', ['999999']))
        ->toThrow(Exception::class);
});

test('manager disables every explicit dismissal while a selection is pending', function () {
    Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertSeeHtml('data-picker-close')
        ->assertSeeHtml('disabled');
});

test('manager accepts upload selection only from its attested owner context', function () {
    app('aura')::registerResources([GalleryPage::class]);
    $uploaded = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'gallery-owner',
        modelClass: GalleryPage::class,
        modelKey: null,
        action: 'create',
        slug: 'hero',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $manager = Livewire::test(MediaManager::class, [
        'model' => GalleryPage::class,
        'slug' => 'hero',
        'selected' => [(string) $this->attachment->getKey()],
        'ownerToken' => $ownerToken,
        'modalAttributes' => $this->arguments['modalAttributes'],
    ]);

    $manager->dispatch(
        'media-uploaded',
        ids: [(string) $uploaded->getKey()],
        ownerToken: 'foreign-owner-token',
    )->assertSet('selected', [(string) $this->attachment->getKey()]);

    $manager->dispatch(
        'media-uploaded',
        ids: [(string) $uploaded->getKey()],
        ownerToken: $ownerToken,
    )
        ->assertSet('field.max_files', 1)
        ->assertSet('selected', [(string) $uploaded->getKey()])
        ->assertDispatched('selectedRows', [(string) $uploaded->getKey()]);
});

test('media manager transport and compatibility aliases mount and hydrate the selected winner', function (string $identifier) {
    Livewire::test($identifier, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertSet('pending', true)
        ->assertNotDispatched('closeModal');
})->with([
    'transport' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
    'blade alias' => 'aura::media-manager',
    'dot alias' => 'aura.base.livewire.media-manager',
]);
