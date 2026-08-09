<?php

use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionRejected;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config()->set('aura.media.security.selection_ttl', 15);
    config()->set('aura.media.security.owner_token_ttl', 120);
    $this->actor = createSuperAdmin();
    $this->owners = app(MediaOwnerTokenBroker::class);
    $this->selections = app(MediaSelectionBroker::class);
    $this->ownerToken = $this->owners->issue(
        ownerComponentId: 'owner-component',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'gallery',
        actor: $this->actor,
    );
});

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
        ->and($record->teamId)->toBe((string) $this->actor->current_team_id)
        ->and($record->slug)->toBe('gallery')
        ->and($record->valueDigest)->toBe(hash('sha256', '["4","7"]'))
        ->and($record->state)->toBe('pending')
        ->and($record->errorCode)->toBeNull();
});

test('owner processing is atomic successful and idempotent', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $applications = 0;

    $first = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'gallery',
        value: ['9'],
        actor: $this->actor,
        mutation: function () use (&$applications): void {
            $applications++;
        },
    );
    $duplicate = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'gallery',
        value: ['9'],
        actor: $this->actor,
        mutation: function () use (&$applications): void {
            $applications++;
        },
    );

    expect($applications)->toBe(1)
        ->and($first->state)->toBe('succeeded')
        ->and($first->errorCode)->toBeNull()
        ->and($duplicate->state)->toBe('succeeded');
});

test('processing failures settle generically and a retry receives a new token', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $failed = $this->selections->processForOwner(
        requestToken: $request->token,
        ownerToken: $this->ownerToken,
        ownerComponentId: 'owner-component',
        slug: 'gallery',
        value: ['9'],
        actor: $this->actor,
        mutation: fn () => throw new MediaSelectionRejected('selection_rejected'),
    );
    $retry = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);

    expect($failed->state)->toBe('failed')
        ->and($failed->errorCode)->toBe('selection_rejected')
        ->and($retry->token)->not->toBe($request->token);
});

test('forged owner request value manager and actor never alter the pending record', function () {
    $request = $this->selections->begin($this->ownerToken, 'manager', ['9'], $this->actor);
    $otherActor = User::factory()->create(['current_team_id' => $this->actor->current_team_id]);

    foreach ([
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken.'x', 'owner-component', 'gallery', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'other-owner', 'gallery', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'owner-component', 'other', ['9'], $this->actor, fn () => null),
        fn () => $this->selections->processForOwner($request->token, $this->ownerToken, 'owner-component', 'gallery', ['10'], $this->actor, fn () => null),
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
        'gallery',
        ['1'],
        $this->actor,
        fn () => null,
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
            'gallery',
            ['2'],
            $this->actor,
            function () use (&$applications): void {
                $applications++;
            },
        )->state)->toBe('expired')
        ->and($applications)->toBe(0);
});
