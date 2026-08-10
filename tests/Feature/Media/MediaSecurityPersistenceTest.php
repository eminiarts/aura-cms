<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\Media\InvalidMediaOwnerToken;
use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaSelectionMutation;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Tests\Support\FaultInjectingMediaEnvironment;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config()->set('aura.media.security.selection_ttl', 15);
    config()->set('aura.media.security.owner_token_ttl', 120);
    $this->actingAs($this->actor = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
    $this->media = FaultInjectingMediaEnvironment::install(app());
});

function issueFaultTestOwnerToken($test): string
{
    return $test->media->owners->issue(
        ownerComponentId: 'fault-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $test->actor,
    );
}

function faultTestOwnerIndexKey($test): string
{
    return 'aura:media-owner:v1:index:'.hash('sha256', json_encode([
        'fault-owner',
        null,
        Post::class,
        null,
        'create',
        'image',
        Image::class,
        (string) $test->actor->getAuthIdentifier(),
        config('aura.teams') ? (string) $test->actor->current_team_id : null,
    ], JSON_THROW_ON_ERROR));
}

test('owner issuance rejects a false durable context write', function () {
    $this->media->store->failNext('put', 'aura:media-owner:v1:token:');

    expect(fn () => issueFaultTestOwnerToken($this))
        ->toThrow(InvalidMediaOwnerToken::class);
});

test('owner issuance rolls back its context when index publication fails', function (bool $throws) {
    $this->media->store->failNext('put', 'aura:media-owner:v1:index:', $throws);

    expect(fn () => issueFaultTestOwnerToken($this))
        ->toThrow(InvalidMediaOwnerToken::class)
        ->and(issueFaultTestOwnerToken($this))->toBeString();
})->with([
    'false return' => false,
    'exception' => true,
]);

test('owner issuance does not replace a reusable token during a transient context read failure', function () {
    $ownerToken = issueFaultTestOwnerToken($this);
    $this->media->store->failNext('get', 'aura:media-owner:v1:token:', true);

    expect(fn () => issueFaultTestOwnerToken($this))
        ->toThrow(InvalidMediaOwnerToken::class)
        ->and(issueFaultTestOwnerToken($this))->toBe($ownerToken);
});

test('owner issuance fails closed when its reusable-token index is malformed', function () {
    $this->media->store->put(faultTestOwnerIndexKey($this), ['malformed'], 120);

    expect(fn () => issueFaultTestOwnerToken($this))
        ->toThrow(InvalidMediaOwnerToken::class);
});

test('selection begin rejects failed scope and owner index publication', function (string $key) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $this->media->store->failNext('put', $key);

    expect(fn () => $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class);
})->with([
    'scope pointer' => 'aura:media-selection:v1:scope:',
    'owner index' => 'aura:media-selection:v1:owner:',
]);

test('selection begin rolls back its owner publication when record creation fails', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $this->media->store->failNext('add', 'aura:media-selection:v1:request:', $throws);

    expect(fn () => $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class)
        ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeFalse();
})->with([
    'false return' => false,
    'exception' => true,
]);

