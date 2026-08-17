<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Support\TeamExecutionContext;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Explicit Team execution is a Teams-on seam.');
    }
});

test('explicit execution scopes queries and restores actor and Team after exceptions', function () {
    $requestActor = createSuperAdmin();
    $teamA = $requestActor->currentTeam;
    $teamB = Team::factory()->createQuietly(['user_id' => $requestActor->id]);
    $queuedActor = User::factory()->create();
    $role = Role::firstOrCreateGlobalAdmin();
    $queuedActor->roles()->attach($role->id, ['team_id' => $teamA->id]);
    $queuedActor->roles()->attach($role->id, ['team_id' => $teamB->id]);
    $queuedActor->forceFill(['current_team_id' => $teamB->id])->saveQuietly();

    Post::withoutEvents(function () use ($requestActor, $teamA, $teamB): void {
        Post::create(['title' => 'Team A', 'type' => Post::$type, 'user_id' => $requestActor->id, 'team_id' => $teamA->id]);
        Post::create(['title' => 'Team B', 'type' => Post::$type, 'user_id' => $requestActor->id, 'team_id' => $teamB->id]);
    });

    expect(fn () => app(TeamExecutionContext::class)->run($teamA->id, $queuedActor, function () use ($queuedActor, $teamA): void {
        expect(auth()->id())->toBe($queuedActor->id)
            ->and(TeamExecutionContext::active())->toBeTrue()
            ->and(TeamExecutionContext::currentTeamId())->toBe($teamA->id)
            ->and($queuedActor->current_team_id)->toBe($teamA->id)
            ->and(Post::query()->pluck('title')->all())->toBe(['Team A']);

        throw new RuntimeException('queue failure');
    }))->toThrow(RuntimeException::class, 'queue failure');

    expect(auth()->id())->toBe($requestActor->id)
        ->and(TeamExecutionContext::active())->toBeFalse()
        ->and(TeamExecutionContext::currentTeamId())->toBeNull()
        ->and($queuedActor->current_team_id)->toBe($teamB->id)
        ->and(User::withoutGlobalScopes()->findOrFail($queuedActor->id)->current_team_id)->toBe($teamB->id);
});

test('explicit execution rechecks membership before invoking queued work', function () {
    $owner = createSuperAdmin();
    $team = $owner->currentTeam;
    $recipient = soleMemberOf($team);
    $recipient->roles()->detach();
    $invoked = false;

    expect(fn () => app(TeamExecutionContext::class)->run($team->id, $recipient, function () use (&$invoked): void {
        $invoked = true;
    }))->toThrow(AuthorizationException::class);

    expect($invoked)->toBeFalse()
        ->and(TeamExecutionContext::active())->toBeFalse();
});
