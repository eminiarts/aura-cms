<?php

use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->actor = createSuperAdmin();
    app('aura')::registerResources([Post::class]);
    $this->tokens = app(MediaOwnerTokenBroker::class);
    $this->authorization = app(MediaAuthorization::class);
});

test('owner authorization reloads and authorizes create and update contexts with a real field', function () {
    $createToken = $this->tokens->issue('create-owner', Post::class, null, 'create', 'image', $this->actor);
    $createOwner = $this->authorization->authorizeOwner($createToken, $this->actor, Post::class, 'image');

    $post = Post::factory()->create(['team_id' => $this->actor->current_team_id]);
    $updateToken = $this->tokens->issue('update-owner', Post::class, (string) $post->getKey(), 'update', 'image', $this->actor);
    $updateOwner = $this->authorization->authorizeOwner($updateToken, $this->actor, Post::class, 'image');

    expect($createOwner->resource)->toBeInstanceOf(Post::class)
        ->and($createOwner->resource->exists)->toBeFalse()
        ->and($createOwner->field['slug'])->toBe('image')
        ->and($updateOwner->resource->is($post))->toBeTrue()
        ->and($updateOwner->resource)->not->toBe($post);
});

test('owner authorization rejects model slug unregistered resource and foreign records', function () {
    $post = Post::factory()->create(['team_id' => $this->actor->current_team_id]);
    $token = $this->tokens->issue('owner', Post::class, (string) $post->getKey(), 'update', 'image', $this->actor);

    expect(fn () => $this->authorization->authorizeOwner($token, $this->actor, Attachment::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class)
        ->and(fn () => $this->authorization->authorizeOwner($token, $this->actor, Post::class, 'missing'))
        ->toThrow(InvalidMediaOwnerContext::class);

    app('aura')::flushState();

    expect(fn () => $this->authorization->authorizeOwner($token, $this->actor, Post::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class);

    app('aura')::registerResources([Post::class]);
    $foreignTeam = Team::factory()->createQuietly();
    $foreign = Post::withoutGlobalScopes()->create([
        'type' => Post::$type,
        'team_id' => $foreignTeam->getKey(),
        'user_id' => $this->actor->getKey(),
    ]);
    $foreignToken = $this->tokens->issue('foreign-owner', Post::class, (string) $foreign->getKey(), 'update', 'image', $this->actor);

    expect(fn () => $this->authorization->authorizeOwner($foreignToken, $this->actor, Post::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class);
});

test('owner and attachment policy denials fail authorization', function () {
    $denied = User::factory()->create(['current_team_id' => $this->actor->current_team_id]);
    $this->actingAs($denied);
    $ownerToken = $this->tokens->issue('owner', Post::class, null, 'create', 'image', $denied);

    expect(fn () => $this->authorization->authorizeOwner($ownerToken, $denied, Post::class, 'image'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->authorization->authorizeAttachments([], $denied))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->authorization->authorizeAttachmentCreate($denied))
        ->toThrow(AuthorizationException::class);
});

test('attachment authorization preserves order and rejects missing duplicate and cross-team ids', function () {
    $first = Attachment::factory()->create(['team_id' => $this->actor->current_team_id]);
    $second = Attachment::factory()->create(['team_id' => $this->actor->current_team_id]);
    $foreign = Attachment::withoutGlobalScopes()->create([
        'type' => Attachment::$type,
        'team_id' => Team::factory()->createQuietly()->getKey(),
        'user_id' => $this->actor->getKey(),
    ]);

    $attachments = $this->authorization->authorizeAttachments(
        [(string) $second->getKey(), (string) $first->getKey()],
        $this->actor,
    );

    expect($attachments->map(fn (Attachment $attachment): string => (string) $attachment->getKey())->all())
        ->toBe([(string) $second->getKey(), (string) $first->getKey()])
        ->and(fn () => $this->authorization->authorizeAttachments([(string) $first->getKey(), (string) $first->getKey()], $this->actor))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->authorization->authorizeAttachments(['999999'], $this->actor))
        ->toThrow(InvalidMediaOwnerContext::class)
        ->and(fn () => $this->authorization->authorizeAttachments([(string) $foreign->getKey()], $this->actor))
        ->toThrow(InvalidMediaOwnerContext::class);
});