test('failed final settlement rolls back and emits no post commit effect', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $request = $this->media->selections->begin($ownerToken, 'fault-manager', ['9'], $this->actor);
    $value = [];
    $effects = 0;

    expect(fn () => $this->media->selections->processForOwner(
        $request->token,
        $ownerToken,
        'fault-owner',
        'image',
        ['9'],
        $this->actor,
        function () use (&$value, &$effects, $throws): MediaSelectionMutation {
            return new MediaSelectionMutation(
                apply: function () use (&$value, $throws): void {
                    $value = ['9'];
                    $this->media->store->failNext(
                        'put',
                        'aura:media-selection:v1:request:',
                        $throws,
                    );
                },
                rollback: function () use (&$value): void {
                    $value = [];
                },
                afterCommit: function () use (&$effects): void {
                    $effects++;
                },
            );
        },
    ))->toThrow(InvalidMediaSelectionRequest::class);

    expect($value)->toBe([])
        ->and($effects)->toBe(0);

    Carbon::setTestNow(now()->addSeconds(16));

    try {
        expect($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeTrue()
            ->and($this->media->selections->expireForManager(
                $request->token,
                $ownerToken,
                'fault-manager',
                $this->actor,
            )->state)->toBe('expired')
            ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
})->with([
    'false return' => false,
    'exception' => true,
]);

test('failed timeout settlement remains locked and can be retried durably', function () {
    config()->set('aura.media.security.selection_ttl', 1);
    $ownerToken = issueFaultTestOwnerToken($this);
    $request = $this->media->selections->begin($ownerToken, 'fault-manager', ['9'], $this->actor);
    Carbon::setTestNow(now()->addSecond());
    $this->media->store->failNext('put', 'aura:media-selection:v1:request:');

    try {
        expect(fn () => $this->media->selections->expireForManager(
            $request->token,
            $ownerToken,
            'fault-manager',
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class)
            ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeTrue()
            ->and($this->media->selections->expireForManager(
                $request->token,
                $ownerToken,
                'fault-manager',
                $this->actor,
            )->state)->toBe('expired')
            ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
});

test('failed selection lock release restores the active fence and rolls back application', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $request = $this->media->selections->begin($ownerToken, 'fault-manager', ['9'], $this->actor);
    $value = [];
    $effects = 0;
    $this->media->store->failNext('release', 'aura:media-selection:v1:owner:', $throws);

    expect(fn () => $this->media->selections->processForOwner(
        $request->token,
        $ownerToken,
        'fault-owner',
        'image',
        ['9'],
        $this->actor,
        fn (): MediaSelectionMutation => new MediaSelectionMutation(
            apply: function () use (&$value): void {
                $value = ['9'];
            },
            rollback: function () use (&$value): void {
                $value = [];
            },
            afterCommit: function () use (&$effects): void {
                $effects++;
            },
        ),
    ))->toThrow(InvalidMediaSelectionRequest::class);

    expect($value)->toBe([])
        ->and($effects)->toBe(0)
        ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeTrue();

    Carbon::setTestNow(now()->addSeconds(16));

    try {
        expect($this->media->selections->expireForManager(
            $request->token,
            $ownerToken,
            'fault-manager',
            $this->actor,
        )->state)->toBe('expired')
            ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeFalse();
    } finally {
        Carbon::setTestNow();
    }
})->with([
    'false return' => false,
    'exception' => true,
]);

test('selection begin release failures disclose the same durable request only to an exact retry', function (string $lockKey, bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $this->media->store->failNext('release', $lockKey, $throws);

    expect(fn () => $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class)
        ->and(fn () => $this->media->selections->begin(
            $ownerToken,
            'fault-manager',
            ['10'],
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class)
        ->and(fn () => $this->media->selections->begin(
            $ownerToken,
            'other-manager',
            ['9'],
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class);

    $recovered = $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    );

    expect($recovered->record->state)->toBe('pending')
        ->and($recovered->record->managerComponentId)->toBe('fault-manager')
        ->and($this->media->selections->processForOwner(
            $recovered->token,
            $ownerToken,
            'fault-owner',
            'image',
            ['9'],
            $this->actor,
            fn (): MediaSelectionMutation => new MediaSelectionMutation(
                apply: static function (): void {},
                rollback: static function (): void {},
            ),
        )->state)->toBe('succeeded')
        ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeFalse();
})->with([
    'scope release false' => ['aura:media-selection:v1:scope:', false],
    'scope release exception' => ['aura:media-selection:v1:scope:', true],
    'owner release false' => ['aura:media-selection:v1:owner:', false],
    'owner release exception' => ['aura:media-selection:v1:owner:', true],
]);

test('selection recovery rejects an active scope missing its owner wide fence', function () {
    $ownerToken = issueFaultTestOwnerToken($this);
    $request = $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    );
    $this->media->store->put(
        'aura:media-selection:v1:owner:'.hash('sha256', $ownerToken),
        [],
        75,
    );

    expect(fn () => $this->media->selections->begin(
        $ownerToken,
        'fault-manager',
        ['9'],
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class)
        ->and($this->media->selections->forManager(
            $request->token,
            $ownerToken,
            'fault-manager',
            $this->actor,
        )->state)->toBe('pending');
});

test('failed timeout lock release restores the active fence for retry', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $request = $this->media->selections->begin($ownerToken, 'fault-manager', ['9'], $this->actor);
    Carbon::setTestNow(now()->addSeconds(16));

    try {
        $this->media->store->failNext('release', 'aura:media-selection:v1:lock:', $throws);

        expect(fn () => $this->media->selections->expireForManager(
            $request->token,
            $ownerToken,
            'fault-manager',
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class)
            ->and($this->media->selections->hasActiveRequestForOwner($ownerToken, $this->actor))->toBeTrue()
            ->and($this->media->selections->expireForManager(
                $request->token,
                $ownerToken,
                'fault-manager',
                $this->actor,
            )->state)->toBe('expired');
    } finally {
        Carbon::setTestNow();
    }
})->with([
    'false return' => false,
    'exception' => true,
]);

test('details consumption fails closed until compare and delete succeeds', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $token = $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    );
    $this->media->store->failNext('forget', 'aura:media-details:v1:', $throws);

    expect(fn () => $this->media->details->consume(
        $token,
        $ownerToken,
        'details-component',
        'image',
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class);

    expect($this->media->details->consume(
        $token,
        $ownerToken,
        'details-component',
        'image',
        $this->actor,
    )['attachment_id'])->toBe('9')
        ->and(fn () => $this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        ))->toThrow(InvalidMediaOwnerContext::class);
})->with([
    'false return' => false,
    'exception' => true,
]);

test('details lock release failures preserve the single use snapshot for retry', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $token = $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    );
    $this->media->store->failNext('release', 'aura:media-details:v1:', $throws);

    expect(fn () => $this->media->details->consume(
        $token,
        $ownerToken,
        'details-component',
        'image',
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class)
        ->and($this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        )['attachment_id'])->toBe('9')
        ->and(fn () => $this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        ))->toThrow(InvalidMediaOwnerContext::class);
})->with([
    'false return' => false,
    'exception' => true,
]);

