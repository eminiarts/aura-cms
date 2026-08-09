<?php

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

    SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Global',
        'team_id' => null,
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

    SharedCatalogPost::withoutGlobalScopes()->create([
        'title' => 'Global',
        'team_id' => null,
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
