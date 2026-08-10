<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSecurityStore;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionMutation;
use Aura\Base\Livewire\Media\MediaSelectionRejected;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Livewire\Modals;
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

function core20ManagerSelectionRecordKey(string $requestToken): string
{
    return 'aura:media-selection:v1:request:'.hash('sha256', $requestToken);
}

function core20ManagerSelectionScopeKey(string $ownerToken, string $managerComponentId): string
{
    return 'aura:media-selection:v1:scope:'.hash('sha256', $ownerToken).':'.hash('sha256', $managerComponentId);
}

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
        mutation: fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
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

test('a hydrated manager cannot close from a cache forged success with a live claim', function () {
    $requestToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return true;
        });
    $key = core20ManagerSelectionRecordKey($requestToken);
    $record = app(MediaSelectionBroker::class)->forManager(
        $requestToken,
        $this->ownerToken,
        $manager->instance()->getId(),
        $this->actor,
    )->toArray();
    $record['state'] = 'succeeded';
    $record['generation'] = 2;
    $record['claim_id'] = str_repeat('a', 64);
    $record['claimed_at'] = $record['issued_at'];
    $record['completed_at'] = $record['issued_at'];
    app(MediaSecurityStore::class)->cache->put($key, $record, 60);

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

test('a hydrated manager cannot close from a coherent cache forged success', function () {
    $requestToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return true;
        });
    $key = core20ManagerSelectionRecordKey($requestToken);
    $record = app(MediaSelectionBroker::class)->forManager(
        $requestToken,
        $this->ownerToken,
        $manager->instance()->getId(),
        $this->actor,
    )->toArray();
    $record['state'] = 'succeeded';
    $record['generation'] = 2;
    $record['claimed_at'] = $record['issued_at'];
    $record['completed_at'] = $record['issued_at'];
    app(MediaSecurityStore::class)->cache->put($key, $record, 60);

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

test('a hydrated manager cannot manufacture timeout from malformed cached timestamps', function () {
    $requestToken = null;
    $manager = Livewire::test(MediaManager::class, $this->arguments)
        ->call('requestMediaSelection', [(string) $this->attachment->getKey()])
        ->assertDispatched('aura-media-selection-requested', function (string $event, array $payload) use (&$requestToken): bool {
            $requestToken = $payload['requestToken'];

            return true;
        });
    $key = core20ManagerSelectionRecordKey($requestToken);
    $record = app(MediaSelectionBroker::class)->forManager(
        $requestToken,
        $this->ownerToken,
        $manager->instance()->getId(),
        $this->actor,
    )->toArray();
    $record['deadline'] = $record['issued_at'];
    app(MediaSecurityStore::class)->cache->put($key, $record, 60);

    Carbon::setTestNow(now()->addSeconds(16));

    $manager->call('expireMediaSelection', $requestToken)
        ->assertSet('pending', true)
        ->assertSet('selectionError', null)
        ->assertNotDispatched('closeModal');

    Carbon::setTestNow();
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
        fn (): MediaSelectionMutation => throw new MediaSelectionRejected('selection_rejected'),
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
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
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

test('global modal close events cannot bypass a pending media dismissal lock', function () {
    $request = app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );
    $modals = new Modals;
    $modals->modals = [
        'picker' => [
            'name' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'arguments' => ['ownerToken' => $this->ownerToken],
        ],
    ];

    $modals->closeModal('picker');

    expect($modals->modals)->toHaveKey('picker');

    app(MediaSelectionBroker::class)->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        [(string) $this->attachment->getKey()],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
    );
    $modals->closeModal('picker');

    expect($modals->modals)->not->toHaveKey('picker');
});

test('global modal close fails closed when a terminal record is detached from its scope fence', function () {
    $request = app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );
    app(MediaSelectionBroker::class)->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        [(string) $this->attachment->getKey()],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
    );
    app(MediaSecurityStore::class)->cache->put(
        core20ManagerSelectionScopeKey($this->ownerToken, 'manager-component'),
        str_repeat('a', 43),
        60,
    );
    $modals = new Modals;
    $modals->modals = [
        'picker' => [
            'name' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'arguments' => ['ownerToken' => $this->ownerToken],
        ],
    ];

    $modals->closeModal('picker');

    expect($modals->modals)->toHaveKey('picker');
});

test('deadline crossing cannot close the modal until timeout settlement is durable', function () {
    config()->set('aura.media.security.selection_ttl', 1);
    $request = app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );
    $modals = new Modals;
    $modals->modals = [
        'picker' => [
            'name' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'arguments' => ['ownerToken' => $this->ownerToken],
        ],
    ];
    Carbon::setTestNow(now()->addSecond());

    try {
        $modals->closeModal('picker');

        expect($modals->modals)->toHaveKey('picker')
            ->and(app(MediaSelectionBroker::class)->expireForManager(
                $request->token,
                $this->ownerToken,
                'manager-component',
                $this->actor,
            )->state)->toBe('expired');

        $modals->closeModal('picker');

        expect($modals->modals)->not->toHaveKey('picker');
    } finally {
        Carbon::setTestNow();
    }
});

