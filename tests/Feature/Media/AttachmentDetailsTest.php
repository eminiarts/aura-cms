<?php

use Aura\Base\Contracts\ScopesMediaVisibility;
use Aura\Base\Fields\Image;
use Aura\Base\Livewire\AttachmentDetails;
use Aura\Base\Livewire\Media\MediaDetailsBroker;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Role;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

class Core20DetailsScopedAttachmentPolicy implements ScopesMediaVisibility
{
    public static ?int $visibleId = null;

    public function delete(): bool
    {
        return true;
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

    public function view(): bool
    {
        return true;
    }

    public function viewAny(): bool
    {
        return true;
    }
}

class Core20DetailsUnscopedAttachmentPolicy
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
    Core20DetailsScopedAttachmentPolicy::$visibleId = null;
    Gate::policy(Attachment::class, Core20DetailsScopedAttachmentPolicy::class);
    $this->actingAs($this->user = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
    config()->set('aura.resources.core20-details-owner', Post::class);
});

function pickerDetailsArguments($actor): array
{
    return [
        'surface' => 'picker',
        'ownerToken' => app(MediaOwnerTokenBroker::class)->issue(
            ownerComponentId: 'form-owner',
            modelClass: Post::class,
            modelKey: null,
            action: 'create',
            slug: 'image',
            fieldType: Image::class,
            actor: $actor,
        ),
        'correlationComponentId' => 'picker-owner',
        'fieldSlug' => 'image',
    ];
}

function detailsAttachment(string $name = 'photo.jpg', string $mime = 'image/jpeg'): Attachment
{
    return Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 2048,
        'mime_type' => $mime,
    ]);
}

test('opening the panel loads the attachment', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertSet('attachmentId', $attachment->id)
        ->assertSet('title', 'photo.jpg')
        ->assertSee('Details')
        ->assertSee('2 KB');
});

test('picker details consume only a server issued opaque snapshot once', function () {
    $attachment = detailsAttachment('picker.jpg');
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'form-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->user,
    );
    $details = livewire(AttachmentDetails::class, [
        'surface' => 'picker',
        'ownerToken' => $ownerToken,
        'correlationComponentId' => 'picker-owner',
        'fieldSlug' => 'image',
    ]);

    $token = app(MediaDetailsBroker::class)->issue(
        $ownerToken,
        'picker-owner',
        'image',
        $attachment->id,
        [$attachment->id],
        [],
        $this->user,
    );

    $details->dispatch('open-attachment-details', id: 999999, ids: [999999], detailsToken: $token)
        ->assertSet('attachmentId', $attachment->id)
        ->call('close')
        ->dispatch('open-attachment-details', detailsToken: $token)
        ->assertSet('attachmentId', null);
});

test('picker details row snapshot cannot be changed by hydration', function () {
    $attachment = detailsAttachment('locked-rows.jpg');
    $arguments = pickerDetailsArguments($this->user);
    $token = app(MediaDetailsBroker::class)->issue(
        $arguments['ownerToken'],
        'picker-owner',
        'image',
        $attachment->id,
        [$attachment->id],
        [],
        $this->user,
    );
    $details = livewire(AttachmentDetails::class, $arguments)
        ->dispatch('open-attachment-details', detailsToken: $token);

    expect(fn () => $details->set('rowIds', [999999]))->toThrow(Exception::class);
});

test('picker details reject a valid snapshot issued for another picker component', function () {
    $attachment = detailsAttachment('cross-picker.jpg');
    $arguments = pickerDetailsArguments($this->user);
    $token = app(MediaDetailsBroker::class)->issue(
        $arguments['ownerToken'],
        'other-picker',
        'image',
        $attachment->id,
        [$attachment->id],
        [],
        $this->user,
    );

    livewire(AttachmentDetails::class, $arguments)
        ->dispatch('open-attachment-details', detailsToken: $token)
        ->assertSet('attachmentId', null);

    $arguments['correlationComponentId'] = 'other-picker';

    livewire(AttachmentDetails::class, $arguments)
        ->dispatch('open-attachment-details', detailsToken: $token)
        ->assertSet('attachmentId', $attachment->id);
});

test('the attachment id cannot bypass the view policy', function () {
    $user = createAdmin();
    $role = Role::where('slug', 'editor')->firstOrFail();
    $permissions = $role->permissions;
    $permissions['view-attachment'] = false;
    $permissions['viewAny-attachment'] = true;
    $role->update(['permissions' => $permissions]);

    $this->actingAs($user->refresh());
    Gate::policy(Attachment::class, ResourcePolicy::class);

    $attachment = detailsAttachment('private.jpg');

    livewire(AttachmentDetails::class)
        ->call('open', $attachment->id)
        ->assertForbidden();
});

test('the attachment id cannot bypass the sql visibility scope when view is allowed', function () {
    $visible = detailsAttachment('visible.jpg');
    $hidden = detailsAttachment('hidden.jpg');
    Core20DetailsScopedAttachmentPolicy::$visibleId = $visible->getKey();

    livewire(AttachmentDetails::class)
        ->call('open', $hidden->getKey())
        ->assertForbidden();
});

