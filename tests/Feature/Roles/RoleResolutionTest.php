<?php

use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The Role Catalog resolution seam (Role::resolveForTeam + User::cachedRoles):
// Shadowing resolves by slug at check time. A Team Role wins over a Global Role
// of the same slug; deleting the Team Role falls back to the Global Role. No
// Membership pivot rows are ever rewritten.

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Role catalog resolution requires the teams schema.');
    }

    // Seed the base catalog the same way a fresh install does.
    RoleCatalogSeeder::seed();
});

/**
 * Create a Global Role (team_id = null) bypassing TeamScope.
 */
function makeGlobalRole(string $slug, array $attributes = []): Role
{
    return Role::withoutGlobalScopes()->create(array_merge([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'super_admin' => false,
        'permissions' => [],
        'team_id' => null,
    ], $attributes));
}

/**
 * Create a Team Role (a Shadow) for the given team.
 */
function makeTeamRole(int $teamId, string $slug, array $attributes = []): Role
{
    return Role::withoutGlobalScopes()->create(array_merge([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $teamId,
    ], $attributes));
}

describe('Role::resolveForTeam', function () {
    it('seeds admin and user Global Roles', function () {
        $admin = Role::resolveForTeam('admin', null);
        $user = Role::resolveForTeam('user', null);

        expect($admin)->not->toBeNull();
        expect($admin->super_admin)->toBeTrue();
        expect($admin->team_id)->toBeNull();

        expect($user)->not->toBeNull();
        expect($user->super_admin)->toBeFalse();
        expect($user->team_id)->toBeNull();
    });

    it('resolves the Global Role when the team has no Shadow', function () {
        $team = Team::factory()->create();
        makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);

        $resolved = Role::resolveForTeam('editor', $team->id);

        expect($resolved)->not->toBeNull();
        expect($resolved->team_id)->toBeNull();
        expect($resolved->permissions)->toBe(['view-post' => true]);
    });

    it('prefers the Team Role (Shadow) over the Global Role of the same slug', function () {
        $team = Team::factory()->create();
        makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);
        $shadow = makeTeamRole($team->id, 'editor', ['permissions' => ['delete-post' => true]]);

        $resolved = Role::resolveForTeam('editor', $team->id);

        expect($resolved->id)->toBe($shadow->id);
        expect($resolved->team_id)->toBe($team->id);
        expect($resolved->permissions)->toBe(['delete-post' => true]);
    });

    it('does not leak one team\'s Shadow into another team', function () {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);
        makeTeamRole($teamA->id, 'editor', ['permissions' => ['delete-post' => true]]);

        // Team B has no Shadow, so it resolves the Global Role.
        $resolved = Role::resolveForTeam('editor', $teamB->id);

        expect($resolved->team_id)->toBeNull();
        expect($resolved->permissions)->toBe(['view-post' => true]);
    });

    it('returns null for an unknown slug', function () {
        $team = Team::factory()->create();

        expect(Role::resolveForTeam('does-not-exist', $team->id))->toBeNull();
    });

    it('makes Global Roles visible to team-scoped Role queries (TeamScope)', function () {
        $user = createSuperAdmin();
        $this->actingAs($user);
        $teamId = $user->current_team_id;

        makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);
        makeTeamRole($teamId, 'moderator');

        // A plain, team-scoped Role query now sees the team's own roles AND the
        // Global Roles (team_id = null), without bypassing scopes.
        $slugs = Role::pluck('slug');

        expect($slugs)->toContain('editor');   // Global Role
        expect($slugs)->toContain('moderator'); // Team Role
        expect($slugs)->toContain('admin');     // seeded Global Role
    });
});

describe('shadow create/delete takes instant effect through the User seam', function () {
    it('applies the Shadow the moment it is created and falls back when deleted', function () {
        // A user whose Membership is the global "editor" role in their team.
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Cache::forget("user_{$user->id}_current_team_id");

        $globalEditor = makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);
        $user->roles()->syncWithPivotValues([$globalEditor->id], ['team_id' => $team->id]);
        $user->refresh();
        $this->actingAs($user);

        // Global definition applies.
        expect($user->hasPermission('view-post'))->toBeTrue();
        expect($user->hasPermission('delete-post'))->toBeFalse();

        // Creating a Shadow changes the outcome instantly — no pivot rewrite.
        $shadow = makeTeamRole($team->id, 'editor', ['permissions' => ['delete-post' => true]]);

        expect($user->hasPermission('view-post'))->toBeFalse();
        expect($user->hasPermission('delete-post'))->toBeTrue();

        // The Membership row still points at the global role id.
        $pivotRoleId = DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('team_id', $team->id)
            ->value('role_id');
        expect($pivotRoleId)->toBe($globalEditor->id);

        // Deleting the Shadow falls back to the global definition instantly.
        $shadow->delete();

        expect($user->hasPermission('view-post'))->toBeTrue();
        expect($user->hasPermission('delete-post'))->toBeFalse();
    });

    it('resolves a super_admin Shadow so isSuperAdmin reflects it live', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Cache::forget("user_{$user->id}_current_team_id");

        $globalManager = makeGlobalRole('manager', ['super_admin' => false]);
        $user->roles()->syncWithPivotValues([$globalManager->id], ['team_id' => $team->id]);
        $user->refresh();
        $this->actingAs($user);

        expect($user->isSuperAdmin())->toBeFalse();
        expect($user->hasRole('manager'))->toBeTrue();

        // Shadow the manager role as a Super Admin inside this team.
        makeTeamRole($team->id, 'manager', ['super_admin' => true]);

        expect($user->isSuperAdmin())->toBeTrue();
    });
});

describe('resolved-roles memoization', function () {
    it('serves repeated permission checks from cache and recomputes only when the catalog changes', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Cache::forget("user_{$user->id}_current_team_id");

        $editor = makeGlobalRole('editor', ['permissions' => ['view-post' => true]]);
        $user->roles()->syncWithPivotValues([$editor->id], ['team_id' => $team->id]);
        $user->refresh();
        $this->actingAs($user);

        // Warm the memo (this call does the resolution queries).
        expect($user->hasPermission('view-post'))->toBeTrue();

        // Repeated permission checks in the same request are served from the
        // memo and must issue ZERO role/user_role queries.
        DB::enableQueryLog();
        $user->isSuperAdmin();
        $user->hasPermission('view-post');
        $user->hasPermissionTo('view', new Post);
        $roleQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn ($q) => str_contains($q, 'user_role') || str_contains($q, '"roles"'));
        DB::disableQueryLog();

        expect($roleQueries)->toHaveCount(0);

        // Creating a Shadow bumps the catalog version → the next check recomputes
        // and reflects the new definition (instant shadow effect preserved).
        makeTeamRole($team->id, 'editor', ['permissions' => ['delete-post' => true]]);

        expect($user->hasPermission('view-post'))->toBeFalse();
        expect($user->hasPermission('delete-post'))->toBeTrue();
    });
});

describe('permission set normalization', function () {
    it('behaves identically whether permissions are stored as an array or a JSON string', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Cache::forget("user_{$user->id}_current_team_id");

        // Persist the permission set as a raw JSON string, bypassing the cast.
        $role = makeTeamRole($team->id, 'reviewer');
        DB::table('roles')
            ->where('id', $role->id)
            ->update(['permissions' => json_encode(['view-post' => true])]);

        $user->roles()->syncWithPivotValues([$role->id], ['team_id' => $team->id]);
        $user->refresh();
        $this->actingAs($user);

        expect($user->hasPermission('view-post'))->toBeTrue();
        expect($user->hasPermission('delete-post'))->toBeFalse();
    });
});
