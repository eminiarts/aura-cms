<?php

use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
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
    $createToken = $this->tokens->issue('create-owner', Post::class, null, 'create', 'image', Image::class, $this->actor);
    $createOwner = $this->authorization->authorizeOwner($createToken, $this->actor, Post::class, 'image');

    $post = Post::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $updateToken = $this->tokens->issue('update-owner', Post::class, (string) $post->getKey(), 'update', 'image', Image::class, $this->actor);
    $updateOwner = $this->authorization->authorizeOwner($updateToken, $this->actor, Post::class, 'image');

    expect($createOwner->resource)->toBeInstanceOf(Post::class)
        ->and($createOwner->resource->exists)->toBeFalse()
        ->and($createOwner->field['slug'])->toBe('image')
        ->and($updateOwner->resource->is($post))->toBeTrue()
        ->and($updateOwner->resource)->not->toBe($post);
});

test('standalone library owner authorization requires attachment listing access', function () {
    $token = $this->tokens->issueLibrary('library', $this->actor);
    $owner = $this->authorization->authorizeOwner($token, $this->actor, Attachment::class, '__library__');

    expect($owner->context->action)->toBe('library')
        ->and($owner->resource)->toBeInstanceOf(Attachment::class)
        ->and($owner->field)->toBe(['slug' => '__library__']);
});

test('owner authorization rejects model slug field type and unregistered resource', function () {
    $post = Post::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $token = $this->tokens->issue('owner', Post::class, (string) $post->getKey(), 'update', 'image', Image::class, $this->actor);
    $wrongTypeToken = $this->tokens->issue('wrong-type-owner', Post::class, (string) $post->getKey(), 'update', 'image', File::class, $this->actor);

    expect(fn () => $this->authorization->authorizeOwner($token, $this->actor, Attachment::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class)
        ->and(fn () => $this->authorization->authorizeOwner($token, $this->actor, Post::class, 'missing'))
        ->toThrow(InvalidMediaOwnerContext::class)
        ->and(fn () => $this->authorization->authorizeOwner($wrongTypeToken, $this->actor, Post::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class);

    app('aura')::flushState();

    expect(fn () => $this->authorization->authorizeOwner($token, $this->actor, Post::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class);

});

test('owner authorization rejects foreign team records', function () {
    $foreignTeam = Team::factory()->createQuietly();
    $foreign = Post::withoutGlobalScopes()->create([
        'type' => Post::$type,
        'team_id' => $foreignTeam->getKey(),
        'user_id' => $this->actor->getKey(),
    ]);
    $foreignToken = $this->tokens->issue('foreign-owner', Post::class, (string) $foreign->getKey(), 'update', 'image', Image::class, $this->actor);

    expect(fn () => $this->authorization->authorizeOwner($foreignToken, $this->actor, Post::class, 'image'))
        ->toThrow(InvalidMediaOwnerContext::class);
})->skip(fn () => ! config('aura.teams'), 'Cross-team authorization requires teams enabled.');

test('owner and attachment policy denials fail authorization', function () {
    $denied = User::factory()->create(config('aura.teams') ? ['current_team_id' => $this->actor->current_team_id] : []);
    $this->actingAs($denied);
    $ownerToken = $this->tokens->issue('owner', Post::class, null, 'create', 'image', Image::class, $denied);

    expect(fn () => $this->authorization->authorizeOwner($ownerToken, $denied, Post::class, 'image'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->authorization->authorizeAttachments([], $denied))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->authorization->authorizeAttachmentCreate($denied))
        ->toThrow(AuthorizationException::class);
});

test('attachment authorization preserves order and rejects missing and duplicate ids', function () {
    $teamAttributes = config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : [];
    $first = Attachment::factory()->create($teamAttributes);
    $second = Attachment::factory()->create($teamAttributes);

    $attachments = $this->authorization->authorizeAttachments(
        [(string) $second->getKey(), (string) $first->getKey()],
        $this->actor,
    );

    expect($attachments->map(fn (Attachment $attachment): string => (string) $attachment->getKey())->all())
        ->toBe([(string) $second->getKey(), (string) $first->getKey()])
        ->and(fn () => $this->authorization->authorizeAttachments([(string) $first->getKey(), (string) $first->getKey()], $this->actor))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $this->authorization->authorizeAttachments(['999999'], $this->actor))
        ->toThrow(InvalidMediaOwnerContext::class);
});

test('attachment authorization rejects foreign team ids', function () {
    $foreign = Attachment::withoutGlobalScopes()->create([
        'type' => Attachment::$type,
        'team_id' => Team::factory()->createQuietly()->getKey(),
        'user_id' => $this->actor->getKey(),
    ]);

    expect(fn () => $this->authorization->authorizeAttachments([(string) $foreign->getKey()], $this->actor))
        ->toThrow(InvalidMediaOwnerContext::class);
})->skip(fn () => ! config('aura.teams'), 'Cross-team authorization requires teams enabled.');
