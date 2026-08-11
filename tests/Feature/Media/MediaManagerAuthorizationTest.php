<?php

use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

function managerAttachment(string $name = 'photo.jpg'): Attachment
{
    return Attachment::create([
        'url' => 'media/'.$name,
        'name' => $name,
        'title' => $name,
        'size' => 2048,
        'mime_type' => 'image/jpeg',
    ]);
}

function denyAttachmentViewForAdmin(): User
{
    $user = createAdmin();
    $role = Role::where('slug', 'editor')
        ->where('team_id', $user->current_team_id)
        ->firstOrFail();
    $permissions = $role->permissions;
    $permissions['view-attachment'] = false;
    $permissions['viewAny-attachment'] = true;
    $role->update(['permissions' => $permissions]);
    Cache::flush();

    return $user->refresh();
}

test('mount does not leak all attachment ids via rowIds', function () {
    managerAttachment('a.jpg');
    managerAttachment('b.jpg');
    managerAttachment('c.jpg');

    livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [],
        'modalAttributes' => null,
    ])
        ->assertSet('rowIds', [])
        ->assertOk();
});

test('mount authorizes selected attachment ids', function () {
    $attachment = managerAttachment('selected.jpg');

    livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [(string) $attachment->id],
        'modalAttributes' => null,
    ])
        ->assertSet('selected', [(string) $attachment->id])
        ->assertOk();
});

test('mount rejects unauthorized selected ids', function () {
    $user = denyAttachmentViewForAdmin();
    $this->actingAs($user);

    $attachment = managerAttachment('hidden.jpg');

    // Livewire converts AuthorizationException on mount into a forbidden response.
    livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [(string) $attachment->id],
        'modalAttributes' => null,
    ])->assertForbidden();
});

test('select rejects unauthorized attachment ids', function () {
    $user = denyAttachmentViewForAdmin();
    $this->actingAs($user);

    $attachment = managerAttachment('not-mine.jpg');

    // Mount with empty selection (requires viewAny only).
    livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [],
        'modalAttributes' => null,
    ])
        ->call('select', [(string) $attachment->id])
        ->assertForbidden();
});

test('select rejects foreign team attachment ids', function () {
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

    expect(fn () => livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [],
        'modalAttributes' => null,
    ])->call('select', [(string) $foreign->id]))->toThrow(InvalidMediaOwnerContext::class);
});

test('select accepts authorized attachment ids', function () {
    $attachment = managerAttachment('ok.jpg');

    livewire(MediaManager::class, [
        'model' => User::class,
        'slug' => 'avatar',
        'selected' => [],
        'modalAttributes' => null,
    ])
        ->call('select', [(string) $attachment->id])
        ->assertDispatched('updateField');
});
