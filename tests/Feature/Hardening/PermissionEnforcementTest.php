<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

/**
 * End-to-end permission enforcement (issue #54, user story 33 + AC bullet 3).
 *
 * PermissionsTest already proves index/create/edit gating for a moderator. These
 * complement the release-gate cases: a permission REMOVED from a role blocks the
 * corresponding page on the very next request (no stale cross-request cache), a
 * super_admin=false role with an EMPTY permission set is refused everywhere, and
 * view/delete are gated too. Written mode-agnostically (roles with no team_id,
 * a user with no current team) so the file exercises BOTH the teams-on and the
 * Teams-off suites identically.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    Aura::fake();
    Aura::setModel(new Post);
});

function attachRole(User $user, Role $role): void
{
    $user->update(['roles' => [$role->id]]);
    $user->refresh();
}

it('refuses every resource page for a role with no permissions (super_admin=false, empty set)', function () {
    $role = Role::create([
        'name' => 'Powerless', 'slug' => 'powerless', 'description' => 'No permissions.',
        'super_admin' => false, 'permissions' => [],
    ]);
    $post = Post::create([
        'type' => 'Post', 'title' => 'Locked', 'slug' => 'locked', 'name' => 'Locked',
        'description' => 'Locked', 'fields' => [],
    ]);

    attachRole($this->user, $role);

    $slug = $post->getSlug();

    $this->actingAs($this->user)->get(route('aura.'.$slug.'.index'))->assertForbidden();
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.create'))->assertForbidden();
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.edit', ['id' => $post->id]))->assertForbidden();
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.view', ['id' => $post->id]))->assertForbidden();
});

it('blocks a page on the next request once its permission is removed from the role', function () {
    $role = Role::create([
        'name' => 'Creator', 'slug' => 'creator', 'description' => 'Can create.',
        'super_admin' => false, 'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'create-post' => true,
        ],
    ]);

    attachRole($this->user, $role);

    $slug = (new Post)->getSlug();

    // Baseline: the create page is reachable.
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.create'))->assertSuccessful();

    // Revoke create-post (a fresh permission set on the same role).
    $role->update(['permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => false,
    ]]);

    // The very next request is refused — no stale authorization survives.
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.create'))->assertForbidden();

    // A still-granted page keeps working, proving the change was targeted.
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.index'))->assertSuccessful();
});

it('gates the view page on the view permission', function () {
    $post = Post::create([
        'type' => 'Post', 'title' => 'Viewable', 'slug' => 'viewable', 'name' => 'Viewable',
        'description' => 'Viewable', 'fields' => [],
    ]);
    $slug = $post->getSlug();

    $viewer = Role::create([
        'name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Can view.',
        'super_admin' => false, 'permissions' => ['viewAny-post' => true, 'view-post' => true],
    ]);
    attachRole($this->user, $viewer);
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.view', ['id' => $post->id]))->assertSuccessful();

    // Remove view-post: the view page is now refused.
    $viewer->update(['permissions' => ['viewAny-post' => true, 'view-post' => false]]);
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.view', ['id' => $post->id]))->assertForbidden();
});

it('gates the edit page for a read-only (view-but-not-update) role', function () {
    $post = Post::create([
        'type' => 'Post', 'title' => 'Read only', 'slug' => 'read-only', 'name' => 'Read only',
        'description' => 'Read only', 'fields' => [],
    ]);
    $slug = $post->getSlug();

    $readOnly = Role::create([
        'name' => 'Read Only', 'slug' => 'read-only-role', 'description' => 'View, not edit.',
        'super_admin' => false, 'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'update-post' => false,
        ],
    ]);
    attachRole($this->user, $readOnly);

    // Can list and view, cannot edit.
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.index'))->assertSuccessful();
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.view', ['id' => $post->id]))->assertSuccessful();
    $this->actingAs($this->user)->get(route('aura.'.$slug.'.edit', ['id' => $post->id]))->assertForbidden();
});

it('blocks a delete bulk action for a role without delete permission', function () {
    $role = Role::create([
        'name' => 'No Delete', 'slug' => 'no-delete', 'description' => 'Cannot delete.',
        'super_admin' => false, 'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'delete-post' => false,
        ],
    ]);

    $post = Post::create([
        'type' => 'Post', 'title' => 'Keep me', 'slug' => 'keep-me', 'name' => 'Keep me',
        'description' => 'Keep me', 'fields' => [],
    ]);

    attachRole($this->user, $role);

    livewire(Table::class, ['query' => null, 'model' => $post])
        ->set('selected', [$post->id])
        ->call('bulkAction', 'deleteSelected')
        ->assertStatus(403);

    expect(Post::withoutGlobalScopes()->whereKey($post->id)->exists())->toBeTrue();
});