test('a close racing the exact application deadline waits for rollback and settlement', function () {
    config()->set('aura.media.security.selection_ttl', 1);
    $request = app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );
    $modals = new Modals;
    $modals->modals = [
        'picker' => [
            'name' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'arguments' => ['ownerToken' => $this->ownerToken],
        ],
    ];
    $value = [];
    $effects = 0;

    try {
        $record = app(MediaSelectionBroker::class)->processForOwner(
            $request->token,
            $this->ownerToken,
            'owner-component',
            'image',
            [(string) $this->attachment->getKey()],
            $this->actor,
            function () use (&$value, &$effects, $modals): MediaSelectionMutation {
                return new MediaSelectionMutation(
                    apply: function () use (&$value, $modals): void {
                        $value = [(string) $this->attachment->getKey()];
                        Carbon::setTestNow(now()->addSecond());
                        $modals->closeModal('picker');
                    },
                    rollback: function () use (&$value): void {
                        $value = [];
                    },
                    afterCommit: function () use (&$effects): void {
                        $effects++;
                    },
                );
            },
        );

        expect($record->state)->toBe('expired')
            ->and($value)->toBe([])
            ->and($effects)->toBe(0)
            ->and($modals->modals)->toHaveKey('picker');

        $modals->closeModal('picker');

        expect($modals->modals)->not->toHaveKey('picker');
    } finally {
        Carbon::setTestNow();
    }
});

test('a cross process global close cannot pass timeout settlement before apply rollback', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the concurrent modal proof.');
    }

    config()->set('aura.media.security.selection_ttl', 1);
    $request = app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );
    $barrier = sys_get_temp_dir().'/aura-core20-applying-'.bin2hex(random_bytes(8));
    $release = $barrier.'-release';
    $applied = $barrier.'-applied';
    $rolledBack = $barrier.'-rolled-back';
    $processId = pcntl_fork();

    if ($processId === 0) {
        try {
            app(MediaSelectionBroker::class)->processForOwner(
                $request->token,
                $this->ownerToken,
                'owner-component',
                'image',
                [(string) $this->attachment->getKey()],
                $this->actor,
                fn (): MediaSelectionMutation => new MediaSelectionMutation(
                    apply: static function () use ($barrier, $release, $applied): void {
                        file_put_contents($applied, 'applied');
                        file_put_contents($barrier, 'applying');
                        $deadline = microtime(true) + 3;

                        while (! is_file($release) && microtime(true) < $deadline) {
                            usleep(10_000);
                        }
                    },
                    rollback: static function () use ($applied, $rolledBack): void {
                        @unlink($applied);
                        file_put_contents($rolledBack, 'rolled-back');
                    },
                ),
            );
            exit(0);
        } catch (Throwable) {
            exit(20);
        }
    }

    expect($processId)->toBeGreaterThan(0);
    $deadline = microtime(true) + 3;

    while (! is_file($barrier) && microtime(true) < $deadline) {
        usleep(10_000);
    }

    $modals = new Modals;
    $modals->modals = [
        'picker' => [
            'name' => ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            'arguments' => ['ownerToken' => $this->ownerToken],
        ],
    ];

    try {
        Carbon::setTestNow(now()->addSecond());
        expect(app(MediaSelectionBroker::class)->expireForManager(
            $request->token,
            $this->ownerToken,
            'manager-component',
            $this->actor,
        )->state)->toBe('expired');

        $modals->closeModal('picker');

        expect($modals->modals)->toHaveKey('picker')
            ->and(is_file($applied))->toBeTrue()
            ->and(is_file($rolledBack))->toBeFalse();

        file_put_contents($release, 'continue');
        pcntl_waitpid($processId, $status);

        expect(is_file($barrier))->toBeTrue()
            ->and(pcntl_wifexited($status))->toBeTrue()
            ->and(pcntl_wexitstatus($status))->toBe(0)
            ->and(is_file($applied))->toBeFalse()
            ->and(is_file($rolledBack))->toBeTrue();

        $modals->closeModal('picker');

        expect($modals->modals)->not->toHaveKey('picker');
    } finally {
        Carbon::setTestNow();
        file_put_contents($release, 'continue');

        if (isset($status) === false) {
            pcntl_waitpid($processId, $status);
        }

        @unlink($barrier);
        @unlink($release);
        @unlink($applied);
        @unlink($rolledBack);
    }
});

test('picker modal security metadata cannot be hydrated away to bypass its dismissal lock', function () {
    app(MediaSelectionBroker::class)->begin(
        $this->ownerToken,
        'manager-component',
        [(string) $this->attachment->getKey()],
        $this->actor,
    );

    $modals = Livewire::test(Modals::class)
        ->call('openModal', ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID, $this->arguments);

    $id = array_key_first($modals->get('modals'));

    $modals->set("modals.{$id}.name", 'harmless-modal')->assertForbidden();

    $freshModals = Livewire::test(Modals::class)
        ->call('openModal', ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID, $this->arguments);
    $freshId = array_key_first($freshModals->get('modals'));

    $freshModals->call('closeModal', $freshId)->assertSet("modals.{$freshId}.active", true);
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

test('manager rejects forged selections above the fresh field maximum', function () {
    app('aura')::registerResources([GalleryPage::class]);
    $second = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'gallery-owner',
        modelClass: GalleryPage::class,
        modelKey: null,
        action: 'create',
        slug: 'hero',
        fieldType: Image::class,
        actor: $this->actor,
    );

    Livewire::test(MediaManager::class, [
        'model' => GalleryPage::class,
        'slug' => 'hero',
        'selected' => [],
        'ownerToken' => $ownerToken,
        'modalAttributes' => $this->arguments['modalAttributes'],
    ])->call('requestMediaSelection', [
        (string) $this->attachment->getKey(),
        (string) $second->getKey(),
    ])->assertForbidden()->assertNotDispatched('aura-media-selection-requested');
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
