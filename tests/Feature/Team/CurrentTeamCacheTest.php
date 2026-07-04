<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Cache;

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

    $firstTeamRole = Role::withoutGlobalScopes()
        ->where('team_id', $firstTeam->id)
        ->where('slug', 'admin')
        ->first();
    $secondTeamRole = Role::withoutGlobalScopes()
        ->where('team_id', $secondTeam->id)
        ->where('slug', 'admin')
        ->first();

    $firstTeam->users()->attach($otherUser->id, ['role_id' => $firstTeamRole->id]);
    $secondTeam->users()->attach($otherUser->id, ['role_id' => $secondTeamRole->id]);

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

it('does not cache a missing current team forever before a later team assignment', function () {
    $user = User::factory()->create([
        'current_team_id' => null,
    ]);
    $this->actingAs($user);

    $cacheKey = User::currentTeamCacheKey($user->id);

    expect(Post::count())->toBe(0);
    expect(Post::count())->toBe(0);
    expect(Cache::has($cacheKey))->toBeFalse();

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
