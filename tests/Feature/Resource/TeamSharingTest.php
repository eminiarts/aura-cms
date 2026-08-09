<?php

use Aura\Base\Jobs\GenerateResourcePermissions;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class SharedCatalogPost extends Resource
{
    public static bool $sharedAcrossTeams = true;

    public static string $type = 'SharedCatalogPost';
}

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team sharing requires the teams schema.');
    }
});

it('opts shared catalogs into the resource contract without changing the default', function () {
    expect(Role::sharesRecordsAcrossTeams())->toBeTrue()
        ->and(Permission::sharesRecordsAcrossTeams())->toBeTrue()
        ->and(Post::sharesRecordsAcrossTeams())->toBeFalse();
});

it('keeps non-shared resources isolated to the current team', function () {
    $owner = User::factory()->create();
    $teamA = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $teamB = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $userA = User::factory()->create(['current_team_id' => $teamA->id]);
    $userB = User::factory()->create(['current_team_id' => $teamB->id]);

    Post::createForTeamForSystem($teamA->id, [
        'title' => 'Team A',
        'user_id' => $owner->id,
    ]);
    Post::createForTeamForSystem($teamB->id, [
        'title' => 'Team B',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($userA);
    expect(Post::pluck('title')->all())->toBe(['Team A']);

    $this->actingAs($userB);
    expect(Post::pluck('title')->all())->toBe(['Team B']);
});

it('composes global and current-team rows without leaking another team', function () {
    $owner = User::factory()->create();
    $teamA = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $teamB = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $userA = User::factory()->create(['current_team_id' => $teamA->id]);
    $userB = User::factory()->create(['current_team_id' => $teamB->id]);

    SharedCatalogPost::createGlobalForSystem([
        'title' => 'Global',
        'user_id' => $owner->id,
    ]);
    SharedCatalogPost::createForTeamForSystem($teamA->id, [
        'title' => 'Team A',
        'user_id' => $owner->id,
    ]);
    SharedCatalogPost::createForTeamForSystem($teamB->id, [
        'title' => 'Team B',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($userA);
    expect(SharedCatalogPost::pluck('title')->all())
        ->toEqualCanonicalizing(['Global', 'Team A']);

    $this->actingAs($userB);
    expect(SharedCatalogPost::pluck('title')->all())
        ->toEqualCanonicalizing(['Global', 'Team B']);
});

it('shows only global shared rows to an authenticated user without a team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $userWithoutTeam = User::factory()->create(['current_team_id' => null]);

    SharedCatalogPost::createGlobalForSystem([
        'title' => 'Global',
        'user_id' => $owner->id,
    ]);
    SharedCatalogPost::createForTeamForSystem($team->id, [
        'title' => 'Team Only',
        'user_id' => $owner->id,
    ]);
    Post::createForTeamForSystem($team->id, [
        'title' => 'Private Team Row',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($userWithoutTeam);

    expect(SharedCatalogPost::pluck('title')->all())->toBe(['Global'])
        ->and(Post::count())->toBe(0)
        ->and(User::pluck('users.id')->all())->toBe([$userWithoutTeam->id]);
});

it('allows only Global Admins to authorize global rows on opted-in resources', function () {
    $teamAdmin = createSuperAdmin();
    $globalAdmin = createGlobalAdmin();

    expect(Gate::forUser($teamAdmin)->denies('createGlobal', new SharedCatalogPost))->toBeTrue()
        ->and(Gate::forUser($globalAdmin)->allows('createGlobal', new SharedCatalogPost))->toBeTrue()
        ->and(Gate::forUser($globalAdmin)->denies(
            'createGlobal',
            new Post
        ))->toBeTrue();
});

it('allows only Global Admins to mutate global rows from every shared resource', function () {
    $teamAdmin = createSuperAdmin();
    $globalAdmin = createGlobalAdmin();

    $permission = Permission::createGlobalForSystem([
        'name' => 'Shared permission',
        'slug' => 'shared-permission',
        'group' => 'Shared',
    ]);

    $sharedPost = SharedCatalogPost::createGlobalForSystem([
        'title' => 'Shared post',
        'user_id' => $teamAdmin->id,
    ]);

    foreach ([$permission, $sharedPost] as $resource) {
        expect(Gate::forUser($teamAdmin)->allows('view', $resource))->toBeTrue();

        foreach (['update', 'delete', 'restore', 'forceDelete'] as $ability) {
            expect(Gate::forUser($teamAdmin)->denies($ability, $resource))->toBeTrue()
                ->and(Gate::forUser($globalAdmin)->allows($ability, $resource))->toBeTrue();
        }
    }
});

it('fails closed for unauthenticated tenant queries and exposes explicit background contexts', function () {
    $owner = User::factory()->create();
    $teamA = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $teamB = Team::factory()->createQuietly(['user_id' => $owner->id]);

    $postA = Post::createForTeamForSystem($teamA->id, [
        'title' => 'Background team A',
        'user_id' => $owner->id,
    ]);
    $postB = Post::createForTeamForSystem($teamB->id, [
        'title' => 'Background team B',
        'user_id' => $owner->id,
    ]);

    auth()->logout();

    expect(Post::count())->toBe(0)
        ->and(User::count())->toBe(0)
        ->and(Team::count())->toBe(0);

    $teamAIds = TeamScope::forTeam($teamA->id, fn () => Post::pluck('id')->all());
    $allIds = TeamScope::withoutTenantScope(fn () => Post::pluck('id')->all());

    expect($teamAIds)->toBe([$postA->id])
        ->and($allIds)->toEqualCanonicalizing([$postA->id, $postB->id]);
});

it('makes explicit nested team contexts authoritative for guests and Global Admins', function () {
    $owner = User::factory()->create();
    $teamA = Team::factory()->createQuietly(['name' => 'Context A', 'user_id' => $owner->id]);
    $teamB = Team::factory()->createQuietly(['name' => 'Context B', 'user_id' => $owner->id]);
    $memberA = User::factory()->create(['current_team_id' => $teamA->id]);
    $globalAdmin = createGlobalAdmin(['current_team_id' => $teamB->id]);
    $membershipRole = Role::firstOrCreateGlobalAdmin();

    $this->actingAs($owner);

    $teamA->users()->attach($memberA->id, ['role_id' => $membershipRole->id]);
    $teamB->users()->attach($globalAdmin->id, ['role_id' => $membershipRole->id]);

    $globalEditor = Role::createGlobalForSystem([
        'name' => 'Global Editor',
        'slug' => 'context-editor',
        'permissions' => [],
    ]);
    Role::createGlobalForSystem([
        'name' => 'Global Viewer',
        'slug' => 'context-viewer',
        'permissions' => [],
    ]);
    $shadowA = TeamScope::forTeam($teamA->id, fn () => Role::withoutGlobalScopes()->create([
        'name' => 'Team A Editor',
        'slug' => 'context-editor',
        'team_id' => $teamA->id,
        'permissions' => [],
    ]));

    SharedCatalogPost::createGlobalForSystem([
        'title' => 'Context Global',
        'user_id' => $owner->id,
    ]);
    TeamScope::forTeam($teamA->id, fn () => SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Context A',
        'team_id' => $teamA->id,
        'user_id' => $owner->id,
    ]));
    TeamScope::forTeam($teamB->id, fn () => SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Context B',
        'team_id' => $teamB->id,
        'user_id' => $owner->id,
    ]));

    $readContext = function (): array {
        $roles = Role::query()
            ->shadowResolved(Role::currentTeamIdForResolution())
            ->whereIn('slug', ['context-editor', 'context-viewer'])
            ->get();

        return [
            'posts' => SharedCatalogPost::pluck('title')->all(),
            'users' => User::pluck('users.id')->all(),
            'teams' => Team::pluck('teams.id')->all(),
            'roles' => $roles->pluck('id')->all(),
        ];
    };

    $assertTeamA = function (array $result) use ($teamA, $memberA, $shadowA): void {
        expect($result['posts'])->toEqualCanonicalizing(['Context Global', 'Context A'])
            ->and($result['users'])->toBe([$memberA->id])
            ->and($result['teams'])->toBe([$teamA->id])
            ->and($result['roles'])->toHaveCount(2)
            ->and($result['roles'])->toContain($shadowA->id);
    };

    auth()->logout();
    $assertTeamA(TeamScope::forTeam($teamA->id, $readContext));

    $this->actingAs($globalAdmin);
    $assertTeamA(TeamScope::forTeam($teamA->id, $readContext));

    $nested = TeamScope::forTeam($teamA->id, function () use ($teamB): array {
        $outerBefore = SharedCatalogPost::pluck('title')->all();
        $inner = TeamScope::forTeam($teamB->id, fn () => SharedCatalogPost::pluck('title')->all());
        $outerAfter = SharedCatalogPost::pluck('title')->all();

        return compact('outerBefore', 'inner', 'outerAfter');
    });

    expect($nested['outerBefore'])->toEqualCanonicalizing(['Context Global', 'Context A'])
        ->and($nested['inner'])->toEqualCanonicalizing(['Context Global', 'Context B'])
        ->and($nested['outerAfter'])->toEqualCanonicalizing(['Context Global', 'Context A'])
        ->and($globalEditor->id)->not->toBe($shadowA->id);
});

it('returns the global role catalog for a null shadow resolution context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->createQuietly(['user_id' => $owner->id]);

    $this->actingAs($owner);

    $globalEditor = Role::createGlobalForSystem([
        'name' => 'Global Null Editor',
        'slug' => 'null-context-editor',
        'permissions' => [],
    ]);
    $globalViewer = Role::createGlobalForSystem([
        'name' => 'Global Null Viewer',
        'slug' => 'null-context-viewer',
        'permissions' => [],
    ]);
    $shadowEditor = TeamScope::forTeam($team->id, fn () => Role::withoutGlobalScopes()->create([
        'name' => 'Team Null Editor',
        'slug' => 'null-context-editor',
        'team_id' => $team->id,
        'permissions' => [],
    ]));

    auth()->logout();

    $resolvedRoles = Role::shadowResolved(null)
        ->whereIn('slug', ['null-context-editor', 'null-context-viewer'])
        ->get();

    expect($resolvedRoles->pluck('id')->all())
        ->toEqualCanonicalizing([$globalEditor->id, $globalViewer->id])
        ->and($resolvedRoles->pluck('id')->all())->not->toContain($shadowEditor->id)
        ->and($resolvedRoles->pluck('slug')->all())->toHaveCount(2);
});

it('restores fail-closed scope state after a background context throws an Error', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->createQuietly(['user_id' => $owner->id]);

    Post::createForTeamForSystem($team->id, [
        'title' => 'Never leaked',
        'user_id' => $owner->id,
    ]);

    auth()->logout();

    expect(fn () => TeamScope::forTeam($team->id, function () {
        expect(Post::count())->toBe(1);

        throw new Error('background failure');
    }))->toThrow(Error::class, 'background failure');

    expect(Post::count())->toBe(0);

    expect(fn () => TeamScope::withoutTenantScope(function () {
        expect(Post::count())->toBe(1);

        throw new Error('bypass failure');
    }))->toThrow(Error::class, 'bypass failure');

    expect(Post::count())->toBe(0);
});

it('runs the real permission generation job idempotently without authentication', function () {
    auth()->logout();

    (new GenerateResourcePermissions(Post::class))->handle();
    (new GenerateResourcePermissions(Post::class))->handle();

    $permissions = Permission::withoutGlobalScopes()
        ->whereNull('team_id')
        ->where('group', (new Post)->pluralName())
        ->get();

    expect($permissions)->toHaveCount(8)
        ->and($permissions->pluck('slug')->unique())->toHaveCount(8);
});
