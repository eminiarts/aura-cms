<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Team Delete Action Authorization', function () {
    it('can delete team as global admin', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $teams = $this->user->getTeams();
        expect($teams->count())->toBe(1);
        expect(Role::count())->toBe(1);

        $team = Team::first();

        Aura::fake();
        Aura::setModel($team);

        livewire(Edit::class, ['id' => $team->id])
            ->call('singleAction', 'deleteAction')
            ->assertHasNoErrors();

        $team->refresh();

        expect($team->deleted_at)->not()->toBeNull();
        expect(Team::count())->toBe(0);
    });

    it('cannot delete team when not global admin', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => false);

        $teams = $this->user->getTeams();
        expect($teams->count())->toBe(1);
        expect(Role::count())->toBe(1);

        $team = Team::first();

        Aura::fake();
        Aura::setModel($team);

        livewire(Edit::class, ['id' => $team->id])
            ->call('singleAction', 'deleteAction')
            ->assertForbidden();

        $team->refresh();

        expect($team->deleted_at)->toBeNull();
        expect(Team::count())->toBe(1);
    });

    it('team owner cannot delete team when not global admin due to conditional logic', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => false);

        $team = Team::first();

        // Verify user owns the team
        expect($team->user_id)->toBe($this->user->id);
        expect($this->user->ownsTeam($team))->toBeTrue();

        Aura::fake();
        Aura::setModel($team);

        // The action's conditional_logic blocks execution even though TeamPolicy would allow it
        livewire(Edit::class, ['id' => $team->id])
            ->call('singleAction', 'deleteAction')
            ->assertForbidden();

        $team->refresh();

        expect($team->deleted_at)->toBeNull();
        expect(Team::count())->toBe(1);
    });
});

describe('Team Delete Action Configuration', function () {
    it('has delete action defined in team actions', function () {
        $team = Team::first();

        $actions = $team->getActions();
        expect($actions)->toHaveKey('deleteAction');
        expect($actions['deleteAction'])->toHaveKey('conditional_logic');
        expect($actions['deleteAction'])->toHaveKey('label');
        expect($actions['deleteAction']['label'])->toBe('Delete');
    });

    it('delete action has correct icon and styling', function () {
        $team = Team::first();

        $actions = $team->getActions();
        expect($actions['deleteAction'])->toHaveKey('icon-view');
        expect($actions['deleteAction'])->toHaveKey('class');
        expect($actions['deleteAction']['class'])->toContain('text-red');
    });

    it('delete action conditional logic returns true for global admin', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $team = Team::first();

        $actions = $team->getActions();
        $conditionalLogic = $actions['deleteAction']['conditional_logic'];

        expect($conditionalLogic())->toBeTrue();
    });

    it('delete action conditional logic returns false for non-global admin', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => false);

        $team = Team::first();

        $actions = $team->getActions();
        $conditionalLogic = $actions['deleteAction']['conditional_logic'];

        expect($conditionalLogic())->toBeFalse();
    });
});

describe('Team Delete Side Effects', function () {
    it('deletes team invitations when team is deleted', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $team = Team::first();

        // Create some invitations
        $team->teamInvitations()->create([
            'email' => 'test1@example.com',
            'role' => Role::first()->id,
        ]);
        $team->teamInvitations()->create([
            'email' => 'test2@example.com',
            'role' => Role::first()->id,
        ]);

        expect($team->teamInvitations()->count())->toBe(2);

        Aura::fake();
        Aura::setModel($team);

        livewire(Edit::class, ['id' => $team->id])
            ->call('singleAction', 'deleteAction')
            ->assertHasNoErrors();

        // Invitations should be deleted with the team
        expect(TeamInvitation::where('team_id', $team->id)->count())->toBe(0);
    });

    it('deletes team meta when team is deleted', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $team = Team::first();

        // Check if meta table exists before testing
        if (! Schema::hasTable('postmeta')) {
            $this->markTestSkipped('postmeta table does not exist');
        }

        Aura::fake();
        Aura::setModel($team);

        livewire(Edit::class, ['id' => $team->id])
            ->call('singleAction', 'deleteAction')
            ->assertHasNoErrors();

        // Team meta should be deleted
        expect(DB::table('postmeta')
            ->where('post_id', $team->id)
            ->count())->toBe(0);
    });

    it('updates users current_team_id when their team is deleted', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        // Create a second team so user has somewhere to go
        $team1 = Team::first();
        $team2 = Team::create([
            'name' => 'Backup Team',
            'user_id' => $this->user->id,
        ]);

        // Set team2 as current (will be deleted)
        $this->user->current_team_id = $team2->id;
        $this->user->save();

        expect($this->user->fresh()->current_team_id)->toBe($team2->id);

        Aura::fake();
        Aura::setModel($team2);

        livewire(Edit::class, ['id' => $team2->id])
            ->call('singleAction', 'deleteAction')
            ->assertHasNoErrors();

        // User's current_team_id should be updated to first available team
        $this->user->refresh();
        expect($this->user->current_team_id)->not->toBe($team2->id);
        expect($this->user->current_team_id)->toBe($team1->id);
    });
});

describe('Team Delete Policy', function () {
    it('allows global admin to delete any team', function () {
        Gate::define('AuraGlobalAdmin', fn (User $user) => true);

        $team = Team::first();

        expect($this->user->can('delete', $team))->toBeTrue();
    });

    it('allows team owner to delete their team', function () {
        $team = Team::first();

        expect($this->user->ownsTeam($team))->toBeTrue();
        expect($this->user->can('delete', $team))->toBeTrue();
    });

    it('denies non-owner from deleting team', function () {
        // Create another user who doesn't own the team
        $otherUser = User::factory()->create();

        $team = Team::first();

        expect($otherUser->ownsTeam($team))->toBeFalse();
        expect($otherUser->can('delete', $team))->toBeFalse();
    });
});
