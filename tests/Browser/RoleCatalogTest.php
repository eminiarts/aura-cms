<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

require_once __DIR__.'/Support/helpers.php';

/*
|--------------------------------------------------------------------------
| Role / catalog UI browser journeys (issue #56)
|--------------------------------------------------------------------------
|
| Real Chromium against the Testbench app: a team Super Admin creates a Team Role
| with a permission through the matrix and a Shadow of a Global Role by slug; the
| merged Roles index marks Global Roles and hides the shadowed global row; a
| Global Admin mints a Global Role through the guarded toggle; and removing a
| permission from a role visibly gates the affected resource page for a member.
|
*/

test('a team Super Admin creates a Team Role with a permission through the matrix', function () {
    $admin = browserSuperAdmin('admin-password');
    $team = $admin->currentTeam;

    $page = visit('/admin/role/create');

    $page->assertSee('Permissions');

    $page->fill('#resource-field-name', 'Reviewer Role');

    // Toggle a single permission on through the matrix (a users permission the
    // team-creation hook has already generated).
    $page->check('permissions_view-user')->wait(1);

    $page->press('Save')->wait(3);

    $role = Role::withoutGlobalScopes()->where('slug', 'reviewer_role')->first();

    expect($role)->not->toBeNull()
        ->and($role->team_id)->toBe($team->id)              // a Team Role, not global
        ->and($role->permissions['view-user'] ?? false)->toBeTrue();
});

test('a team Super Admin creates a Shadow of a Global Role, and the index hides the shadowed global row', function () {
    $admin = browserSuperAdmin('admin-password');
    $team = $admin->currentTeam;

    // A Global Role the team will shadow by slug, plus an unshadowed one that must
    // keep its "Global" marker in the team-context index.
    $globalUser = browserGlobalRole('user', 'Global Member Role');
    browserGlobalRole('auditor', 'Global Auditor');

    // Create a Team Role whose slug matches the global 'user' role: naming the
    // role "User" derives the slug 'user' (the disabled Slug field auto-fills),
    // producing a Shadow.
    $create = visit('/admin/role/create');

    $create->fill('#resource-field-name', 'User')->wait(1);

    $create->press('Save')->wait(3);

    $shadow = Role::withoutGlobalScopes()
        ->where('slug', 'user')
        ->where('team_id', $team->id)
        ->first();

    expect($shadow)->not->toBeNull()
        ->and($shadow->team_id)->toBe($team->id)
        // The Shadow wins resolution in this team, without any pivot rewrite.
        ->and(Role::resolveForTeam('user', $team->id)->id)->toBe($shadow->id)
        // The global definition still exists — it is only hidden, never deleted.
        ->and(Role::withoutGlobalScopes()->whereKey($globalUser->id)->exists())->toBeTrue();

    // The merged, shadow-resolved Roles index: the shadowed global row is hidden,
    // while a non-shadowed Global Role keeps its "Global" marker.
    $index = visit('/admin/role');

    $index->assertSee('Global')                     // the badge on unshadowed globals
        ->assertSee('Global Auditor')               // the unshadowed global row shows
        ->assertDontSee('Global Member Role');      // the shadowed global row is hidden
});

test('a Global Admin mints a Global Role through the guarded global toggle', function () {
    $ga = browserGlobalAdmin('ga-password');
    $this->actingAs($ga);

    $page = visit('/admin/role/create');

    $page->assertSee('Global Role');

    $page->fill('#resource-field-name', 'Continent Role');

    // Flip the guarded "Global Role" toggle (distinct from the "Admin" toggle).
    $page->click('#resource-field-is-global-wrapper [x-ref="toggle"]')->wait(1);

    $page->press('Save')->wait(3);

    $role = Role::withoutGlobalScopes()->where('slug', 'continent_role')->first();

    expect($role)->not->toBeNull()
        ->and($role->team_id)->toBeNull();   // a Global Role
});

test('removing a permission from a role blocks the affected page for a member holding it', function () {
    $admin = browserSuperAdmin('admin-password');
    $team = $admin->currentTeam;

    // A Team Role that grants access to the GalleryPage index.
    $role = browserTeamRole($team->id, 'Gallery Viewer', 'gallery-viewer');
    $role->update(['permissions' => [
        'viewAny-gallery-page' => true,
        'view-gallery-page' => true,
    ]]);

    // A plain member (not a Super Admin) holding that role in the team.
    $member = User::factory()->create(['email' => 'gallery-member@example.com', 'current_team_id' => $team->id]);
    browserAttachMembership($member, $team->id, $role->id);
    $member->refresh();

    // With the permission, the member can load the resource index.
    $this->actingAs($member);

    $granted = visit('/admin/gallery-page');

    $granted->assertSee('GalleryPages');

    // The team Super Admin removes the viewAny permission through the Role edit
    // form's matrix.
    $this->actingAs($admin);

    $edit = visit('/admin/role/'.$role->id.'/edit');

    $edit->uncheck('permissions_viewAny-gallery-page')->wait(1);

    $edit->press('Save')->wait(3);

    expect($role->fresh()->permissions['viewAny-gallery-page'] ?? false)->toBeFalse();

    // The member's next load of the same page is now blocked — assert the actual
    // refusal (the 403 page), not merely the absence of the index content, so a
    // blank render could never pass.
    $this->actingAs($member);

    $blocked = visit('/admin/gallery-page');

    $blocked->assertSee('403')
        ->assertSee('This action is unauthorized')
        ->assertDontSee('GalleryPages');
});