test('details replay staging failures preserve a single use retry', function (string $operation, bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $token = $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    );
    $this->media->store->failNext($operation, ':recovery', $throws);

    expect(fn () => $this->media->details->consume(
        $token,
        $ownerToken,
        'details-component',
        'image',
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class)
        ->and($this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        )['attachment_id'])->toBe('9')
        ->and(fn () => $this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        ))->toThrow(InvalidMediaOwnerContext::class);
})->with([
    'staging add false' => ['add', false],
    'staging add exception' => ['add', true],
    'final delete false' => ['forget', false],
    'final delete exception' => ['forget', true],
]);

test('concurrent details consumers reveal a snapshot only once across processes', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the concurrent details proof.');
    }

    $ownerToken = issueFaultTestOwnerToken($this);
    $token = $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    );
    $processId = pcntl_fork();

    if ($processId === 0) {
        try {
            $this->media->details->consume(
                $token,
                $ownerToken,
                'details-component',
                'image',
                $this->actor,
            );
            exit(0);
        } catch (InvalidMediaOwnerContext) {
            exit(10);
        } catch (Throwable) {
            exit(20);
        }
    }

    expect($processId)->toBeGreaterThan(0);
    $parentSucceeded = true;

    try {
        $this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        );
    } catch (InvalidMediaOwnerContext) {
        $parentSucceeded = false;
    }

    pcntl_waitpid($processId, $status);
    $childExit = pcntl_wexitstatus($status);

    expect(pcntl_wifexited($status))->toBeTrue()
        ->and($childExit)->toBeIn([0, 10])
        ->and((int) $parentSucceeded + (int) ($childExit === 0))->toBe(1)
        ->and(fn () => $this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        ))->toThrow(InvalidMediaOwnerContext::class);
});

test('details issuance rejects failed atomic creation', function (bool $throws) {
    $ownerToken = issueFaultTestOwnerToken($this);
    $this->media->store->failNext('add', 'aura:media-details:v1:', $throws);

    expect(fn () => $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class);
})->with([
    'false return' => false,
    'exception' => true,
]);

test('broker lock creation failures are normalized and cannot acknowledge success', function (string $broker, bool $throws) {
    if ($broker === 'owner') {
        $this->media->store->failNext('lock', 'aura:media-owner:v1:index:', $throws);

        expect(fn () => issueFaultTestOwnerToken($this))->toThrow(InvalidMediaOwnerToken::class);

        return;
    }

    $ownerToken = issueFaultTestOwnerToken($this);

    if ($broker === 'selection') {
        $this->media->store->failNext('lock', 'aura:media-selection:v1:scope:', $throws);

        expect(fn () => $this->media->selections->begin(
            $ownerToken,
            'fault-manager',
            ['9'],
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class);

        return;
    }

    $token = $this->media->details->issue(
        $ownerToken,
        'details-component',
        'image',
        9,
        [9],
        [],
        $this->actor,
    );
    $this->media->store->failNext('lock', 'aura:media-details:v1:', $throws);

    expect(fn () => $this->media->details->consume(
        $token,
        $ownerToken,
        'details-component',
        'image',
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class)
        ->and($this->media->details->consume(
            $token,
            $ownerToken,
            'details-component',
            'image',
            $this->actor,
        )['attachment_id'])->toBe('9');
})->with([
    'owner false' => ['owner', false],
    'owner exception' => ['owner', true],
    'selection false' => ['selection', false],
    'selection exception' => ['selection', true],
    'details false' => ['details', false],
    'details exception' => ['details', true],
]);
