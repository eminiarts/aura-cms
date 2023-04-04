<?php

use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Team;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

test('team can be created', function () {
    // Teams
    $teams = $this->user->getTeams();

    // Expect 1 team
    expect($teams->count())->toBe(1);

    $component = livewire(Create::class, ['slug' => 'Team'])->set('post.fields.name', 'Test Team')
    ->set('post.fields.description', 'Test Description')
    ->call('save')
    ->assertHasNoErrors();

    $team = $this->user->fresh()->currentTeam;

    // User Teams Count
    expect($this->user->fresh()->teams()->count())->toBe(2);

    // Cache('user.'.$this->id.'.teams') Count
    expect($this->user->getTeams()->count())->toBe(2);

    expect($team->name)->toBe('Test Team');
    expect($team->description)->toBe('Test Description');
});

test('team can be changed', function () {
    // Create second Team
    $team = Team::create([
        'name' => 'Test Team 2',
        'description' => 'Test Description 2',
        'user_id' => $this->user->id,
    ]);

    // put request to route aura.current-team.update
    $this->put(route('aura.current-team.update', ['team_id' => $team->id]))
        ->assertRedirect(route('aura.dashboard'));

    // Assert that the user's current team has been updated...
    expect($this->user->fresh()->currentTeam->id)->toBe($team->id);
});

test('team can be deleted', function () {
})->todo();
