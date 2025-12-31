<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('team can be deleted as a global admin', function () {
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return true;
    });

    // Teams
    $teams = $this->user->getTeams();

    // Expect 1 team
    expect($teams->count())->toBe(1);

    expect(Role::count())->toBe(1);

    $team = Team::first();

    Aura::fake();
    Aura::setModel($team);

    $component = livewire(Edit::class, ['id' => $team->id])
        ->call('singleAction', 'deleteAction')
        ->assertHasNoErrors();

    $team->refresh();

    expect($team->deleted_at)->not()->toBeNull();

    expect(Team::count())->toBe(0);
});

test('team can not be deleted when not a global admin', function () {
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return false;
    });

    // Teams
    $teams = $this->user->getTeams();

    // Expect 1 team
    expect($teams->count())->toBe(1);

    expect(Role::count())->toBe(1);

    $team = Team::first();

    Aura::fake();
    Aura::setModel($team);

    $component = livewire(Edit::class, ['id' => $team->id])
        ->call('singleAction', 'deleteAction')
        ->assertForbidden();

    $team->refresh();

    expect($team->deleted_at)->toBeNull();

    expect(Team::count())->toBe(1);
});

test('team owner cannot delete team when not global admin due to conditional logic', function () {
    // The user is a team owner but the deleteAction has conditional_logic that requires isAuraGlobalAdmin
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return false;
    });

    $team = Team::first();

    // Verify user owns the team
    expect($team->user_id)->toBe($this->user->id);
    expect($this->user->ownsTeam($team))->toBeTrue();

    Aura::fake();
    Aura::setModel($team);

    // The action's conditional_logic blocks execution even though TeamPolicy would allow it
    $component = livewire(Edit::class, ['id' => $team->id])
        ->call('singleAction', 'deleteAction')
        ->assertForbidden();

    $team->refresh();

    expect($team->deleted_at)->toBeNull();
    expect(Team::count())->toBe(1);
});

test('delete action is defined in team actions', function () {
    $team = Team::first();

    // getActions returns all defined actions (unfiltered)
    $actions = $team->getActions();
    expect($actions)->toHaveKey('deleteAction');
    expect($actions['deleteAction'])->toHaveKey('conditional_logic');
});

test('delete action conditional logic returns true for global admin', function () {
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return true;
    });

    $team = Team::first();

    $actions = $team->getActions();
    $conditionalLogic = $actions['deleteAction']['conditional_logic'];

    expect($conditionalLogic())->toBeTrue();
});

test('delete action conditional logic returns false for non-global admin', function () {
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return false;
    });

    $team = Team::first();

    $actions = $team->getActions();
    $conditionalLogic = $actions['deleteAction']['conditional_logic'];

    expect($conditionalLogic())->toBeFalse();
});
