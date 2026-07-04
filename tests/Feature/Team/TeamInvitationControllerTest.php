<?php

use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team invitation controller tests require teams enabled.');
    }

    $this->actingAs($this->user = createSuperAdmin());
    config(['aura.auth.user_invitations' => true]);
});

it('existing user can accept a team invitation', function () {
    $team = $this->user->currentTeam;
    $role = Role::first();
    $existingUser = User::factory()->create([
        'email' => 'existing-accept@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $existingUser->email,
        'role' => $role->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->actingAs($existingUser)
        ->get($url)
        ->assertRedirect(route('aura.dashboard'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $role->id,
    ]);

    expect($existingUser->fresh()->current_team_id)->toBe($team->id);
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
});

it('rejects accepting an invitation with a different authenticated email', function () {
    $team = $this->user->currentTeam;
    $role = Role::first();
    $otherUser = User::factory()->create([
        'email' => 'wrong-user@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invited-user@example.com',
        'role' => $role->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->actingAs($otherUser)
        ->get($url)
        ->assertForbidden();

    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
    expect(DB::table('user_role')->where('team_id', $team->id)->where('user_id', $otherUser->id)->exists())->toBeFalse();
});

it('revokes a pending team invitation', function () {
    $team = $this->user->currentTeam;
    $role = Role::first();
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'revoke@example.com',
        'role' => $role->id,
    ]);

    $this->from(route('aura.dashboard'))
        ->delete(route('aura.team-invitations.destroy', ['team' => $team, 'invitation' => $invitation]))
        ->assertRedirect(route('aura.dashboard'));

    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
});

it('resends a pending team invitation', function () {
    Mail::fake();

    $team = $this->user->currentTeam;
    $role = Role::first();
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'resend@example.com',
        'role' => $role->id,
    ]);

    $this->from(route('aura.dashboard'))
        ->post(route('aura.team-invitations.resend', ['team' => $team, 'invitation' => $invitation]))
        ->assertRedirect(route('aura.dashboard'));

    Mail::assertSent(TeamInvitationMail::class, function (TeamInvitationMail $mail) use ($invitation) {
        return $mail->hasTo('resend@example.com')
            && $mail->invitation->is($invitation);
    });
});

it('rejects an expired team invitation accept link', function () {
    $team = $this->user->currentTeam;
    $role = Role::first();
    $existingUser = User::factory()->create([
        'email' => 'expired-existing@example.com',
        'current_team_id' => null,
    ]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => $existingUser->email,
        'role' => $role->id,
    ]);

    $url = URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays(config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );

    $this->travel(8)->days();

    $this->actingAs($existingUser)
        ->get($url)
        ->assertForbidden();

    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
    expect(DB::table('user_role')->where('team_id', $team->id)->where('user_id', $existingUser->id)->exists())->toBeFalse();
});
