<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

use function Pest\Livewire\livewire;
use Illuminate\Support\Facades\Gate;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Create;

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


test('team can not be deleted as a global admin', function () {
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
        ->assertHasNoErrors();

    $team->refresh();

    expect($team->deleted_at)->toBeNull();
    
    expect(Team::count())->toBe(1);
});
