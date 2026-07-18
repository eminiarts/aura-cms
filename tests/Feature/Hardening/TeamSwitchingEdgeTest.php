<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Team-switching hardening (issue #54, user stories 20, 34-35).
 *
 * GlobalAdminVisitationTest already covers the non-member 403 and Global Admin
 * visitation. These complement the switch-target integrity edges (soft-deleted
 * team, unknown id) and the staleness edges: a Membership removed in another
 * session refuses the next switch, and deleting the current team falls the user
 * back gracefully — including the "no team left" branch.
 */
beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team-switching tests require the teams schema (teams-on only).');
    }

    $this->actingAs($this->user = createSuperAdmin());
});

it('refuses switching into a soft-deleted team (404)', function () {
    $currentTeam = $this->user->currentTeam;

    $ghost = Team::factory()->create();
    $ghost->delete(); // soft delete

    $this->put(route('aura.current-team.update'), ['team_id' => $ghost->id])
        ->assertNotFound();

    expect($this->user->fresh()->current_team_id)->toBe($currentTeam->id);
});

it('refuses switching into a team id that does not exist (404)', function () {
    $currentTeam = $this->user->currentTeam;

    $this->put(route('aura.current-team.update'), ['team_id' => 999999])
        ->assertNotFound();

    expect($this->user->fresh()->current_team_id)->toBe($currentTeam->id);
});

it('refuses switching into a team after the Membership was removed in another session', function () {
    $teamA = $this->user->currentTeam;

    // Creating a team auto-joins the creator and makes it current; switch back to A
    // so the user is a member of both A and B with A current.
    $teamB = Team::factory()->create();
    $this->user->switchTeam($teamA);

    // Another session detaches the user from team B (raw pivot delete + cache bust).
    DB::table('user_role')->where('user_id', $this->user->id)->where('team_id', $teamB->id)->delete();
    Cache::forget('user.'.$this->user->id.'.teams');

    // Next request: the switch is refused because the user is no longer a member.
    $this->actingAs(User::find($this->user->id))
        ->put(route('aura.current-team.update'), ['team_id' => $teamB->id])
        ->assertForbidden();

    expect($this->user->fresh()->current_team_id)->toBe($teamA->id);
});

it('falls the current team back to a remaining team when the current team is deleted', function () {
    $teamA = $this->user->currentTeam;

    // Creating team B auto-joins the user and makes B their current team.
    $teamB = Team::factory()->create();
    expect($this->user->fresh()->current_team_id)->toBe($teamB->id);

    // Team B is deleted out from under them.
    Team::find($teamB->id)->delete();

    // Next request lands on the remaining team A, not a ghost tenant.
    $fresh = User::find($this->user->id);
    expect($fresh->current_team_id)->toBe($teamA->id);
    expect($fresh->currentTeam->id)->toBe($teamA->id);
});

it('leaves no current team when the user\'s only team is deleted', function () {
    $onlyTeam = $this->user->currentTeam;

    Team::find($onlyTeam->id)->delete();

    $fresh = User::find($this->user->id);

    // Graceful "or none" fallback: current team is null and nothing crashes.
    expect($fresh->current_team_id)->toBeNull();
    expect($fresh->currentTeam)->toBeNull();
    expect($fresh->getTeams())->toHaveCount(0);
});
