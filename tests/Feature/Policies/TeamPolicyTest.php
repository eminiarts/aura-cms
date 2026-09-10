<?php

use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team policy tests require teams enabled.');
    }

    // Define the AuraGlobalAdmin gate in the test
    Gate::define('AuraGlobalAdmin', function (User $user) {
        return in_array($user->email, [
            'bajram@eminiarts.ch',
            'enes@eminiarts.ch',
            'info@eminiarts.ch',
        ]);
    });

    $this->policy = new TeamPolicy;
    $this->team = Team::factory()->create();
    $this->regularUser = User::factory()->create();
    $this->teamOwner = User::factory()->create();
    $this->auraGlobalAdmin = User::factory()->create(['email' => 'bajram@eminiarts.ch']);

    // Store original static property values for cleanup
    $this->originalCreateEnabled = Team::$createEnabled;
    $this->originalEditEnabled = Team::$editEnabled;
    $this->originalIndexViewEnabled = Team::$indexViewEnabled;

    // Create owner role with all permissions
    $ownerRole = Role::create([
        'team_id' => $this->team->id,
        'type' => 'Role',
        'title' => 'Owner',
        'slug' => 'owner',
        'name' => 'Owner',
        'description' => 'Team Owner Role',
        'super_admin' => false,
        'permissions' => [
            'view-team' => true,
            'viewAny-team' => true,
            'create-team' => true,
            'update-team' => true,
            'delete-team' => true,
            'invite-users' => true,
            'remove-team-members' => true,
            'update-team-members' => true,
        ],
    ]);

    // Set current team and attach owner role
    $this->teamOwner->update([
        'current_team_id' => $this->team->id,
        'roles' => [$ownerRole->id],
    ]);
    $this->teamOwner->refresh();

    // Set the team owner
    $this->team->update(['user_id' => $this->teamOwner->id]);

    // Attach team members with role
    $this->team->users()->syncWithPivotValues($this->teamOwner->id, [
        'role_id' => $ownerRole->id,
    ]);
});

afterEach(function () {
    // Reset static properties to their original values to prevent test pollution
    Team::$createEnabled = $this->originalCreateEnabled ?? true;
    Team::$editEnabled = $this->originalEditEnabled ?? true;
    Team::$indexViewEnabled = $this->originalIndexViewEnabled ?? true;
});

test('super admin can add team members', function () {
    $this->actingAs($user = createSuperAdmin());
    $team = Team::withoutGlobalScopes()->findOrFail($user->current_team_id);

    expect($this->policy->addTeamMember($user, $team))->toBeTrue();
});

test('super admin cannot add team members to a foreign team', function () {
    $this->actingAs($user = createSuperAdmin());
    $foreignTeam = Team::factory()->createQuietly([
        'user_id' => User::factory()->create()->id,
    ]);

    expect($this->policy->addTeamMember($user, $foreignTeam))->toBeFalse();
});

test('super admin can add team members to a team where they are a member from another current team', function () {
    $currentTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    $targetTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    $user = User::factory()->create(['current_team_id' => $currentTeam->id]);

    $currentRole = Role::withoutGlobalScopes()->create([
        'team_id' => $currentTeam->id,
        'type' => 'Role',
        'title' => 'Current Team Member',
        'slug' => 'current-team-member',
        'name' => 'Current Team Member',
        'description' => 'Current team role.',
        'super_admin' => false,
        'permissions' => [],
    ]);
    $targetRole = Role::withoutGlobalScopes()->create([
        'team_id' => $targetTeam->id,
        'type' => 'Role',
        'title' => 'Target Team Super Admin',
        'slug' => 'target-team-super-admin',
        'name' => 'Target Team Super Admin',
        'description' => 'Target team super admin role.',
        'super_admin' => true,
        'permissions' => [],
    ]);

    $user->roles()->attach($currentRole->id, ['team_id' => $currentTeam->id]);
    $user->roles()->attach($targetRole->id, ['team_id' => $targetTeam->id]);
    $user->refresh();

    $this->actingAs($user);

    expect($this->policy->addTeamMember($user, $targetTeam))->toBeTrue();
});

