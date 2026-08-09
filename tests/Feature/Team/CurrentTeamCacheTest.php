<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team tests require the teams schema.');
    }
});

beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::flush();
});

it('uses the new current team after switching with a warmed team scope cache', function () {
    $user = createSuperAdmin();
    $firstTeam = Team::find($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Second Cache Team',
        'user_id' => $user->id,
    ]);

    $user->refresh();
    expect($user->switchTeam($firstTeam))->toBeTrue();

    $firstTeamPost = createPost([
        'title' => 'First Team Post',
        'team_id' => $firstTeam->id,
        'user_id' => $user->id,
    ]);
    $secondTeamPost = createPost([
        'title' => 'Second Team Post',
        'team_id' => $secondTeam->id,
        'user_id' => $user->id,
    ]);

    $cacheKey = User::currentTeamCacheKey($user->id);

    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();
    expect(Cache::get($cacheKey))->toBe($firstTeam->id);

    expect($user->switchTeam($secondTeam))->toBeTrue();
    expect(Cache::has($cacheKey))->toBeFalse();

    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeFalse();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();
    expect(Cache::get($cacheKey))->toBe($secondTeam->id);
});

it('clears current team and team list caches for affected users when deleting a team', function () {
    $user = createSuperAdmin();
    $firstTeam = Team::find($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Deleted Cache Team',
        'user_id' => $user->id,
    ]);
    $otherUser = User::factory()->create([
        'current_team_id' => $secondTeam->id,
    ]);

    // Attach-don't-mint: Memberships in every team point at the shared global
    // admin role (team_id = null), scoped to the team via the pivot.
    $globalAdmin = globalAdminRole();

    $firstTeam->users()->attach($otherUser->id, ['role_id' => $globalAdmin->id]);
    $secondTeam->users()->attach($otherUser->id, ['role_id' => $globalAdmin->id]);

    $firstTeamPost = createPost([
        'title' => 'Remaining Team Post',
        'team_id' => $firstTeam->id,
        'user_id' => $user->id,
    ]);
    $secondTeamPost = createPost([
        'title' => 'Deleted Team Post',
        'team_id' => $secondTeam->id,
        'user_id' => $user->id,
    ]);

    $user->refresh();
    $this->actingAs($user);
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();

    $otherUser->refresh();
    $this->actingAs($otherUser);
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeTrue();

    Cache::put('user.'.$user->id.'.teams', 'stale-user-teams');
    Cache::put('user.'.$otherUser->id.'.teams', 'stale-other-user-teams');

    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeTrue();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeTrue();
    expect(Cache::has('user.'.$user->id.'.teams'))->toBeTrue();
    expect(Cache::has('user.'.$otherUser->id.'.teams'))->toBeTrue();

    $this->actingAs($user);
    $secondTeam->delete();

    $reassignedUser = User::withoutGlobalScopes()->find($user->id);
    $reassignedOtherUser = User::withoutGlobalScopes()->find($otherUser->id);

    expect($reassignedUser->current_team_id)->toBe($firstTeam->id);
    expect($reassignedOtherUser->current_team_id)->toBe($firstTeam->id);
    expect(Cache::has(User::currentTeamCacheKey($user->id)))->toBeFalse();
    expect(Cache::has(User::currentTeamCacheKey($otherUser->id)))->toBeFalse();
    expect(Cache::has('user.'.$user->id.'.teams'))->toBeFalse();
    expect(Cache::has('user.'.$otherUser->id.'.teams'))->toBeFalse();

    $this->actingAs($reassignedUser);
    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();

    $this->actingAs($reassignedOtherUser);
    expect(Post::whereKey($firstTeamPost->id)->exists())->toBeTrue();
    expect(Post::whereKey($secondTeamPost->id)->exists())->toBeFalse();
});

