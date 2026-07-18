<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Role-catalog staleness / shadowing at the integration seam (issue #54,
 * user story 30 + sweep area 7).
 *
 * RoleResolutionTest covers the resolution seam directly. These prove the same
 * invalidation is observable through hasPermission() on LIVE user instances in a
 * single request: a permission change reaches two separate instances at once, and
 * creating/deleting a Shadow flips the effective permission with no data rewrite
 * and no refresh() — the catalog-version-keyed memo recomputes on its own.
 */
beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Shadowing tests require the teams schema (teams-on only).');
    }

    $this->actingAs($this->admin = createSuperAdmin());
    $this->teamId = $this->admin->currentTeam->id;
});

function hardeningGlobalRole(string $slug, array $permissions): Role
{
    // Write a Global Role (team_id = null) the way the catalog helper does, so the
    // InitialPostFields saving hook does not re-team it. Bump the version since a
    // quiet write fires no model events.
    $role = Role::withoutGlobalScopes()->newModelInstance([
        'name' => ucfirst($slug), 'slug' => $slug, 'super_admin' => false,
        'permissions' => $permissions, 'team_id' => null,
    ]);
    $role->saveQuietly();
    Role::bumpCatalogVersion();

    return $role;
}

function memberIn(int $teamId, int $roleId): User
{
    $user = User::factory()->create(['current_team_id' => $teamId]);
    $user->roles()->attach($roleId, ['team_id' => $teamId]);
    $user->refresh();

    return $user;
}

it('reflects a permission change on two live user instances at once (no refresh)', function () {
    $editor = Role::create([
        'team_id' => $this->teamId, 'slug' => 'editor', 'type' => 'Role', 'title' => 'Editor',
        'name' => 'Editor', 'super_admin' => false, 'permissions' => ['create-post' => true],
    ]);

    $userA = memberIn($this->teamId, $editor->id);
    $userB = memberIn($this->teamId, $editor->id);

    expect($userA->hasPermission('create-post'))->toBeTrue();
    expect($userB->hasPermission('create-post'))->toBeTrue();

    // A single write bumps the catalog version; both already-warm instances
    // recompute their resolved-roles memo on the next check.
    $editor->update(['permissions' => ['create-post' => false]]);

    expect($userA->hasPermission('create-post'))->toBeFalse();
    expect($userB->hasPermission('create-post'))->toBeFalse();
});

it('applies a Shadow the moment it is created and reverts the moment it is deleted', function () {
    $global = hardeningGlobalRole('contributor', ['create-post' => true]);

    $member = memberIn($this->teamId, $global->id);

    // Global definition applies: create-post granted.
    expect($member->hasPermission('create-post'))->toBeTrue();

    // The team Shadows the slug with a stricter permission set. Same instance,
    // no refresh — the Shadow wins instantly.
    $shadow = Role::create([
        'team_id' => $this->teamId, 'slug' => 'contributor', 'type' => 'Role', 'title' => 'Contributor',
        'name' => 'Contributor', 'super_admin' => false, 'permissions' => ['create-post' => false],
    ]);

    expect($member->hasPermission('create-post'))->toBeFalse();

    // Deleting the Shadow falls back to the Global definition, again instantly.
    $shadow->delete();

    expect($member->hasPermission('create-post'))->toBeTrue();
});

it('applies a Shadow that grants Super Admin power without rewriting the Membership', function () {
    $global = hardeningGlobalRole('lead', ['create-post' => false]);
    $member = memberIn($this->teamId, $global->id);

    expect($member->isSuperAdmin())->toBeFalse();

    // Shadow the slug with a Super Admin role: the member's power jumps instantly.
    $shadow = Role::create([
        'team_id' => $this->teamId, 'slug' => 'lead', 'type' => 'Role', 'title' => 'Lead',
        'name' => 'Lead', 'super_admin' => true, 'permissions' => [],
    ]);

    expect($member->isSuperAdmin())->toBeTrue();

    $shadow->delete();

    expect($member->isSuperAdmin())->toBeFalse();

    // The Membership pivot still points at the ORIGINAL global role id throughout —
    // shadowing resolves by slug at check time, it never rewrites pivots.
    expect(
        DB::table('user_role')
            ->where('user_id', $member->id)
            ->where('team_id', $this->teamId)
            ->value('role_id')
    )->toBe($global->id);
});
