<?php

use Aura\Base\Livewire\Media\InvalidMediaOwnerToken;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
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
        actor: $this->actor,
    );

    $sameToken = $this->broker->issue(
        ownerComponentId: 'owner-component-1',
        modelClass: Post::class,
        modelKey: '73',
        action: 'update',
        slug: 'gallery',
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
        ->and($context->actorId)->toBe((string) $this->actor->getAuthIdentifier())
        ->and($context->teamId)->toBe((string) $this->actor->current_team_id)
        ->and($context->nonce)->toHaveLength(64)
        ->and($this->broker->digest($token))->toHaveLength(64);
});

test('owner tokens reject forgery actor changes team changes and expiry', function () {
    $token = $this->broker->issue(
        ownerComponentId: 'owner-component-2',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        actor: $this->actor,
    );

    expect(fn () => $this->broker->resolve(substr_replace($token, 'x', -1), $this->actor))
        ->toThrow(InvalidMediaOwnerToken::class);

    $otherActor = User::factory()->create(['current_team_id' => $this->actor->current_team_id]);

    expect(fn () => $this->broker->resolve($token, $otherActor))
        ->toThrow(InvalidMediaOwnerToken::class);

    $otherTeam = Team::factory()->createQuietly();
    $this->actor->forceFill(['current_team_id' => $otherTeam->getKey()])->saveQuietly();

    expect(fn () => $this->broker->resolve($token, $this->actor->refresh()))
        ->toThrow(InvalidMediaOwnerToken::class);

    $this->actor->forceFill(['current_team_id' => $this->actor->teams()->first()->getKey()])->saveQuietly();
    $freshToken = $this->broker->issue(
        ownerComponentId: 'owner-component-3',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        actor: $this->actor->refresh(),
    );

    Carbon::setTestNow(now()->addSeconds(121));

    expect(fn () => $this->broker->resolve($freshToken, $this->actor->refresh()))
        ->toThrow(InvalidMediaOwnerToken::class);
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
    ]],
    'non resource model' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => stdClass::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => 'image',
    ]],
    'wrong action and key pair' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => Post::class,
        'modelKey' => '1',
        'action' => 'create',
        'slug' => 'image',
    ]],
    'empty slug' => [[
        'ownerComponentId' => 'owner',
        'modelClass' => Post::class,
        'modelKey' => null,
        'action' => 'create',
        'slug' => '',
    ]],
]);
