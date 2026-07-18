<?php

use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/**
 * Cross-cutting invitation-lifecycle hardening (issue #54, user stories 8-10).
 *
 * These complement TeamInvitationControllerTest and InviteUserExtensiveTest with
 * the edges those files leave open: expiry on the new-user register path, reuse
 * after consumption, reuse after revoke, resend producing a working link, the
 * case-insensitive email match, and the "role deleted before acceptance" outcome
 * for BOTH accept paths. Every refusal is asserted at the HTTP seam as a status
 * plus unchanged data (invitation preserved, no Membership minted, no escalation).
 */
beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Invitation tests require the teams schema (teams-on only).');
    }

    $this->actingAs($this->user = createSuperAdmin());
    config(['aura.auth.user_invitations' => true]);

    // A plain (non-super-admin) Team Role to carry on invitations, so a mistaken
    // grant would be observable as an unexpected Membership rather than hidden by
    // the seeded admin role.
    $this->memberRole = Role::create([
        'team_id' => $this->user->currentTeam->id,
        'slug' => 'member',
        'type' => 'Role',
        'title' => 'Member',
        'name' => 'Member',
        'super_admin' => false,
        'permissions' => [],
    ]);
});

function acceptUrl(TeamInvitation $invitation, ?int $days = null): string
{
    return URL::temporarySignedRoute(
        'aura.team-invitations.accept',
        now()->addDays($days ?? (int) config('aura.auth.invitation_expiry')),
        ['invitation' => $invitation],
    );
}

function registerUrl($team, TeamInvitation $invitation, ?int $days = null): string
{
    return URL::temporarySignedRoute(
        'aura.invitation.register',
        now()->addDays($days ?? (int) config('aura.auth.invitation_expiry')),
        ['team' => $team, 'teamInvitation' => $invitation],
    );
}

it('rejects an expired invitation on the new-user register path (GET and POST)', function () {
    $team = $this->user->currentTeam;
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'expired-register@example.com', 'role' => $this->memberRole->id,
    ]);

    $getUrl = registerUrl($team, $invitation);
    $postUrl = registerUrl($team, $invitation);

    // The register path is guest-only; drop the super-admin session so the guest
    // middleware does not bounce the request before the signature is validated.
    auth()->logout();

    $this->travel(8)->days();

    $this->get($getUrl)->assertForbidden();

    $this->post($postUrl, [
        'name' => 'Too Late',
        'password' => 'Password123!XX',
        'password_confirmation' => 'Password123!XX',
    ])->assertForbidden();

    expect(User::withoutGlobalScopes()->where('email', 'expired-register@example.com')->exists())->toBeFalse();
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
});

it('rejects a reused accept link after the invitation was already accepted', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'reuse-accept@example.com', 'current_team_id' => null]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);

    $url = acceptUrl($invitation);

    // First click consumes the invitation.
    $this->actingAs($existingUser)->get($url)->assertRedirect(route('aura.dashboard'));
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();

    // Second click on the same (still-signed) link finds nothing to accept.
    $this->actingAs($existingUser)->get($url)->assertNotFound();
});

it('rejects a reused register link after the invitation was consumed by registration', function () {
    $team = $this->user->currentTeam;
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'reuse-register@example.com', 'role' => $this->memberRole->id,
    ]);

    $postUrl = registerUrl($team, $invitation);
    $getUrl = registerUrl($team, $invitation);

    // Guest-only path: drop the super-admin session for the registration itself.
    auth()->logout();

    $this->post($postUrl, [
        'name' => 'First Timer',
        'password' => 'Password123!XX',
        'password_confirmation' => 'Password123!XX',
    ])->assertRedirect(config('aura.auth.redirect'));

    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
    expect(User::withoutGlobalScopes()->where('email', 'reuse-register@example.com')->count())->toBe(1);

    // Registration logs the new user in; drop that session too so the reused link
    // reaches the signed route (guest middleware would otherwise bounce it first).
    auth()->logout();

    // Reusing the link (valid signature, consumed invitation) 404s on model binding.
    $this->get($getUrl)->assertNotFound();
    $this->post($getUrl, [
        'name' => 'Second Timer',
        'password' => 'Password123!XX',
        'password_confirmation' => 'Password123!XX',
    ])->assertNotFound();

    expect(User::withoutGlobalScopes()->where('email', 'reuse-register@example.com')->count())->toBe(1);
});

it('rejects the accept link after the invitation was revoked', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'revoked-accept@example.com', 'current_team_id' => null]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);

    $url = acceptUrl($invitation);

    // Super admin revokes the invitation.
    $this->delete(route('aura.team-invitations.destroy', ['team' => $team, 'invitation' => $invitation]))
        ->assertRedirect();

    $this->actingAs($existingUser)->get($url)->assertNotFound();

    expect(DB::table('user_role')->where('team_id', $team->id)->where('user_id', $existingUser->id)->exists())->toBeFalse();
});

it('rejects the register link after the invitation was revoked', function () {
    $team = $this->user->currentTeam;
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'revoked-register@example.com', 'role' => $this->memberRole->id,
    ]);

    $url = registerUrl($team, $invitation);

    $this->delete(route('aura.team-invitations.destroy', ['team' => $team, 'invitation' => $invitation]))
        ->assertRedirect();

    auth()->logout();

    $this->get($url)->assertNotFound();

    expect(User::withoutGlobalScopes()->where('email', 'revoked-register@example.com')->exists())->toBeFalse();
});

