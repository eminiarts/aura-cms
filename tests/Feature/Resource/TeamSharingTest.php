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

    Post::withoutGlobalScopes()->create([
        'title' => 'Team A',
        'team_id' => $teamA->id,
        'user_id' => $owner->id,
    ]);
    Post::withoutGlobalScopes()->create([
        'title' => 'Team B',
        'team_id' => $teamB->id,
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
    SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Team A',
        'team_id' => $teamA->id,
        'user_id' => $owner->id,
    ]);
    SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Team B',
        'team_id' => $teamB->id,
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
    SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Team Only',
        'team_id' => $team->id,
        'user_id' => $owner->id,
    ]);
    Post::withoutGlobalScopes()->create([
        'title' => 'Private Team Row',
        'team_id' => $team->id,
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

    $postA = Post::withoutGlobalScopes()->create([
        'title' => 'Background team A',
        'team_id' => $teamA->id,
        'user_id' => $owner->id,
    ]);
    $postB = Post::withoutGlobalScopes()->create([
        'title' => 'Background team B',
        'team_id' => $teamB->id,
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

it('restores fail-closed scope state after a background context throws an Error', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->createQuietly(['user_id' => $owner->id]);

    Post::withoutGlobalScopes()->create([
        'title' => 'Never leaked',
        'team_id' => $team->id,
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