test('a client supplied mount id cannot bypass the sql visibility scope', function () {
    $visible = detailsAttachment('mount-visible.jpg');
    $hidden = detailsAttachment('mount-hidden.jpg');
    Core20DetailsScopedAttachmentPolicy::$visibleId = $visible->getKey();

    livewire(AttachmentDetails::class, ['attachmentId' => $hidden->getKey()])
        ->assertForbidden();
});

test('attachment details fail closed when the attachment policy has no sql visibility contract', function () {
    Gate::policy(Attachment::class, Core20DetailsUnscopedAttachmentPolicy::class);
    $attachment = detailsAttachment('unscoped.jpg');

    livewire(AttachmentDetails::class)
        ->call('open', $attachment->getKey())
        ->assertForbidden();
});

test('a hydrated details panel rechecks sql visibility before another action', function () {
    $attachment = detailsAttachment('hydrated.jpg');
    $replacement = detailsAttachment('replacement.jpg');
    Core20DetailsScopedAttachmentPolicy::$visibleId = $attachment->getKey();
    $details = livewire(AttachmentDetails::class)
        ->call('open', $attachment->getKey())
        ->assertSet('attachmentId', $attachment->getKey());

    Core20DetailsScopedAttachmentPolicy::$visibleId = $replacement->getKey();

    $details->call('next')->assertForbidden();
});

test('a hydrated details panel rechecks sql visibility after a team switch', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This test exercises team switching.');
    }

    $attachment = detailsAttachment('old-team.jpg');
    $details = livewire(AttachmentDetails::class)
        ->call('open', $attachment->getKey())
        ->assertSet('attachmentId', $attachment->getKey());
    $otherTeam = foreignTeam();
    $this->user->forceFill(['current_team_id' => $otherTeam->getKey()])->save();
    Cache::forget("user_{$this->user->getKey()}_current_team_id");
    $this->actingAs($this->user->refresh());

    $details->call('next')->assertForbidden();
});

test('editing the title persists to the attachment name', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('title', 'Renamed photo')
        ->assertHasNoErrors()
        ->assertDispatched('attachment-details-saved')
        ->assertDispatched('refreshTable');

    expect(Attachment::find($attachment->id)->name)->toBe('Renamed photo');
});

test('an empty title is rejected and not persisted', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('title', '')
        ->assertHasErrors(['title']);

    expect(Attachment::find($attachment->id)->name)->toBe('photo.jpg');
});

test('editing the alt text persists', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->set('altText', 'A test image')
        ->assertHasNoErrors()
        ->assertDispatched('attachment-details-saved');

    expect(Attachment::find($attachment->id)->alt_text)->toBe('A test image');
});

test('next and previous navigate the row ids', function () {
    $first = detailsAttachment('first.jpg');
    $second = detailsAttachment('second.jpg');
    $third = detailsAttachment('third.jpg');

    $ids = [$first->id, $second->id, $third->id];

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $second->id, ids: $ids)
        ->assertSet('title', 'second.jpg')
        ->call('next')
        ->assertSet('attachmentId', $third->id)
        ->assertSet('title', 'third.jpg')
        ->call('next')
        ->assertSet('attachmentId', $third->id)
        ->call('previous')
        ->assertSet('attachmentId', $second->id)
        ->call('previous')
        ->assertSet('attachmentId', $first->id)
        ->call('previous')
        ->assertSet('attachmentId', $first->id);
});

test('delete removes the attachment and advances to a sibling', function () {
    $first = detailsAttachment('first.jpg');
    $second = detailsAttachment('second.jpg');

    livewire(AttachmentDetails::class, ['surface' => 'index'])
        ->dispatch('open-attachment-details', id: $first->id, ids: [$first->id, $second->id])
        ->call('deleteAttachment')
        ->assertDispatched('refreshTable')
        ->assertSet('attachmentId', $second->id);

    expect(Attachment::find($first->id))->toBeNull()
        ->and(Attachment::find($second->id))->not->toBeNull();
});

test('delete is refused on the picker surface', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class, pickerDetailsArguments($this->user))
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->call('deleteAttachment');

    expect(Attachment::find($attachment->id))->not->toBeNull();
});

test('the picker surface renders no delete button', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class, pickerDetailsArguments($this->user))
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertDontSeeHtml('data-details-delete');
});

test('closing the panel resets and announces itself', function () {
    $attachment = detailsAttachment();

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->call('close')
        ->assertSet('attachmentId', null)
        ->assertDispatched('attachment-details-closed');
});

test('image dimensions are shown when present', function () {
    $attachment = detailsAttachment();
    $attachment->update(['width' => 640, 'height' => 480]);

    livewire(AttachmentDetails::class)
        ->dispatch('open-attachment-details', id: $attachment->id, ids: [$attachment->id])
        ->assertSee('640 × 480 px');
});