it('resends a pending invitation and the resent accept link works', function () {
    Mail::fake();

    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'resend-works@example.com', 'current_team_id' => null]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);

    $this->post(route('aura.team-invitations.resend', ['team' => $team, 'invitation' => $invitation]))
        ->assertRedirect();

    Mail::assertSent(TeamInvitationMail::class, fn (TeamInvitationMail $mail) => $mail->hasTo('resend-works@example.com'));

    // The resend leaves the invitation intact; a freshly signed link (exactly what
    // the resent mail carries) accepts successfully.
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();

    $this->actingAs($existingUser)->get(acceptUrl($invitation))->assertRedirect(route('aura.dashboard'));

    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $this->memberRole->id,
    ]);
});

it('matches the invited email case-insensitively on accept', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'casing@example.com', 'current_team_id' => null]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'Casing@Example.com', 'role' => $this->memberRole->id,
    ]);

    $this->actingAs($existingUser)->get(acceptUrl($invitation))
        ->assertRedirect(route('aura.dashboard'));

    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $this->memberRole->id,
    ]);
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
});

it('refuses acceptance when the carried role was deleted before acceptance (accept path)', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'deleted-role-accept@example.com', 'current_team_id' => null]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);

    $url = acceptUrl($invitation);

    $this->memberRole->delete();

    // Sane refusal: the invitation cannot resolve a role, so it 404s rather than
    // silently attaching without one (or escalating to super admin).
    $this->actingAs($existingUser)->get($url)->assertNotFound();

    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
    expect(DB::table('user_role')->where('team_id', $team->id)->where('user_id', $existingUser->id)->exists())->toBeFalse();
    expect($existingUser->fresh()->isSuperAdmin())->toBeFalse();
});

it('refuses registration and creates no orphan user when the carried role was deleted (register path)', function () {
    $team = $this->user->currentTeam;
    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'deleted-role-register@example.com', 'role' => $this->memberRole->id,
    ]);

    $url = registerUrl($team, $invitation);

    $this->memberRole->delete();

    auth()->logout();

    // The carried role no longer resolves, so registration fails like the accept
    // path (404) and the pre-check + transaction leave the DB untouched.
    $this->post($url, [
        'name' => 'No Role',
        'password' => 'Password123!XX',
        'password_confirmation' => 'Password123!XX',
    ])->assertNotFound();

    // No orphaned account, and the invitation survives for revoke/resend.
    expect(User::withoutGlobalScopes()->where('email', 'deleted-role-register@example.com')->exists())->toBeFalse();
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
});

it('accepts an invitation carrying a shared Global Role', function () {
    $team = $this->user->currentTeam;
    $globalAdmin = Role::firstOrCreateGlobalAdmin(); // Global Role, team_id = null
    $existingUser = User::factory()->create(['email' => 'global-role@example.com', 'current_team_id' => null]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $globalAdmin->id,
    ]);

    $this->actingAs($existingUser)->get(acceptUrl($invitation))
        ->assertRedirect(route('aura.dashboard'));

    // Membership records the team; the carried Global Role grants Super Admin there.
    $this->assertDatabaseHas('user_role', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role_id' => $globalAdmin->id,
    ]);
    expect($existingUser->fresh()->isSuperAdmin())->toBeTrue();
});

it('refuses an invitation carrying a role owned by another team (cross-team injection)', function () {
    $team = $this->user->currentTeam;
    $otherTeamRole = Role::create([
        'team_id' => Team::factory()->create()->id,
        'slug' => 'foreign', 'type' => 'Role', 'title' => 'Foreign', 'name' => 'Foreign',
        'super_admin' => false, 'permissions' => [],
    ]);
    $existingUser = User::factory()->create(['email' => 'foreign-role@example.com', 'current_team_id' => null]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $otherTeamRole->id,
    ]);

    // The role is not visible to the inviting team, so acceptance is refused and
    // no cross-team access is injected.
    $this->actingAs($existingUser)->get(acceptUrl($invitation))->assertNotFound();

    expect(DB::table('user_role')->where('user_id', $existingUser->id)->where('team_id', $team->id)->exists())->toBeFalse();
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
});

it('is idempotent when an existing member accepts: no duplicate Membership, invitation consumed', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'already-member@example.com', 'current_team_id' => null]);
    $existingUser->roles()->attach($this->memberRole->id, ['team_id' => $team->id]);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);

    $this->actingAs($existingUser)->get(acceptUrl($invitation))
        ->assertRedirect(route('aura.dashboard'));

    // Still exactly one Membership row for this team (no duplicate), invitation gone.
    expect(DB::table('user_role')->where('user_id', $existingUser->id)->where('team_id', $team->id)->count())->toBe(1);
    expect(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeFalse();
});

it('rejects an accept link whose invitation id was tampered after signing', function () {
    $team = $this->user->currentTeam;
    $existingUser = User::factory()->create(['email' => 'tamper@example.com', 'current_team_id' => null]);

    $target = TeamInvitation::create([
        'team_id' => $team->id, 'email' => $existingUser->email, 'role' => $this->memberRole->id,
    ]);
    $other = TeamInvitation::create([
        'team_id' => $team->id, 'email' => 'other-invite@example.com', 'role' => $this->memberRole->id,
    ]);

    // Sign for $target, then swap the path id to $other: the signature no longer
    // matches the URL, so the signed middleware refuses with a 403.
    $signed = acceptUrl($target);
    $tampered = str_replace(
        '/team-invitations/'.$target->id.'?',
        '/team-invitations/'.$other->id.'?',
        $signed,
    );

    $this->actingAs($existingUser)->get($tampered)->assertForbidden();

    expect(DB::table('user_role')->where('user_id', $existingUser->id)->exists())->toBeFalse();
});
