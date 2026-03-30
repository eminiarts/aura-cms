<?php

use Aura\Base\Livewire\InviteUser;
use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    config(['aura.teams' => true]);
    config(['aura.auth.user_invitations' => true]);
});

// ─── Email Sending ───────────────────────────────────────────────────────────

describe('Invitation Email', function () {
    it('sends an invitation email when inviting a user', function () {
        Mail::fake();

        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'invited@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        Mail::assertSent(TeamInvitationMail::class, function ($mail) {
            return $mail->hasTo('invited@example.com');
        });
    });

    it('sends exactly one email per invitation', function () {
        Mail::fake();

        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'single@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save');

        Mail::assertSent(TeamInvitationMail::class, 1);
    });

    it('email contains a register URL for new users', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newperson@example.com',
            'role' => $role->id,
        ]);

        $mail = new TeamInvitationMail($invitation);
        $rendered = $mail->build();

        expect($rendered->viewData['registerUrl'])->toContain('register');
        expect($rendered->viewData['userExists'])->toBeFalse();
    });

    it('email shows accept URL for existing users', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        // Create a user with a role in the team so TeamScope finds them
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'current_team_id' => $team->id,
        ]);
        $existingUser->roles()->syncWithPivotValues([$role->id], ['team_id' => $team->id]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'existing@example.com',
            'role' => $role->id,
        ]);

        $mail = new TeamInvitationMail($invitation);
        $rendered = $mail->build();

        expect($rendered->viewData['userExists'])->toBeTrue();
        expect($rendered->viewData['acceptUrl'])->toContain('team-invitations');
    });

    it('email has correct subject line', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'subject@example.com',
            'role' => $role->id,
        ]);

        $mail = new TeamInvitationMail($invitation);
        $built = $mail->build();

        expect($built->subject)->toContain($team->name);
        expect($built->subject)->toContain('invited');
    });
});

// ─── Duplicate Prevention ────────────────────────────────────────────────────

describe('Duplicate Invitation Prevention', function () {
    it('prevents inviting the same email twice to the same team', function () {
        $role = Role::first();

        // First invitation succeeds
        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'duplicate@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        // Second invitation to same email fails
        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'duplicate@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasErrors(['form.fields.email']);

        expect(TeamInvitation::count())->toBe(1);
    });

    it('prevents inviting a user who is already a team member', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        // The super admin user is already a member
        Livewire::test(InviteUser::class)
            ->set('form.fields.email', $this->user->email)
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    it('allows inviting the same email to a different team', function () {
        Mail::fake();

        $team1 = $this->user->currentTeam;
        $role = Role::first();

        // First invitation to team1
        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'multiTeam@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        // Create a second team and switch to it
        $team2 = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->update(['current_team_id' => $team2->id]);
        $this->user->refresh();

        // Get or create an admin role for team2 so the user can invite
        $role2 = Role::withoutGlobalScope(TeamScope::class)
            ->firstOrCreate(
                ['slug' => 'admin', 'team_id' => $team2->id],
                ['name' => 'Admin', 'super_admin' => true, 'permissions' => []]
            );
        $this->user->roles()->syncWithPivotValues([$role2->id], ['team_id' => $team2->id]);
        $this->user->refresh();

        // Invitation to second team should succeed
        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'multiTeam@example.com')
            ->set('form.fields.role', $role2->id)
            ->call('save')
            ->assertHasNoErrors();

        // Use withoutGlobalScope since invitations are in different teams
        expect(TeamInvitation::withoutGlobalScopes()->count())->toBe(2);
    });
});

// ─── Authorization ───────────────────────────────────────────────────────────

describe('Invitation Authorization', function () {
    it('super admin can invite users', function () {
        Mail::fake();

        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'admin-invite@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(TeamInvitation::count())->toBe(1);
    });

    it('regular user without invite permission cannot invite', function () {
        $team = $this->user->currentTeam;

        // Create a regular user with a non-admin role
        $regularRole = Role::create([
            'name' => 'Regular',
            'slug' => 'regular',
            'team_id' => $team->id,
            'super_admin' => false,
            'permissions' => [],
        ]);

        $regularUser = User::factory()->create([
            'current_team_id' => $team->id,
        ]);
        $regularUser->roles()->syncWithPivotValues([$regularRole->id], ['team_id' => $team->id]);
        $regularUser->refresh();

        $this->actingAs($regularUser);

        Mail::fake();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'unauthorized-invite@example.com')
            ->set('form.fields.role', $regularRole->id)
            ->call('save')
            ->assertForbidden();

        Mail::assertNothingSent();
        expect(TeamInvitation::count())->toBe(0);
    });
});

// ─── Registration Flow (New Users) ──────────────────────────────────────────

describe('Invitation Registration for New Users', function () {
    it('new user can register via signed invitation URL', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'newreg@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        Auth::logout();

        $this->get($url)->assertOk()->assertSee('newreg@example.com');

        $response = $this->post($url, [
            'name' => 'New Registered User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(config('aura.auth.redirect'));
        $this->assertAuthenticated();

        $user = User::where('email', 'newreg@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('New Registered User');
        expect($user->current_team_id)->toBe($team->id);
        expect(Hash::check('password', $user->password))->toBeTrue();
    });

    it('fires Registered event on invitation registration', function () {
        Event::fake([Registered::class]);

        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'event@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->post($url, [
            'name' => 'Event User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Event::assertDispatched(Registered::class, function ($event) {
            return $event->user->email === 'event@example.com';
        });
    });

    it('uses email from invitation, not from request', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'real@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        // Even if someone tries to submit a different email, the invitation email is used
        $this->post($url, [
            'name' => 'Tamper Test',
            'email' => 'tampered@evil.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'real@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'tampered@evil.com']);
    });

    it('validates password confirmation on registration', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'passvalid@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $response = $this->post($url, [
            'name' => 'Bad Pass',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => 'passvalid@example.com']);
    });

    it('validates name is required on registration', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'noname@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $response = $this->post($url, [
            'name' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['name']);
    });

    it('user is automatically logged in after registration', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'autologin@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->assertGuest();

        $this->post($url, [
            'name' => 'Auto Login',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        expect(Auth::user()->email)->toBe('autologin@example.com');
    });

    it('redirects authenticated users away from registration page', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'redirect@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        // Logged-in user should be redirected, not see registration form
        $response = $this->get($url);
        $response->assertStatus(302);
    });
});

