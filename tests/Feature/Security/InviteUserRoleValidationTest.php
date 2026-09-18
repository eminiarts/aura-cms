<?php

use Aura\Base\Livewire\InviteUser;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;

use function Pest\Livewire\livewire;

/**
 * `form.fields.role` used to be validated as `required` only, so any role id the
 * client sent was accepted — including a role owned by another team and a role
 * carrying super_admin. The invitation then became a cheaper escalation path
 * than the Roles field, which guards both cases.
 */
beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Invitations are a teams-on feature.');
    }
});

/** A delegated inviter: may invite users, but is not a Super Admin. */
function delegatedInviter(): User
{
    $owner = User::factory()->create();
    $team = Team::factory()->createQuietly(['user_id' => $owner->id]);

    $role = Role::withoutGlobalScopes()->create([
        'type' => 'Role',
        'name' => 'Inviter',
        'slug' => 'inviter-'.uniqid(),
        'super_admin' => false,
        'permissions' => ['invite-users' => true, 'viewAny-user' => true, 'view-user' => true],
        'team_id' => $team->id,
    ]);

    $inviter = User::factory()->create();
    $inviter->roles()->attach($role->id, ['team_id' => $team->id]);
    $inviter->forceFill(['current_team_id' => $team->id])->save();

    return $inviter->refresh();
}

test('a non super admin inviter cannot invite with a super admin role', function () {
    $inviter = delegatedInviter();
    $this->actingAs($inviter);

    $superAdminRole = Role::withoutGlobalScopes()->create([
        'type' => 'Role',
        'name' => 'Team Admin',
        'slug' => 'team-admin-'.uniqid(),
        'super_admin' => true,
        'permissions' => [],
        'team_id' => $inviter->current_team_id,
    ]);

    livewire(InviteUser::class)
        ->set('form.fields.email', 'escalate@example.com')
        ->set('form.fields.role', $superAdminRole->id)
        ->call('save')
        ->assertHasErrors('form.fields.role');

    expect(TeamInvitation::withoutGlobalScopes()->count())->toBe(0);
});

test('an inviter cannot invite with a role owned by another team', function () {
    $inviter = delegatedInviter();

    $foreign = foreignTeam();
    $foreignRole = Role::withoutGlobalScopes()->create([
        'type' => 'Role',
        'name' => 'Foreign Role',
        'slug' => 'foreign-'.uniqid(),
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $foreign->id,
    ]);

    $this->actingAs($inviter);

    livewire(InviteUser::class)
        ->set('form.fields.email', 'crossteam@example.com')
        ->set('form.fields.role', $foreignRole->id)
        ->call('save')
        ->assertHasErrors('form.fields.role');

    expect(TeamInvitation::withoutGlobalScopes()->count())->toBe(0);
});

test('a super admin may still invite with a super admin role from the team catalog', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $role = Role::shadowResolvedForCurrentTeam()->get()->firstWhere('super_admin', true);

    expect($role)->not->toBeNull();

    livewire(InviteUser::class)
        ->set('form.fields.email', 'legit@example.com')
        ->set('form.fields.role', $role->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(TeamInvitation::withoutGlobalScopes()->count())->toBe(1);
});