it('caches a missing current team until a later model assignment invalidates it', function () {
    $user = User::factory()->create([
        'current_team_id' => null,
    ]);
    $this->actingAs($user);

    $cacheKey = User::currentTeamCacheKey($user->id);
    $currentTeamQueries = 0;

    DB::listen(function ($query) use (&$currentTeamQueries) {
        if (str_contains($query->sql, 'current_team_id') && str_contains($query->sql, 'from "users"')) {
            $currentTeamQueries++;
        }
    });

    expect(Post::count())->toBe(0);
    expect(Post::count())->toBe(0);
    expect(Cache::has($cacheKey))->toBeTrue()
        ->and($currentTeamQueries)->toBe(1);

    $team = Team::create([
        'name' => 'Later Assigned Team',
        'user_id' => $user->id,
    ]);
    $post = createPost([
        'title' => 'Later Assigned Team Post',
        'team_id' => $team->id,
        'user_id' => $user->id,
    ]);

    expect($user->fresh()->current_team_id)->toBe($team->id);
    expect(Post::whereKey($post->id)->exists())->toBeTrue();
    expect(Cache::get($cacheKey))->toBe($team->id);
});

it('keeps a request-local team snapshot and resets it at a worker boundary', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Team B', 'team_id' => $teamB->id]);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    DB::table('users')->where('id', $user->id)->update(['current_team_id' => $teamB->id]);
    Cache::forget(User::currentTeamCacheKey($user->id));

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    Aura::flushState();

    expect(Post::whereKey($postA->id)->exists())->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue();
});

it('resets the current-team snapshot after a queue job is processed', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Queue Team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Queue Team B', 'team_id' => $teamB->id]);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse();

    DB::table('users')->where('id', $user->id)->update(['current_team_id' => $teamB->id]);
    Cache::forget(User::currentTeamCacheKey($user->id));

    Event::dispatch(new JobProcessed('sync', Mockery::mock(Job::class), null));

    expect(Post::whereKey($postA->id)->exists())->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue();
});

it('never publishes an uncommitted current team and restores scope state after rollback', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postA = createPost(['title' => 'Rollback team A', 'team_id' => $teamA->id]);
    $postB = createPost(['title' => 'Rollback team B', 'team_id' => $teamB->id]);
    $cacheKey = User::currentTeamCacheKey($user->id);

    Aura::flushState();
    Cache::forget($cacheKey);

    expect(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);

    DB::beginTransaction();

    try {
        $user->forceFill(['current_team_id' => $teamB->id])->save();

        $visibleInsideTransaction = Post::whereKey($postB->id)->exists();
        $sharedCacheInsideTransaction = Cache::get($cacheKey);
    } finally {
        DB::rollBack();
    }

    expect($visibleInsideTransaction)->toBeTrue()
        ->and($sharedCacheInsideTransaction)->toBe($teamA->id)
        ->and(Post::whereKey($postA->id)->exists())->toBeTrue()
        ->and(Post::whereKey($postB->id)->exists())->toBeFalse()
        ->and(Cache::get($cacheKey))->toBe($teamA->id);
});

it('keeps a cold shared cache empty before commit and invalidates process state after commit', function () {
    $user = createSuperAdmin();
    $teamA = Team::findOrFail($user->current_team_id);
    $teamB = Team::factory()->createQuietly(['user_id' => $user->id]);
    $postB = createPost(['title' => 'Committed team B', 'team_id' => $teamB->id]);
    $cacheKey = User::currentTeamCacheKey($user->id);

    Aura::flushState();
    Cache::forget($cacheKey);

    DB::beginTransaction();
    $user->forceFill(['current_team_id' => $teamB->id])->save();

    $visibleBeforeCommit = Post::whereKey($postB->id)->exists();
    $cacheWasPublishedBeforeCommit = Cache::has($cacheKey);

    DB::commit();

    expect($visibleBeforeCommit)->toBeTrue()
        ->and($cacheWasPublishedBeforeCommit)->toBeFalse()
        ->and(Cache::has($cacheKey))->toBeFalse()
        ->and(Post::whereKey($postB->id)->exists())->toBeTrue()
        ->and(Cache::get($cacheKey))->toBe($teamB->id)
        ->and($teamA->id)->not->toBe($teamB->id);
});