test('aura global admin can add team members', function () {
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->addTeamMember($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can add team members', function () {
    $this->actingAs($this->teamOwner);
    expect($this->policy->addTeamMember($this->teamOwner, $this->team))->toBeTrue();
});

test('regular user cannot add team members', function () {
    $this->actingAs($this->regularUser);
    expect($this->policy->addTeamMember($this->regularUser, $this->team))->toBeFalse();
});

test('only aura global admin or super admin can create teams when enabled', function () {
    Team::$createEnabled = true;

    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->create($this->auraGlobalAdmin, Team::class))->toBeTrue();

    $superAdmin = createSuperAdmin();
    $this->actingAs($superAdmin);
    expect($this->policy->create($superAdmin, Team::class))->toBeFalse();

    $this->actingAs($this->regularUser);
    expect($this->policy->create($this->regularUser, Team::class))->toBeFalse();

    $this->actingAs($this->teamOwner);
    expect($this->policy->create($this->teamOwner, Team::class))->toBeFalse();
});

test('no one can create teams when disabled', function () {
    Team::$createEnabled = false;

    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->create($this->auraGlobalAdmin, Team::class))->toBeFalse()
        ->and($this->policy->create($this->regularUser, Team::class))->toBeFalse()
        ->and($this->policy->create($this->teamOwner, Team::class))->toBeFalse();
});

