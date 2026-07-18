<?php

use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

require_once __DIR__.'/Support/helpers.php';

test('a super admin invites a new email; the signed link registers them into the team with the carried role', function () {
    $admin = createSuperAdmin();
    $team = $admin->currentTeam;
    $role = browserTeamRole($team->id, 'Editor', 'editor');

    // Intercept the invitation mail so the flow does not depend on a transport,
    // and so the mailable (carrying the invitation) can be inspected afterwards.
    Mail::fake();

    // Open the invite modal from the Users index header and fill the real form.
    $page = visit('/admin/user');

    $page->click('Invite')->wait(1);

    $page->assertSee('Invite User');

    $page->fill('#aura_field_email', 'newbie@example.com')
        ->select('#aura_field_role', (string) $role->id);

    // Submit the modal form. Enter in the email field triggers wire:submit
    // without colliding with the header's own "Invite" button text.
    $page->keys('#aura_field_email', 'Enter')->wait(2);

    $page->assertSee('Invitation sent successfully.');

    // The invitation exists and the mail was dispatched.
    Mail::assertSent(TeamInvitationMail::class, fn ($mail) => $mail->invitation->email === 'newbie@example.com');

    $invitation = TeamInvitation::withoutGlobalScopes()->latest('id')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('newbie@example.com')
        ->and((int) $invitation->role)->toBe($role->id);

    // The signed register link the mailable would carry.
    $registerUrl = browserInvitationRegisterUrl($team, $invitation);
    $invitationId = $invitation->id;

    // Log out, then follow the signed link as a guest and register.
    Auth::logout();

    $register = visit($registerUrl);

    $register->assertSee('Join '.$team->name);

    $register->fill('#name', 'New Bie')
        ->fill('#password', 'password')
        ->fill('#password_confirmation', 'password')
        ->press('Register')
        ->wait(2);

    $register->assertPathIs('/admin');

    $user = User::withoutGlobalScopes()->where('email', 'newbie@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->current_team_id)->toBe($team->id)
        ->and(browserMembershipExists($user->id, $role->id, $team->id))->toBeTrue();

    // The single-use invitation is consumed.
    expect(TeamInvitation::withoutGlobalScopes()->find($invitationId))->toBeNull();
});

test('an existing user accepts an invitation and gains membership with the carried role', function () {
    $admin = createSuperAdmin();
    $team = $admin->currentTeam;
    $role = browserTeamRole($team->id, 'Editor', 'editor');

    // A person who already has an account but is not yet in this team.
    $existing = User::factory()->create(['email' => 'exists@example.com']);

    // Arrange the pending invitation server-side (the invite UI is covered by the
    // new-user test above); the journey under test here is the accept path.
    $invitation = $team->teamInvitations()->create([
        'email' => 'exists@example.com',
        'role' => $role->id,
    ]);

    $acceptUrl = browserInvitationAcceptUrl($invitation);
    $invitationId = $invitation->id;

    // Act as the invited user, then follow the signed accept link in the browser.
    $this->actingAs($existing);

    $accept = visit($acceptUrl);

    $accept->wait(1)->assertPathIs('/admin');

    $existing->refresh();

    expect(browserMembershipExists($existing->id, $role->id, $team->id))->toBeTrue()
        ->and($existing->current_team_id)->toBe($team->id);

    // The invitation is consumed.
    expect(TeamInvitation::withoutGlobalScopes()->find($invitationId))->toBeNull();
});
