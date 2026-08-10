<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSecurityStore;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionMutation;
use Aura\Base\Livewire\Media\MediaSelectionRejected;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config()->set('aura.media.security.selection_ttl', 15);
    config()->set('aura.media.security.owner_token_ttl', 120);
    $this->actor = createSuperAdmin();
    app('aura')::registerResources([Post::class]);
    $this->owners = app(MediaOwnerTokenBroker::class);
    $this->selections = app(MediaSelectionBroker::class);
    $this->ownerToken = $this->owners->issue(
        ownerComponentId: 'owner-component',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
});

function core20SelectionRecordKey(string $requestToken): string
{
    return 'aura:media-selection:v1:request:'.hash('sha256', $requestToken);
}

function core20SelectionOwnerIndexKey(string $ownerToken): string
{
    return 'aura:media-selection:v1:owner:'.hash('sha256', $ownerToken);
}

function core20SelectionScopeKey(string $ownerToken, string $managerComponentId): string
{
    return 'aura:media-selection:v1:scope:'.hash('sha256', $ownerToken).':'.hash('sha256', $managerComponentId);
}

test('selection requests bind both components actor team slug and normalized value digests', function () {
    $request = $this->selections->begin(
        ownerToken: $this->ownerToken,
        managerComponentId: 'manager-component',
        value: ['4', '7'],
        actor: $this->actor,
    );
    $record = $this->selections->forManager(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        managerComponentId: 'manager-component',
        actor: $this->actor,
    );

    expect($request->token)->toMatch('/^[A-Za-z0-9_-]{43}$/')
        ->and($request->digest)->toBe(hash('sha256', $request->token))
        ->and($record->requestDigest)->toBe($request->digest)
        ->and($record->ownerTokenDigest)->toBe(hash('sha256', $this->ownerToken))
        ->and($record->managerComponentId)->toBe('manager-component')
        ->and($record->ownerComponentId)->toBe('owner-component')
        ->and($record->actorId)->toBe((string) $this->actor->getAuthIdentifier())
        ->and($record->teamId)->toBe(config('aura.teams') ? (string) $this->actor->current_team_id : null)
        ->and($record->slug)->toBe('image')
        ->and($record->valueDigest)->toBe(hash('sha256', '["4","7"]'))
        ->and($record->state)->toBe('pending')
        ->and($record->errorCode)->toBeNull()
        ->and($record->claimId)->toBeNull()
        ->and($record->claimedAt)->toBeNull()
        ->and($record->completedAt)->toBeNull();
});

test('manager reads reject a forged succeeded record that retains a live claim', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $key = core20SelectionRecordKey($request->token);
    $record = app(MediaSecurityStore::class)->cache->get($key);
    $record['state'] = 'succeeded';
    $record['claim_id'] = str_repeat('a', 64);
    app(MediaSecurityStore::class)->cache->put($key, $record, 60);

    expect(fn () => $this->selections->forManager(
        $request->token,
        $this->ownerToken,
        'manager',
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class);
});