test('aura global admin can delete teams', function () {
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->delete($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can delete their team', function () {
    $this->actingAs($this->teamOwner);
    expect($this->policy->delete($this->teamOwner, $this->team))->toBeTrue();
});

test('regular user cannot delete teams', function () {
    $this->actingAs($this->regularUser);
    expect($this->policy->delete($this->regularUser, $this->team))->toBeFalse();
});

test('aura global admin can invite users', function () {
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->inviteUsers($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can invite users', function () {
    $this->actingAs($this->teamOwner);
    expect($this->policy->inviteUsers($this->teamOwner, $this->team))->toBeTrue();
});

test('user with invite permission can invite users', function () {
    $userWithPermission = User::factory()->create();

    $memberRole = Role::create([
        'team_id' => $this->team->id,
        'type' => 'Role',
        'title' => 'Member',
        'slug' => 'member',
        'name' => 'Member',
        'description' => 'Team Member Role',
        'super_admin' => false,
        'permissions' => [
            'invite-users-team' => true,
            'view-team' => true,
        ],
    ]);

    // Set current team
    $userWithPermission->update([
        'current_team_id' => $this->team->id,
        'roles' => [$memberRole->id],
    ]);
    $userWithPermission->refresh();

    // Attach team member with role
    $this->team->users()->syncWithPivotValues($userWithPermission->id, [
        'role_id' => $memberRole->id,
    ]);

    $this->actingAs($userWithPermission);
    expect($this->policy->inviteUsers($userWithPermission, $this->team))->toBeTrue();
});

test('a current-team invitation permission cannot invite users to a foreign team', function () {
    $user = User::factory()->create(['current_team_id' => $this->team->id]);
    $role = Role::withoutGlobalScopes()->create([
        'team_id' => $this->team->id,
        'type' => 'Role',
        'title' => 'Inviter',
        'slug' => 'current-team-inviter',
        'name' => 'Current Team Inviter',
        'description' => 'Can invite users in the current team.',
        'super_admin' => false,
        'permissions' => ['invite-users-team' => true],
    ]);
    $user->roles()->attach($role->id, ['team_id' => $this->team->id]);
    $foreignTeam = Team::factory()->createQuietly([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($user->refresh());

    expect($this->policy->inviteUsers($user, $foreignTeam))->toBeFalse();
});

test('a target-team invitation permission applies even when another team is current', function () {
    $currentTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    $targetTeam = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
    $user = User::factory()->create(['current_team_id' => $currentTeam->id]);

    $currentRole = Role::withoutGlobalScopes()->create([
        'team_id' => $currentTeam->id,
        'type' => 'Role',
        'title' => 'Current Team Member',
        'slug' => 'current-team-member',
        'name' => 'Current Team Member',
        'description' => 'Current team role.',
        'super_admin' => false,
        'permissions' => [],
    ]);
    $targetRole = Role::withoutGlobalScopes()->create([
        'team_id' => $targetTeam->id,
        'type' => 'Role',
        'title' => 'Target Team Inviter',
        'slug' => 'target-team-inviter',
        'name' => 'Target Team Inviter',
        'description' => 'Can invite users in the target team.',
        'super_admin' => false,
        'permissions' => ['invite-users-team' => true],
    ]);

    $user->roles()->attach($currentRole->id, ['team_id' => $currentTeam->id]);
    $user->roles()->attach($targetRole->id, ['team_id' => $targetTeam->id]);
    $user->refresh();

    $this->actingAs($user);

    expect($this->policy->inviteUsers($user, $targetTeam))->toBeTrue();
});

test('regular user cannot invite users', function () {
    $this->actingAs($this->regularUser);
    expect($this->policy->inviteUsers($this->regularUser, $this->team))->toBeFalse();
});

test('aura global admin can remove team members', function () {
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->removeTeamMember($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can remove team members', function () {
    $this->actingAs($this->teamOwner);
    expect($this->policy->removeTeamMember($this->teamOwner, $this->team))->toBeTrue();
});

test('regular user cannot remove team members', function () {
    $this->actingAs($this->regularUser);
    expect($this->policy->removeTeamMember($this->regularUser, $this->team))->toBeFalse();
});

test('aura global admin can update team when enabled', function () {
    Team::$editEnabled = true;
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->update($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can update their team when enabled', function () {
    Team::$editEnabled = true;
    $this->actingAs($this->teamOwner);
    expect($this->policy->update($this->teamOwner, $this->team))->toBeTrue();
});

test('no one can update team when disabled', function () {
    Team::$editEnabled = false;

    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->update($this->auraGlobalAdmin, $this->team))->toBeFalse()
        ->and($this->policy->update($this->regularUser, $this->team))->toBeFalse();
});

test('aura global admin can update team member permissions', function () {
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->updateTeamMember($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can update team member permissions', function () {
    $this->actingAs($this->teamOwner);
    expect($this->policy->updateTeamMember($this->teamOwner, $this->team))->toBeTrue();
});

test('regular user cannot update team member permissions', function () {
    $this->actingAs($this->regularUser);
    expect($this->policy->updateTeamMember($this->regularUser, $this->team))->toBeFalse();
});

test('team member can view team', function () {
    $teamMember = User::factory()->create();

    $memberRole = Role::create([
        'team_id' => $this->team->id,
        'type' => 'Role',
        'title' => 'Member',
        'slug' => 'member',
        'name' => 'Member',
        'description' => 'Team Member Role',
        'super_admin' => false,
        'permissions' => ['view-team' => true],
    ]);

    $this->team->users()->syncWithPivotValues($teamMember->id, [
        'role_id' => $memberRole->id,
    ]);

    $this->actingAs($teamMember);
    expect($this->policy->view($teamMember, $this->team))->toBeTrue();
});

test('non team member cannot view team', function () {
    $nonTeamMember = User::factory()->create();
    $this->actingAs($nonTeamMember);
    expect($this->policy->view($nonTeamMember, $this->team))->toBeFalse();
});

test('aura global admin can view a team without a membership', function () {
    $this->actingAs($this->auraGlobalAdmin);

    expect($this->policy->view($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('aura global admin can view any team when enabled', function () {
    Team::$indexViewEnabled = true;
    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->viewAny($this->auraGlobalAdmin, $this->team))->toBeTrue();
});

test('team owner can view any team when enabled', function () {
    Team::$indexViewEnabled = true;
    $this->actingAs($this->teamOwner);
    expect($this->policy->viewAny($this->teamOwner, $this->team))->toBeTrue();
});

test('no one can view any team when disabled', function () {
    Team::$indexViewEnabled = false;

    $this->actingAs($this->auraGlobalAdmin);
    expect($this->policy->viewAny($this->auraGlobalAdmin, $this->team))->toBeFalse()
        ->and($this->policy->viewAny($this->regularUser, $this->team))->toBeFalse();
});
