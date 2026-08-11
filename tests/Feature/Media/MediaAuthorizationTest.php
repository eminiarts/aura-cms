<?php

use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

function mediaAuthAttachment(string $name = 'photo.jpg'): Attachment
{
    return Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);
}

test('authorizeAttachments returns visible attachments for the actor', function () {
    $attachment = mediaAuthAttachment();

    $authorized = app(MediaAuthorization::class)
        ->authorizeAttachments([(string) $attachment->id], $this->user);

    expect($authorized)->toHaveCount(1)
        ->and($authorized->first()->getKey())->toBe($attachment->id);
});

test('authorizeAttachments rejects foreign team attachment ids', function () {
    $otherTeam = Team::factory()->createQuietly(['user_id' => $this->user->id]);

    $foreign = Attachment::withoutGlobalScopes()->create([
        'url' => 'media/foreign.jpg',
        'name' => 'foreign.jpg',
        'title' => 'foreign.jpg',
        'size' => 1024,
        'mime_type' => 'image/jpeg',
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'type' => Attachment::$type,
    ]);

    expect(Attachment::whereKey($foreign->id)->exists())->toBeFalse();

    expect(fn () => app(MediaAuthorization::class)
        ->authorizeAttachments([(string) $foreign->id], $this->user))
        ->toThrow(InvalidMediaOwnerContext::class);
});

test('authorizeAttachments rejects unviewable attachment ids', function () {
    $user = createAdmin();
    $role = Role::where('slug', 'editor')->firstOrFail();
    $permissions = $role->permissions;
    $permissions['view-attachment'] = false;
    $permissions['viewAny-attachment'] = true;
    $role->update(['permissions' => $permissions]);

    $this->actingAs($user->refresh());

    $attachment = mediaAuthAttachment('private.jpg');

    expect(fn () => app(MediaAuthorization::class)
        ->authorizeAttachments([(string) $attachment->id], $user->refresh()))
        ->toThrow(AuthorizationException::class);
});

test('authorizeAttachments empty list requires viewAny', function () {
    $user = createAdmin();
    $role = Role::where('slug', 'editor')->firstOrFail();
    $permissions = $role->permissions;
    $permissions['viewAny-attachment'] = false;
    $permissions['view-attachment'] = false;
    $role->update(['permissions' => $permissions]);

    $this->actingAs($user->refresh());

    expect(fn () => app(MediaAuthorization::class)
        ->authorizeAttachments([], $user->refresh()))
        ->toThrow(AuthorizationException::class);
});

test('authorizeAttachments empty list succeeds with viewAny', function () {
    $user = createAdmin();

    $this->actingAs($user);

    $authorized = app(MediaAuthorization::class)->authorizeAttachments([], $user);

    expect($authorized)->toHaveCount(0);
});

test('deleteSelected requires delete permission', function () {
    $user = createAdmin();
    $role = Role::where('slug', 'editor')->firstOrFail();
    $permissions = $role->permissions;
    $permissions['delete-attachment'] = false;
    $role->update(['permissions' => $permissions]);

    $this->actingAs($user->refresh());

    $attachment = mediaAuthAttachment('keep-me.jpg');

    expect(fn () => (new Attachment)->deleteSelected([$attachment->id]))
        ->toThrow(AuthorizationException::class);

    expect(Attachment::whereKey($attachment->id)->exists())->toBeTrue();
});

test('deleteSelected deletes when the actor may delete', function () {
    $attachment1 = mediaAuthAttachment('one.jpg');
    $attachment2 = mediaAuthAttachment('two.jpg');

    (new Attachment)->deleteSelected([$attachment1->id, $attachment2->id]);

    expect(Attachment::whereKey($attachment1->id)->exists())->toBeFalse()
        ->and(Attachment::whereKey($attachment2->id)->exists())->toBeFalse();
});
