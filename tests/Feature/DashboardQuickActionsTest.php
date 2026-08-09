<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    config(['aura.teams' => true]);
});

test('team quick action is hidden when teams are disabled', function () {
    config(['aura.teams' => false]);

    $this->actingAs(User::factory()->create(['current_team_id' => null]));

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('authorized users can edit their current team from quick actions', function () {
    $user = createSuperAdmin();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Edit Team')
        ->assertSee(route('aura.team.edit', ['id' => $team->getKey()]));
});

test('team quick action is hidden when the current team relationship is absent', function () {
    $user = createGlobalAdmin(['current_team_id' => null]);

    $this->actingAs($user);

    expect($user->teams()->exists())->toBeFalse()
        ->and($user->getAttribute('current_team_id'))->toBeNull();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('team quick action is hidden without update authorization', function () {
    $owner = User::factory()->create(['current_team_id' => null]);
    $team = Team::factory()->createQuietly(['user_id' => $owner->getKey()]);
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user);

    expect($user->can('update', $team))->toBeFalse();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});
