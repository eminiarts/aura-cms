<?php

use Aura\Base\Fields\Image;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Media\InvalidMediaOwnerToken;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config()->set('aura.media.security.owner_token_ttl', 120);
    $this->actor = createSuperAdmin();
    $this->broker = app(MediaOwnerTokenBroker::class);
});

test('owner tokens are opaque reusable for one mounted field and bind the full context', function () {
    $token = $this->broker->issue(
        ownerComponentId: 'owner-component-1',
        modelClass: Post::class,
        modelKey: '73',
        action: 'update',
        slug: 'gallery',
        fieldType: Image::class,
        actor: $this->actor,
    );

    $sameToken = $this->broker->issue(
        ownerComponentId: 'owner-component-1',
        modelClass: Post::class,
        modelKey: '73',
        action: 'update',
        slug: 'gallery',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $context = $this->broker->resolve($token, $this->actor);

    expect($token)->toMatch('/^[A-Za-z0-9_-]+$/')
        ->and($token)->not->toContain('owner-component-1', 'gallery')
        ->and($sameToken)->toBe($token)
        ->and($context->ownerComponentId)->toBe('owner-component-1')
        ->and($context->modelClass)->toBe(Post::class)
        ->and($context->modelKey)->toBe('73')
        ->and($context->action)->toBe('update')
        ->and($context->slug)->toBe('gallery')
        ->and($context->fieldType)->toBe(Image::class)
        ->and($context->actorId)->toBe((string) $this->actor->getAuthIdentifier())
        ->and($context->teamId)->toBe(config('aura.teams') ? (string) $this->actor->current_team_id : null)
        ->and($context->nonce)->toHaveLength(64)
        ->and($this->broker->digest($token))->toHaveLength(64);
});

test('owner tokens reject forgery actor changes and expiry', function () {
    $token = $this->broker->issue(
        ownerComponentId: 'owner-component-2',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );

    expect(fn () => $this->broker->resolve(substr_replace($token, 'x', -1), $this->actor))
        ->toThrow(InvalidMediaOwnerToken::class);

    $otherActor = User::factory()->create(config('aura.teams') ? ['current_team_id' => $this->actor->current_team_id] : []);

    expect(fn () => $this->broker->resolve($token, $otherActor))
        ->toThrow(InvalidMediaOwnerToken::class);

    $freshToken = $this->broker->issue(
        ownerComponentId: 'owner-component-3',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor->refresh(),
    );

    Carbon::setTestNow(now()->addSeconds(121));

    expect(fn () => $this->broker->resolve($freshToken, $this->actor->refresh()))
        ->toThrow(InvalidMediaOwnerToken::class);
});

test('owner tokens reject team changes', function () {
    $token = $this->broker->issue(
        ownerComponentId: 'owner-component-2',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );

    $otherTeam = Team::factory()->createQuietly();
    $this->actor->forceFill(['current_team_id' => $otherTeam->getKey()])->saveQuietly();

    expect(fn () => $this->broker->resolve($token, $this->actor->refresh()))
        ->toThrow(InvalidMediaOwnerToken::class);
})->skip(fn () => ! config('aura.teams'), 'Team-bound owner tokens require teams enabled.');

test('standalone media library tokens bind the configured attachment resource', function () {
    $token = $this->broker->issueLibrary('library-component', $this->actor);
    $context = $this->broker->resolve($token, $this->actor);

    expect($context->ownerComponentId)->toBe('library-component')
        ->and($context->modelClass)->toBe(Attachment::class)
        ->and($context->modelKey)->toBeNull()
        ->and($context->action)->toBe('library')
        ->and($context->slug)->toBe('__library__')
        ->and($context->fieldType)->toBeNull();
});

test('owner token issue rejects malformed context before creating a token', function (array $arguments) {
    expect(fn () => $this->broker->issue(...$arguments, actor: $this->actor))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty owner id' => [[
        'ownerComponentId' => '',
        'modelClass' => Post::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => 'image',
        'fieldType' => Image::class,
    ]],
    'non resource model' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => stdClass::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => 'image',
        'fieldType' => Image::class,
    ]],
    'wrong action and key pair' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => Post::class,
        'modelKey' => '1',
        'action' => 'create',
        'slug' => 'image',
        'fieldType' => Image::class,
    ]],
    'empty slug' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => Post::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => '',
        'fieldType' => Image::class,
    ]],
    'non media field type' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => Post::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => 'title',
        'fieldType' => Text::class,
    ]],
]);

test('media token brokers reject process-local cache stores even when they expose locks', function () {
    config()->set('aura.media.security.cache_store', 'array');

    expect(fn () => app(MediaOwnerTokenBroker::class))
        ->toThrow(InvalidArgumentException::class, 'process-local stores are unsafe');
});

test('owner tokens resolve in a separate php worker through the configured shared store', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the cross-process cache proof.');
    }

    $token = $this->broker->issue(
        ownerComponentId: 'cross-process-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $processId = pcntl_fork();

    if ($processId === 0) {
        try {
            $context = app(MediaOwnerTokenBroker::class)->resolve($token, $this->actor);
            exit($context->ownerComponentId === 'cross-process-owner' ? 0 : 1);
        } catch (Throwable) {
            exit(1);
        }
    }

    expect($processId)->toBeGreaterThan(0);
    pcntl_waitpid($processId, $status);

    expect(pcntl_wifexited($status))->toBeTrue()
        ->and(pcntl_wexitstatus($status))->toBe(0);
});
