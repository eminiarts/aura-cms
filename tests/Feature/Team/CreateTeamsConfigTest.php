<?php

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Gate::define('AuraGlobalAdmin', function (User $user) {
        return $user->email === 'global-admin@test.com';
    });

    $this->policy = new TeamPolicy;
    $this->globalAdmin = User::factory()->create(['email' => 'global-admin@test.com']);
});

afterEach(function () {
    Team::$createEnabled = true;
    config(['aura.auth.create_teams' => true]);
});

test('create_teams config defaults to true', function () {
    expect(config('aura.auth.create_teams'))->toBeTrue();
});

test('create_teams config can be set to false', function () {
    config(['aura.auth.create_teams' => false]);
    expect(config('aura.auth.create_teams'))->toBeFalse();
});

test('policy allows team creation when create_teams config is true', function () {
    config(['aura.auth.create_teams' => true]);
    Team::$createEnabled = true;

    $this->actingAs($this->globalAdmin);
    expect($this->policy->create($this->globalAdmin, Team::class))->toBeTrue();
});

test('policy blocks team creation when create_teams config is false', function () {
    config(['aura.auth.create_teams' => false]);
    Team::$createEnabled = true;

    $this->actingAs($this->globalAdmin);
    expect($this->policy->create($this->globalAdmin, Team::class))->toBeFalse();
});

test('policy blocks team creation when create_teams is false even for global admin', function () {
    config(['aura.auth.create_teams' => false]);
    Team::$createEnabled = true;

    $this->actingAs($this->globalAdmin);
    expect($this->policy->create($this->globalAdmin, Team::class))->toBeFalse();
});

test('createEnabled static property takes precedence over config', function () {
    config(['aura.auth.create_teams' => true]);
    Team::$createEnabled = false;

    $this->actingAs($this->globalAdmin);
    expect($this->policy->create($this->globalAdmin, Team::class))->toBeFalse();
});

test('global admin can create team via livewire when create_teams is enabled', function () {
    config(['aura.auth.create_teams' => true]);
    Team::$createEnabled = true;

    Gate::define('AuraGlobalAdmin', function (User $user) {
        return true;
    });

    $initialTeamCount = Team::withoutGlobalScopes()->count();

    livewire(Create::class, ['slug' => 'team'])
        ->set('form.fields.name', 'New Config Team')
        ->set('form.fields.description', 'Created with config enabled')
        ->call('save')
        ->assertHasNoErrors();

    expect(Team::withoutGlobalScopes()->count())->toBe($initialTeamCount + 1);
});

test('team creation via livewire is blocked when create_teams is disabled', function () {
    config(['aura.auth.create_teams' => false]);

    Gate::define('AuraGlobalAdmin', function (User $user) {
        return true;
    });

    $initialCount = Team::withoutGlobalScopes()->count();

    livewire(Create::class, ['slug' => 'team'])
        ->assertForbidden();

    expect(Team::withoutGlobalScopes()->count())->toBe($initialCount);
});

test('team switching works regardless of create_teams config', function () {
    config(['aura.auth.create_teams' => false]);

    $team2 = Team::create([
        'name' => 'Second Team',
        'description' => 'For switching test',
        'user_id' => $this->user->id,
    ]);

    $this->put(route('aura.current-team.update', ['team_id' => $team2->id]))
        ->assertRedirect(route('aura.dashboard'));

    expect($this->user->fresh()->currentTeam->id)->toBe($team2->id);
});
