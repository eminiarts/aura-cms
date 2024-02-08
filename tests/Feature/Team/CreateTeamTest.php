<?php

use Aura\Base\Models\User;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\DB;

use Aura\Base\Livewire\Resource\Create;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('team can be created', function () {
    // Teams
    $teams = $this->user->getTeams();

    // Expect 1 team
    expect($teams->count())->toBe(1);
    expect(DB::table('posts')->where('type', 'Role')->count())->toBe(1);

    $component = livewire(Create::class, ['slug' => 'Team'])
        ->set('form.fields.name', 'Test Team')
        ->set('form.fields.description', 'Test Description')
        ->call('save')
        ->assertHasNoErrors();

    $this->user->refresh();

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
    // Create a team to be deleted
    $team = Team::create([
        'name' => 'Team to be deleted',
        'description' => 'This team should be deleted in this test',
        'user_id' => $this->user->id,
    ]);

    // Assert that the team was created
    expect(Team::where('name', 'Team to be deleted')->first())->not()->toBeNull();

    // Count the user's teams before deletion
    $initialCount = $this->user->fresh()->teams()->count();

    // Delete the team using the delete method
    $team->delete();

    // Assert that the team was deleted
    expect(Team::where('name', 'Team to be deleted')->first())->toBeNull();

    // Assert that the user's teams count decreased by 1
    expect($this->user->fresh()->teams()->count())->toBe($initialCount - 1);
});

test('check current users current_team_id is set correctly after deleting a team', function () {
    // Create a team to be deleted
    $team = Team::create([
        'name' => 'Team to be deleted',
        'description' => 'This team should be deleted in this test',
        'user_id' => $this->user->id,
    ]);

    // Set the created team as the current team for the user
    $this->user->current_team_id = $team->id;
    $this->user->save();

    // Assert that the team was created
    expect(Team::where('name', 'Team to be deleted')->first())->not()->toBeNull();

    // Assert that the user's current_team_id is set to the created team's id
    expect($this->user->fresh()->current_team_id)->toBe($team->id);

    // Count the user's teams before deletion
    $initialCount = $this->user->fresh()->teams()->count();

    // Delete the team using the delete method
    $team->delete();

    // Assert that the team was deleted
    expect(Team::where('name', 'Team to be deleted')->first())->toBeNull();

    // Assert that the user's teams count decreased by 1
    expect($this->user->fresh()->teams()->count())->toBe($initialCount - 1);

    // Assert that the user's current_team_id is not the deleted team's id
    expect($this->user->fresh()->current_team_id)->not()->toBe($team->id);

    // Assert that the user's current_team_id is set to the first team's id
    expect($this->user->fresh()->current_team_id)->toBe($this->user->fresh()->teams()->first()->id);
});
