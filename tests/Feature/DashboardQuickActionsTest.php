<?php

use Aura\Base\Livewire\Dashboard;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardCustomTeam extends Team
{
    public static ?string $slug = 'workspace';
}

class TestableDashboard extends Dashboard
{
    public function resolveCurrentTeamEditUrl(): ?string
    {
        return $this->currentTeamEditUrl();
    }
}

test('team quick action is hidden with the real teams-disabled schema', function () {
    if (config('aura.teams')) {
        $this->markTestSkipped('Requires the teams-disabled test configuration.');
    }

    expect(config('aura.teams'))->toBeFalsy()
        ->and(Schema::hasColumn('users', 'current_team_id'))->toBeFalse();

    $this->actingAs(createSuperAdminWithoutTeam());

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('authorized users can edit their current team from quick actions', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createSuperAdmin();
    $team = $user->currentTeam;

    $this->actingAs($user);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Edit Team')
        ->assertSee(route('aura.team.edit', ['id' => $team->getKey()]));
});

test('team quick action uses the configured team resource slug', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createSuperAdmin();

    config(['aura.resources.team' => DashboardCustomTeam::class]);

    Route::get('/admin/workspace/{id}/edit', fn (string $id): string => $id)
        ->name('aura.workspace.edit');

    $this->actingAs($user->unsetRelation('currentTeam'));

    $customTeamUrl = route('aura.workspace.edit', ['id' => $user->current_team_id]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Edit Team')
        ->assertSee($customTeamUrl)
        ->assertDontSee(route('aura.team.edit', ['id' => $user->current_team_id]));
});

test('team quick action is hidden when its configured edit route is absent', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createSuperAdmin();

    config(['aura.resources.team' => DashboardCustomTeam::class]);

    $this->actingAs($user->unsetRelation('currentTeam'));

    expect(Route::has('aura.workspace.edit'))->toBeFalse();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('team quick action is hidden when the configured team resource is unavailable', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createSuperAdmin();

    config(['aura.resources.team' => 'Missing\\TeamResource']);

    $this->actingAs($user->unsetRelation('currentTeam'));

    expect(app(TestableDashboard::class)->resolveCurrentTeamEditUrl())->toBeNull();
});

test('team quick action is hidden when the current team relationship is absent', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createGlobalAdmin(['current_team_id' => null]);

    $this->actingAs($user);

    expect($user->teams()->exists())->toBeFalse()
        ->and($user->getAttribute('current_team_id'))->toBeNull();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('team quick action is hidden when the current team reference is stale', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $user = createSuperAdmin();
    $team = $user->currentTeam;

    $team->deleteQuietly();
    $user->unsetRelation('currentTeam');

    $this->actingAs($user);

    expect($user->getAttribute('current_team_id'))->toBe($team->getKey())
        ->and($user->currentTeam)->toBeNull();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});

test('team quick action is hidden without update authorization', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Requires teams enabled.');
    }

    $owner = User::factory()->create(['current_team_id' => null]);
    $team = Team::factory()->createQuietly(['user_id' => $owner->getKey()]);
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);

    $this->actingAs($user);

    expect($user->can('update', $team))->toBeFalse();

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('Edit Team');
});
