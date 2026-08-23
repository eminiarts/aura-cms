<?php

use Aura\Base\Livewire\UserTeams;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Cross-team visibility requires teams.');
    }
});

/**
 * UserTeams loads the viewed user with `withoutGlobalScopes()` so a Global Admin
 * can manage Memberships across teams. That bypass must not be available to an
 * ordinary team admin: the mount has to prove the target user is reachable, and
 * `$userId` has to be locked so it cannot be swapped after the check.
 */
test('a team admin cannot mount the membership editor for a user of another team', function () {
    $actor = createAdmin();
    $this->actingAs($actor->refresh());

    $foreign = foreignTeam();
    $foreignUser = soleMemberOf($foreign);

    $this->actingAs($actor->refresh());

    livewire(UserTeams::class, ['userId' => $foreignUser->id])
        ->assertStatus(403);
});

test('the membership editor locks the viewed user id against client mutation', function () {
    $ga = createGlobalAdmin();
    $this->actingAs($ga);

    $viewed = User::factory()->create();
    $other = User::factory()->create();

    expect(fn () => livewire(UserTeams::class, ['userId' => $viewed->id])
        ->set('userId', $other->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

/**
 * The `x-aura::image` component used to resolve attachments with
 * `withoutGlobalScopes()` and print the path with no policy check, so any
 * guessed id leaked another tenant's media URL.
 */
test('the image component renders nothing for an attachment of another team', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $foreign = foreignTeam();

    $foreignAttachment = Attachment::withoutGlobalScopes()->create([
        'url' => 'media/secret.jpg',
        'name' => 'secret.jpg',
        'title' => 'secret.jpg',
        'size' => 1024,
        'mime_type' => 'image/jpeg',
        'team_id' => $foreign->id,
        'user_id' => $actor->id,
        'type' => Attachment::$type,
    ]);

    $html = Blade::render('<x-aura::image :id="$id" />', ['id' => $foreignAttachment->id]);

    expect(trim($html))->toBe('')
        ->and($html)->not->toContain('secret.jpg');
});

test('the image component still renders an attachment of the current team', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $own = Attachment::create([
        'url' => 'media/own.jpg',
        'name' => 'own.jpg',
        'title' => 'own.jpg',
        'size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $html = Blade::render('<x-aura::image :id="$id" />', ['id' => $own->id]);

    expect($html)->toContain('own.jpg');
});

test('the image component renders nothing when the viewer may not view attachments', function () {
    $user = createAdmin();
    $role = Role::where('slug', 'editor')->firstOrFail();
    $permissions = $role->permissions;
    $permissions['view-attachment'] = false;
    $role->update(['permissions' => $permissions]);

    $this->actingAs($admin = createSuperAdmin());

    $own = Attachment::create([
        'url' => 'media/restricted.jpg',
        'name' => 'restricted.jpg',
        'title' => 'restricted.jpg',
        'size' => 1024,
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($user->refresh());

    $html = Blade::render('<x-aura::image :id="$id" />', ['id' => $own->id]);

    expect(trim($html))->toBe('');
});
