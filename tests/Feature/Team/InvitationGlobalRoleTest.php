<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Invitations are teams-on only.');
    }

    $this->actingAs($this->user = createSuperAdmin());
    config(['aura.auth.user_invitations' => true]);
});

it('lets an existing user accept an invitation carrying a global role id', function () {
    $team = $this->user->currentTeam;
    $globalAdmin = globalAdminRole();

    $existingUser = User::factory()->create([
        'email' => 'existing-global@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $existingUser->email,
        'role' => $globalAdmin->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->actingAs($existingUser)
        ->get($url)
        ->assertRedirect(route('aura.dashboard'));

    // The Membership records the team and points at the shared global role.
    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $globalAdmin->id,
    ]);

    $existingUser->refresh();
    expect($existingUser->current_team_id)->toBe($team->id);
    expect($existingUser->isSuperAdmin())->toBeTrue();
});

it('lets a new user register through an invitation carrying a global role id', function () {
    $team = Team::first();
    $globalAdmin = globalAdminRole();

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invited-global@example.com',
        'role' => $globalAdmin->id,
    ]);

    $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

    auth()->logout();

    $this->post($url, [
        'name' => 'Invited Global',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(config('aura.auth.redirect'));

    $user = User::where('email', 'invited-global@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->current_team_id)->toBe($team->id);

    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role_id' => $globalAdmin->id,
    ]);

    $user->refresh();
    expect($user->isSuperAdmin())->toBeTrue();
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
});

it('lets an existing user accept an invitation carrying a team Shadow role id', function () {
    $team = $this->user->currentTeam;

    // A Global Role and the current team's Shadow of it (same slug, team-owned).
    // saveQuietly keeps the global row's team_id null (no auto-team from the
    // acting user's context).
    $global = Role::withoutGlobalScopes()->newModelInstance([
        'name' => 'Global Editor',
        'slug' => 'editor',
        'super_admin' => false,
        'permissions' => [],
        'team_id' => null,
    ]);
    $global->saveQuietly();

    $shadow = Role::withoutGlobalScopes()->create([
        'name' => 'Team Editor',
        'slug' => 'editor',
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $team->id,
    ]);

    $existingUser = User::factory()->create([
        'email' => 'existing-shadow@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $existingUser->email,
        'role' => $shadow->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->actingAs($existingUser)
        ->get($url)
        ->assertRedirect(route('aura.dashboard'));

    // The Membership carries the Shadow (team) role id, not the global row.
    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $shadow->id,
    ]);
    $this->assertDatabaseMissing('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $global->id,
    ]);

    expect($existingUser->fresh()->current_team_id)->toBe($team->id);
});

it('still refuses an invitation whose role belongs to another team', function () {
    $team = $this->user->currentTeam;
    $otherTeam = Team::factory()->createQuietly();
    $otherTeamRole = Role::create([
        'name' => 'Other Role',
        'slug' => 'other-role',
        'team_id' => $otherTeam->id,
        'super_admin' => false,
        'permissions' => [],
    ]);

    $existingUser = User::factory()->create([
        'email' => 'existing-cross@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $existingUser->email,
        'role' => $otherTeamRole->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->actingAs($existingUser)
        ->get($url)
        ->assertNotFound();

    $this->assertDatabaseMissing('user_role', [
        'user_id' => $existingUser->id,
        'role_id' => $otherTeamRole->id,
    ]);
});
