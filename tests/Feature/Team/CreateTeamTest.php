<?php

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Team Creation Authorization', function () {
    it('cannot create team without global admin permission', function () {
        $teams = $this->user->getTeams();
        expect($teams->count())->toBe(1);
        expect(Role::count())->toBe(1);

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'Test Team')
            ->set('form.fields.description', 'Test Description')
            ->call('save')
            ->assertHasNoErrors();
    })->throws(\Exception::class);

    it('can create team as global admin', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $teams = $this->user->getTeams();
        expect($teams->count())->toBe(1);
        expect(Role::count())->toBe(1);

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'Test Team')
            ->set('form.fields.description', 'Test Description')
            ->call('save')
            ->assertHasNoErrors();

        $this->user->refresh();
        $team = $this->user->fresh()->currentTeam;

        expect($this->user->fresh()->teams()->count())->toBe(2);
        expect($this->user->getTeams()->count())->toBe(2);
        expect($team->name)->toBe('Test Team');
        expect($team->description)->toBe('Test Description');
    });

    it('creates super_admin role when team is created via Livewire', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $initialRoleCount = Role::withoutGlobalScopes()->count();

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'New Team')
            ->call('save')
            ->assertHasNoErrors();

        // A new super_admin role should be created for the new team
        expect(Role::withoutGlobalScopes()->count())->toBe($initialRoleCount + 1);

        $newTeam = Team::where('name', 'New Team')->first();
        $superAdminRole = Role::withoutGlobalScopes()
            ->where('team_id', $newTeam->id)
            ->where('slug', 'super_admin')
            ->first();

        expect($superAdminRole)->not->toBeNull();
        expect($superAdminRole->super_admin)->toBeTrue();
    });
});

describe('Team Switching', function () {
    it('can switch to a different team', function () {
        $team = Team::create([
            'name' => 'Test Team 2',
            'description' => 'Test Description 2',
            'user_id' => $this->user->id,
        ]);

        $this->put(route('aura.current-team.update', ['team_id' => $team->id]))
            ->assertRedirect(route('aura.dashboard'));

        expect($this->user->fresh()->currentTeam->id)->toBe($team->id);
    });

    it('updates user current_team_id when switching teams', function () {
        $originalTeamId = $this->user->current_team_id;

        $newTeam = Team::create([
            'name' => 'Switch Test Team',
            'user_id' => $this->user->id,
        ]);

        $this->put(route('aura.current-team.update', ['team_id' => $newTeam->id]));

        $this->user->refresh();
        expect($this->user->current_team_id)->toBe($newTeam->id);
        expect($this->user->current_team_id)->not->toBe($originalTeamId);
    });

    it('cannot switch to a team user does not belong to', function () {
        // Create a team without the current user
        $otherUser = User::factory()->create();
        auth()->login($otherUser);

        $otherTeam = Team::create([
            'name' => 'Other Team',
            'user_id' => $otherUser->id,
        ]);

        auth()->login($this->user);

        // Attempt to switch to team user doesn't belong to
        $this->put(route('aura.current-team.update', ['team_id' => $otherTeam->id]))
            ->assertForbidden();
    });
});

describe('Team Deletion', function () {
    it('can delete a team', function () {
        $team = Team::create([
            'name' => 'Team to be deleted',
            'description' => 'This team should be deleted in this test',
            'user_id' => $this->user->id,
        ]);

        expect(Team::where('name', 'Team to be deleted')->first())->not()->toBeNull();

        $initialCount = $this->user->fresh()->teams()->count();

        $team->delete();

        expect(Team::where('name', 'Team to be deleted')->first())->toBeNull();
        expect($this->user->fresh()->teams()->count())->toBe($initialCount - 1);
    });

    it('updates current_team_id when current team is deleted', function () {
        $team = Team::create([
            'name' => 'Team to be deleted',
            'description' => 'This team should be deleted in this test',
            'user_id' => $this->user->id,
        ]);

        $this->user->current_team_id = $team->id;
        $this->user->save();

        expect($this->user->fresh()->current_team_id)->toBe($team->id);

        $initialCount = $this->user->fresh()->teams()->count();

        $team->delete();

        expect(Team::where('name', 'Team to be deleted')->first())->toBeNull();
        expect($this->user->fresh()->teams()->count())->toBe($initialCount - 1);
        expect($this->user->fresh()->current_team_id)->not()->toBe($team->id);
        expect($this->user->fresh()->current_team_id)->toBe($this->user->fresh()->teams()->first()->id);
    });

    it('sets current_team_id to first available team after deletion', function () {
        // Create multiple teams
        $team1 = Team::first();
        $team2 = Team::create([
            'name' => 'Second Team',
            'user_id' => $this->user->id,
        ]);

        $this->user->current_team_id = $team2->id;
        $this->user->save();

        $team2->delete();

        $this->user->refresh();
        expect($this->user->current_team_id)->toBe($team1->id);
    });
});

describe('Team Validation', function () {
    it('requires a name to create a team', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', '')
            ->set('form.fields.description', 'Description')
            ->call('save')
            ->assertHasErrors(['form.fields.name']);
    });

    it('allows empty description', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'Team Without Description')
            ->set('form.fields.description', '')
            ->call('save')
            ->assertHasNoErrors();

        expect(Team::where('name', 'Team Without Description')->first())->not->toBeNull();
    });
});

describe('Team User Association', function () {
    it('associates creating user with new team', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'Association Test Team')
            ->call('save')
            ->assertHasNoErrors();

        $newTeam = Team::where('name', 'Association Test Team')->first();

        expect($newTeam->user_id)->toBe($this->user->id);
        expect($newTeam->users->pluck('id')->toArray())->toContain($this->user->id);
    });

    it('sets new team as current team for creating user', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $originalTeamId = $this->user->current_team_id;

        livewire(Create::class, ['slug' => 'team'])
            ->set('form.fields.name', 'New Current Team')
            ->call('save')
            ->assertHasNoErrors();

        $this->user->refresh();
        expect($this->user->current_team_id)->not->toBe($originalTeamId);
    });
});