test('manager reads reject records detached from their owner index and manager scope fences', function (string $key) {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    app(MediaSecurityStore::class)->cache->put($key === 'owner'
        ? core20SelectionOwnerIndexKey($this->ownerToken)
        : core20SelectionScopeKey($this->ownerToken, 'manager'), [], 60);

    expect(fn () => $this->selections->forManager(
        $request->token,
        $this->ownerToken,
        'manager',
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class);
})->with(['owner', 'scope']);

test('manager reads reject malformed state error claim and timestamp tuples', function (string $forgery) {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $key = core20SelectionRecordKey($request->token);
    $record = app(MediaSecurityStore::class)->cache->get($key);
    $forged = match ($forgery) {
        'unknown' => array_replace($record, ['state' => 'complete']),
        'early-expiry' => array_replace($record, [
            'state' => 'expired',
            'error_code' => 'selection_timeout',
            'completed_at' => $record['issued_at'],
        ]),
        'timeout-failure' => array_replace($record, [
            'state' => 'failed',
            'error_code' => 'selection_timeout',
            'claimed_at' => $record['issued_at'],
            'completed_at' => $record['issued_at'],
        ]),
        'reversed-time' => array_replace($record, [
            'state' => 'succeeded',
            'claimed_at' => $record['issued_at'] + 1,
            'completed_at' => $record['issued_at'],
        ]),
    };
    app(MediaSecurityStore::class)->cache->put($key, $forged, 60);

    expect(fn () => $this->selections->forManager(
        $request->token,
        $this->ownerToken,
        'manager',
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class);
})->with([
    'unknown state' => 'unknown',
    'expired before deadline' => 'early-expiry',
    'failed with timeout error' => 'timeout-failure',
    'success completed before claim' => 'reversed-time',
]);

test('owner processing is atomic successful and idempotent', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $applications = 0;
    $effects = 0;

    $first = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'image',
        value: ['9'],
        actor: $this->actor,
        mutation: function () use (&$applications, &$effects): MediaSelectionMutation {
            return new MediaSelectionMutation(
                apply: function () use (&$applications): void {
                    $applications++;
                },
                rollback: function () use (&$applications): void {
                    $applications--;
                },
                afterCommit: function () use (&$effects): void {
                    $effects++;
                },
            );
        },
    );
    $duplicate = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'image',
        value: ['9'],
        actor: $this->actor,
        mutation: function () use (&$applications): MediaSelectionMutation {
            return new MediaSelectionMutation(
                apply: function () use (&$applications): void {
                    $applications++;
                },
                rollback: function () use (&$applications): void {
                    $applications--;
                },
            );
        },
    );

    expect($applications)->toBe(1)
        ->and($effects)->toBe(1)
        ->and($first->state)->toBe('succeeded')
        ->and($first->errorCode)->toBeNull()
        ->and($first->claimId)->toBeNull()
        ->and($first->claimedAt)->toBeInt()
        ->and($first->completedAt)->toBeGreaterThanOrEqual($first->claimedAt)
        ->and($first->completedAt)->toBeLessThan($first->deadline)
        ->and($duplicate->state)->toBe('succeeded');
});

test('only one active request may exist for a manager and owner scope', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);

    expect(fn () => $this->selections->begin($this->ownerToken, 'manager', ['10'], $this->actor))
        ->toThrow(InvalidMediaSelectionRequest::class);

    $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['9'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
    );

    expect($this->selections->begin($this->ownerToken, 'manager', ['10'], $this->actor)->token)
        ->not->toBe($request->token);
});

test('only one active request may exist for an owner across manager components', function () {
    $request = $this->selections->begin($this->ownerToken, 'first-manager', ['9'], $this->actor);

    expect(fn () => $this->selections->begin($this->ownerToken, 'second-manager', ['10'], $this->actor))
        ->toThrow(InvalidMediaSelectionRequest::class);

    $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['9'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
    );

    expect($this->selections->begin($this->ownerToken, 'second-manager', ['10'], $this->actor)->token)
        ->not->toBe($request->token);
});

test('concurrent workers share the active request fence', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the concurrent selection proof.');
    }

    $processId = pcntl_fork();

    if ($processId === 0) {
        try {
            app(MediaSelectionBroker::class)->begin($this->ownerToken, 'concurrent-manager', ['9'], $this->actor);
            exit(0);
        } catch (InvalidMediaSelectionRequest) {
            exit(10);
        } catch (Throwable) {
            exit(20);
        }
    }

    expect($processId)->toBeGreaterThan(0);
    $parentSucceeded = true;

    try {
        $this->selections->begin($this->ownerToken, 'concurrent-manager', ['10'], $this->actor);
    } catch (InvalidMediaSelectionRequest) {
        $parentSucceeded = false;
    }

    pcntl_waitpid($processId, $status);
    $childExit = pcntl_wexitstatus($status);

    expect(pcntl_wifexited($status))->toBeTrue()
        ->and($childExit)->toBeIn([0, 10])
        ->and((int) $parentSucceeded + (int) ($childExit === 0))->toBe(1);
});

test('processing failures settle generically and a retry receives a new token', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $failed = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'image',
        value: ['9'],
        actor: $this->actor,
        mutation: fn (): MediaSelectionMutation => throw new MediaSelectionRejected('selection_rejected'),
    );
    $retry = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);

    expect($failed->state)->toBe('failed')
        ->and($failed->errorCode)->toBe('selection_rejected')
        ->and($failed->claimedAt)->toBeInt()
        ->and($failed->completedAt)->toBeGreaterThanOrEqual($failed->claimedAt)
        ->and($retry->token)->not->toBe($request->token);
});