// ─── Existing User Accept Flow ──────────────────────────────────────────────

describe('Existing User Accept Flow', function () {
    it('accept route requires authentication', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'accept@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.team-invitations.accept', ['invitation' => $invitation]);

        // Guest should be redirected to login
        Auth::logout();
        $response = $this->get($url);
        $response->assertRedirect();
    });

    it('accept route requires a valid signature', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'unsigned-accept@example.com',
            'role' => $role->id,
        ]);

        // Access without signature
        $url = route('aura.team-invitations.accept', ['invitation' => $invitation]);
        $response = $this->get($url);
        $response->assertStatus(403);
    });
});

// ─── Signed URL Security ────────────────────────────────────────────────────

describe('Signed URL Security', function () {
    it('rejects unsigned registration URL', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'unsigned@example.com',
            'role' => $role->id,
        ]);

        Auth::logout();

        // Access without signature
        $url = route('aura.invitation.register', [$team, $invitation]);
        $response = $this->get($url);
        $response->assertStatus(403);
    });

    it('rejects tampered registration URL', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'tampered@example.com',
            'role' => $role->id,
        ]);

        Auth::logout();

        // Generate signed URL then tamper with it
        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        $tamperedUrl = $url . '&extra=param';
        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);
    });
});

// ─── Config: User Invitations Disabled ──────────────────────────────────────

describe('User Invitations Config Toggle', function () {
    it('returns 404 when user_invitations config is disabled', function () {
        config(['aura.auth.user_invitations' => false]);

        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'disabled@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        Auth::logout();

        $this->withoutExceptionHandling();

        try {
            $this->get($url);
            $this->fail('Expected 404');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            expect(true)->toBeTrue();
        }
    });

    it('returns 404 on POST when user_invitations is disabled', function () {
        config(['aura.auth.user_invitations' => false]);

        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'disabled-post@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        Auth::logout();

        $response = $this->post($url, [
            'name' => 'Should Not Work',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'disabled-post@example.com']);
    });
});

// ─── Invitation Lifecycle ───────────────────────────────────────────────────

describe('Invitation Lifecycle', function () {
    it('invitation is deleted after successful registration', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'lifecycle@example.com',
            'role' => $role->id,
        ]);

        expect(TeamInvitation::count())->toBe(1);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->post($url, [
            'name' => 'Lifecycle User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        expect(TeamInvitation::count())->toBe(0);
    });

    it('can create multiple invitations for the same team', function () {
        Mail::fake();

        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'user1@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'user2@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'user3@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(TeamInvitation::count())->toBe(3);
        Mail::assertSent(TeamInvitationMail::class, 3);
    });
});

// ─── Role Assignment ────────────────────────────────────────────────────────

describe('Role Assignment via Invitation', function () {
    it('registered user has the exact role from invitation', function () {
        $team = $this->user->currentTeam;

        $customRole = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'team_id' => $team->id,
            'super_admin' => false,
            'permissions' => ['view-post' => true, 'create-post' => true],
        ]);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'editor@example.com',
            'role' => $customRole->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->post($url, [
            'name' => 'Editor User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'editor@example.com')->first();

        expect($user->hasRole('editor'))->toBeTrue();
        expect($user->hasRole('admin'))->toBeFalse();
        expect($user->isSuperAdmin())->toBeFalse();
    });

    it('registered user belongs to the invitation team', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'teamcheck@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->post($url, [
            'name' => 'Team Check',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'teamcheck@example.com')->first();

        expect($user->current_team_id)->toBe($team->id);
        expect($user->belongsToTeam($team))->toBeTrue();
    });

    it('registered user can log in and access the team', function () {
        $team = $this->user->currentTeam;
        $role = Role::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'logincheck@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        Auth::logout();

        $this->post($url, [
            'name' => 'Login Check',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Log out and log back in
        Auth::logout();

        $loginSuccess = Auth::attempt([
            'email' => 'logincheck@example.com',
            'password' => 'password',
        ]);

        expect($loginSuccess)->toBeTrue();

        $user = User::where('email', 'logincheck@example.com')->first();
        expect($user->current_team_id)->toBe($team->id);
    });
});

// ─── Livewire Component ─────────────────────────────────────────────────────

describe('InviteUser Livewire Component', function () {
    it('validates email format', function () {
        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'not-an-email')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    it('dispatches closeModal and refreshTable events on success', function () {
        Mail::fake();

        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'events@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('closeModal')
            ->assertDispatched('refreshTable');
    });

    it('stores invitation with correct team_id', function () {
        Mail::fake();

        $team = $this->user->currentTeam;
        $role = Role::first();

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'teamid@example.com')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        $invitation = TeamInvitation::first();
        expect($invitation->team_id)->toBe($team->id);
    });
});
