<?php

use Aura\Base\Contracts\ScopesMediaVisibility;
use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\MediaTable;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class Core20AttachmentPolicy implements ScopesMediaVisibility
{
    public static bool $delete = true;

    public static ?int $visibleId = null;

    public function create(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return self::$delete;
    }

    public function scopeMediaVisibility(Builder $query, Authenticatable $actor, Resource $resource): Builder
    {
        return self::$visibleId === null
            ? $query
            : $query->whereKey(self::$visibleId);
    }

    public function update(): bool
    {
        return true;
    }

    public function view($user, Attachment $attachment): bool
    {
        return self::$visibleId === null || $attachment->getKey() === self::$visibleId;
    }

    public function viewAny(): bool
    {
        return true;
    }
}

class Core20UnscopedAttachmentPolicy
{
    public function view(): bool
    {
        return true;
    }

    public function viewAny(): bool
    {
        return true;
    }
}

beforeEach(function () {
    Core20AttachmentPolicy::$visibleId = null;
    Core20AttachmentPolicy::$delete = true;
    Gate::policy(Attachment::class, Core20AttachmentPolicy::class);
    $this->actingAs($this->actor = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
    $this->ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'media-table-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $this->field = (new Post)->fieldBySlug('image');
});

test('media table renders only attachments the current actor may view', function () {
    $attributes = config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : [];
    $visible = Attachment::factory()->create($attributes + ['name' => 'visible.jpg', 'title' => 'visible.jpg']);
    $hidden = Attachment::factory()->create($attributes + ['name' => 'hidden.jpg', 'title' => 'hidden.jpg']);

    Core20AttachmentPolicy::$visibleId = $visible->getKey();

    Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ])
        ->assertSee('visible.jpg')
        ->assertDontSee('hidden.jpg')
        ->assertViewHas('rows', fn ($rows): bool => $rows->total() === 1);

    expect($hidden->exists)->toBeTrue();
});

test('media table fails closed when a record policy has no sql visibility scope', function () {
    Gate::policy(Attachment::class, Core20UnscopedAttachmentPolicy::class);
    Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);

    Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ])->assertViewHas('rows', fn ($rows): bool => $rows->total() === 0);
});

test('explicit ids fail closed when the policy has no sql visibility scope', function () {
    Gate::policy(Attachment::class, Core20UnscopedAttachmentPolicy::class);
    $attachment = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);

    expect(fn () => app(MediaAuthorization::class)->authorizeAttachments(
        [(string) $attachment->getKey()],
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class);
});

test('explicit ids reject the whole selection when one id is outside the sql visibility scope', function () {
    $attributes = config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : [];
    $visible = Attachment::factory()->create($attributes);
    $hidden = Attachment::factory()->create($attributes);
    Core20AttachmentPolicy::$visibleId = $visible->getKey();

    expect(app(MediaAuthorization::class)->authorizeAttachments(
        [(string) $visible->getKey()],
        $this->actor,
    ))->toHaveCount(1)
        ->and(fn () => app(MediaAuthorization::class)->authorizeAttachments(
            [(string) $visible->getKey(), (string) $hidden->getKey()],
            $this->actor,
        ))->toThrow(InvalidMediaOwnerContext::class);
});

test('explicit ids are re-scoped after the actor switches teams', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This test exercises team switching.');
    }

    $attachment = Attachment::factory()->create(['team_id' => $this->actor->current_team_id]);
    $otherTeam = foreignTeam();
    $this->actor->forceFill(['current_team_id' => $otherTeam->getKey()])->save();
    Cache::forget("user_{$this->actor->getKey()}_current_team_id");
    $this->actingAs($this->actor->refresh());

    expect(fn () => app(MediaAuthorization::class)->authorizeAttachments(
        [(string) $attachment->getKey()],
        $this->actor,
    ))->toThrow(InvalidMediaOwnerContext::class);
});

test('media table rejects undeclared methods and reauthorizes destructive row actions', function () {
    $attributes = config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : [];
    $attachment = Attachment::factory()->create($attributes);
    Core20AttachmentPolicy::$delete = false;

    $table = Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ]);

    $table->call('action', ['action' => 'forceFill', 'id' => $attachment->getKey()])
        ->assertForbidden();

    Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ])->call('action', ['action' => 'deleteAttachment', 'id' => $attachment->getKey()])
        ->assertForbidden();

    expect($attachment->fresh())->not->toBeNull();
});

test('media table locks and revalidates its security context', function () {
    $table = fn () => Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ]);

    expect(fn () => $table()->set('ownerToken', 'forged'))
        ->toThrow(Exception::class)
        ->and(fn () => $table()->set('model', new Post))
        ->toThrow(Exception::class)
        ->and(fn () => $table()->set('field.slug', 'other'))
        ->toThrow(Exception::class);
});

test('media table accepts upload selection only from its attested owner context', function () {
    $attachment = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $table = Livewire::test(MediaTable::class, [
        'model' => new Attachment,
        'field' => $this->field,
        'ownerToken' => $this->ownerToken,
    ]);

    $table->dispatch(
        'media-uploaded',
        ids: [(string) $attachment->getKey()],
        ownerToken: 'foreign-owner-token',
    )->assertSet('selected', []);

    $table->dispatch(
        'media-uploaded',
        ids: [(string) $attachment->getKey()],
        ownerToken: $this->ownerToken,
    )->assertSet('selected', [(string) $attachment->getKey()]);
});