test('forged owner request value manager and actor never alter the pending record', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $otherActor = User::factory()->create(config('aura.teams') ? ['current_team_id' => $this->actor->current_team_id] : []);

    foreach ([
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken.'x', 'owner-component', 'image', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'other-owner', 'image', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'owner-component', 'other', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'owner-component', 'image', ['10'], $this->actor, fn () => null),
        fn () => $this->selections->forManager($request->token, $this->ownerToken, 'other-manager', $this->actor),
        fn () => $this->selections->forManager($request->token, $this->ownerToken, 'manager', $otherActor),
    ] as $attempt) {
        expect($attempt)->toThrow(InvalidMediaSelectionRequest::class);
    }

    expect($this->selections->forManager($request->token, $this->ownerToken, 'manager', $this->actor)->state)
        ->toBe('pending');
});

test('timeout and success ordering is authoritative and late owner work cannot mutate expired requests', function () {
    $successful = $this->selections->begin($this->ownerToken, 'manager', ['1'], $this->actor);
    $this->selections->processForOwner(
        $successful->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['1'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: static function (): void {},
            rollback: static function (): void {},
        ),
    );

    Carbon::setTestNow(now()->addSeconds(16));

    expect($this->selections->expireForManager($successful->token, $this->ownerToken, 'manager', $this->actor)->state)
        ->toBe('succeeded');

    Carbon::setTestNow();
    $expired = $this->selections->begin($this->ownerToken, 'manager', ['2'], $this->actor);
    Carbon::setTestNow(now()->addSeconds(16));
    $applications = 0;

    expect($this->selections->expireForManager($expired->token, $this->ownerToken, 'manager', $this->actor)->state)
        ->toBe('expired')
        ->and($this->selections->processForOwner(
            $expired->token,
            $this->ownerToken,
            'owner-component',
            'image',
            ['2'],
            $this->actor,
            fn (): MediaSelectionMutation => new MediaSelectionMutation(
                apply: function () use (&$applications): void {
                    $applications++;
                },
                rollback: function () use (&$applications): void {
                    $applications--;
                },
            ),
        )->state)->toBe('expired')
        ->and($applications)->toBe(0);
});

test('a request expiring during preparation never commits and cannot be settled by the stale claim', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['3'], $this->actor);
    $applications = 0;

    $record = $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['3'],
        $this->actor,
        function () use (&$applications): MediaSelectionMutation {
            Carbon::setTestNow(now()->addSeconds(16));

            return new MediaSelectionMutation(
                apply: function () use (&$applications): void {
                    $applications++;
                },
                rollback: function () use (&$applications): void {
                    $applications--;
                },
            );
        },
    );

    expect($record->state)->toBe('expired')
        ->and($record->errorCode)->toBe('selection_timeout')
        ->and($applications)->toBe(0)
        ->and($this->selections->forManager($request->token, $this->ownerToken, 'manager', $this->actor)->state)
        ->toBe('expired');
});

test('a non-reversible prepared callback is rejected before it can mutate state', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['5'], $this->actor);
    $applications = 0;

    $record = $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['5'],
        $this->actor,
        fn (): Closure => function () use (&$applications): void {
            $applications++;
        },
    );

    expect($record->state)->toBe('failed')
        ->and($record->errorCode)->toBe('processing_failed')
        ->and($applications)->toBe(0);
});

test('a request expiring during application is rolled back and never succeeds', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['4'], $this->actor);
    $authoritativeValue = [];

    $record = $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['4'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: function () use (&$authoritativeValue): void {
                $authoritativeValue = ['4'];
                Carbon::setTestNow(now()->addSeconds(16));
            },
            rollback: function () use (&$authoritativeValue): void {
                $authoritativeValue = [];
            },
        ),
    );

    expect($record->state)->toBe('expired')
        ->and($record->errorCode)->toBe('selection_timeout')
        ->and($authoritativeValue)->toBe([]);
});

test('post commit effects run only after a durable successful settlement', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['4'], $this->actor);
    $authoritativeValue = [];
    $effects = 0;

    $expired = $this->selections->processForOwner(
        $request->token,
        $this->ownerToken,
        'owner-component',
        'image',
        ['4'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: function () use (&$authoritativeValue): void {
                $authoritativeValue = ['4'];
                Carbon::setTestNow(now()->addSeconds(16));
            },
            rollback: function () use (&$authoritativeValue): void {
                $authoritativeValue = [];
            },
            afterCommit: function () use (&$effects): void {
                $effects++;
            },
        ),
    );

    expect($expired->state)->toBe('expired')
        ->and($authoritativeValue)->toBe([])
        ->and($effects)->toBe(0);
});
