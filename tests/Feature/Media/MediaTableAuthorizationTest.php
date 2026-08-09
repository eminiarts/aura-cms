<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\MediaTable;
use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

class Core20AttachmentPolicy
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
        ->assertDontSee('hidden.jpg');

    expect($hidden->exists)->toBeTrue();
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
