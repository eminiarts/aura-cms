<?php

use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team deletion cleanup is teams-on only.');
    }

    $this->actingAs($this->user = createSuperAdmin());
});

it('removes a deleted team\'s Memberships and Team Roles but never the shared global rows', function () {
    // A secondary team with its own Team Role (a Shadow) and a member holding it.
    $team = Team::create(['name' => 'Doomed Team', 'user_id' => $this->user->id]);

    $shadowRole = Role::create([
        'name' => 'Team Admin',
        'slug' => 'admin', // Shadows the global admin by slug within this team.
        'team_id' => $team->id,
        'super_admin' => true,
        'permissions' => [],
    ]);

    $member = User::factory()->create(['current_team_id' => $team->id]);
    $member->roles()->attach($shadowRole->id, ['team_id' => $team->id]);

    $globalAdmin = globalAdminRole();

    // Sanity: memberships and the shadow row exist pre-deletion.
    expect(DB::table('user_role')->where('team_id', $team->id)->count())->toBeGreaterThan(0);
    expect(Role::withoutGlobalScopes()->where('id', $shadowRole->id)->exists())->toBeTrue();

    $team->delete();

    // The team's Memberships are gone.
    expect(DB::table('user_role')->where('team_id', $team->id)->count())->toBe(0);

    // The team's own Team Role (Shadow) dies with the team.
    expect(Role::withoutGlobalScopes()->where('id', $shadowRole->id)->exists())->toBeFalse();

    // The shared global admin role is never touched.
    expect(Role::withoutGlobalScopes()->whereKey($globalAdmin->id)->whereNull('team_id')->exists())->toBeTrue();
});

it('deletes all scoped options and invalidates warm caches when reassigning members', function () {
    $deletedTeam = $this->user->currentTeam;
    $deletedTeam->updateOption('cleanup', ['team' => 'stale']);
    $this->user->updateOption('cleanup', ['user' => 'stale']);

    expect($deletedTeam->getOption('cleanup'))->toBe(['team' => 'stale'])
        ->and($this->user->getOption('cleanup'))->toBe(['user' => 'stale']);

    $replacement = Team::create(['name' => 'Replacement Team']);
    expect($this->user->switchTeam($deletedTeam))->toBeTrue();

    $deletedTeam->delete();

    $freshUser = User::withoutGlobalScopes()->findOrFail($this->user->id);

    expect($freshUser->current_team_id)->toBe($replacement->id)
        ->and(Option::withoutGlobalScopes()->where('team_id', $deletedTeam->id)->count())->toBe(0)
        ->and($deletedTeam->getOption('cleanup'))->toBeNull()
        ->and($this->user->getOption('cleanup'))->toBeNull()
        ->and($freshUser->getTeams()->pluck('id'))->not->toContain($deletedTeam->id);
});

it('deletes all scoped options and clears current team without a replacement', function () {
    $deletedTeam = $this->user->currentTeam;
    $deletedTeam->updateOption('cleanup', ['team' => 'stale']);
    $this->user->updateOption('cleanup', ['user' => 'stale']);

    expect($deletedTeam->getOption('cleanup'))->toBe(['team' => 'stale'])
        ->and($this->user->getOption('cleanup'))->toBe(['user' => 'stale']);

    $deletedTeam->delete();

    $freshUser = User::withoutGlobalScopes()->findOrFail($this->user->id);

    expect($freshUser->current_team_id)->toBeNull()
        ->and(Option::withoutGlobalScopes()->where('team_id', $deletedTeam->id)->count())->toBe(0)
        ->and($deletedTeam->getOption('cleanup'))->toBeNull()
        ->and($this->user->getOption('cleanup'))->toBeNull()
        ->and($freshUser->getTeams())->toHaveCount(0);
});
