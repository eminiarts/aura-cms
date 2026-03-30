<?php

use Aura\Base\Livewire\InviteUser;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutExceptionHandling();
    $this->actingAs($this->user = createSuperAdmin());
    config(['aura.teams' => true]);
});

describe('User Invitation Creation', function () {
    it('can invite a user via Livewire component', function () {
        $this->withoutExceptionHandling();

        Livewire::test(InviteUser::class)
            ->call('save')
            ->assertHasErrors(['form.fields.email' => 'required'])
            ->set('form.fields.email', 'test@test.ch')
            ->call('save')
            ->assertHasErrors(['form.fields.role' => 'required'])
            ->set('form.fields.role', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(1, TeamInvitation::count());

        $invitation = TeamInvitation::first();
        expect($invitation->email)->toBe('test@test.ch');
    });

    it('creates invitation with correct role', function () {
        expect(config('aura.teams'))->toBeTrue();

        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'permissions' => [
                'test_permission' => true,
            ],
        ]);

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'roletest@test.ch')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        $invitation = TeamInvitation::first();
        expect($invitation->email)->toBe('roletest@test.ch');
        expect((int) $invitation->role)->toBe($role->id);
    });

    it('creates team invitation model directly', function () {
        $team = $this->user->currentTeam;

        $invitation = $team->teamInvitations()->create([
            'email' => 'direct@test.ch',
            'role' => Role::first()->id,
        ]);

        expect($invitation->email)->toBe('direct@test.ch');
        expect($invitation->exists)->toBeTrue();
    });
});

describe('User Invitation Registration', function () {
    it('register route is available for guests', function () {
        $this->withoutExceptionHandling();

        $this->app['auth']->logout();
        $this->assertGuest();

        $this->get(route('aura.register'))->assertOk();
    });

    it('invited user gets correct role after registration', function () {
        expect(config('aura.teams'))->toBeTrue();

        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'permissions' => [
                'test_permission' => true,
            ],
        ]);

        Livewire::test(InviteUser::class)
            ->set('form.fields.email', 'invitee@test.ch')
            ->set('form.fields.role', $role->id)
            ->call('save')
            ->assertHasNoErrors();

        $invitation = TeamInvitation::first();
        $url = URL::signedRoute('aura.invitation.register', [$invitation->team, $invitation]);

        $this->app['auth']->logout();
        $this->assertGuest();

        $this->get($url)->assertOk();

        $response = $this->post($url, [
            'name' => 'Test User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'invitee@test.ch',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(config('aura.auth.redirect'));

        $this->assertDatabaseMissing('team_invitations', [
            'email' => 'invitee@test.ch',
        ]);

        $user = User::where('email', 'invitee@test.ch')->first();

        expect($user->hasRole('test_role'))->toBeTrue();
        expect($user->hasRole('admin'))->toBeFalse();
    });

    it('can register using invitation link', function () {
        $team = Team::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invite@test.de',
            'role' => Role::first()->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        // Logged in user should be redirected
        $this->get($url)->assertStatus(302);

        $this->app['auth']->logout();
        $this->assertGuest();

        // Guest should see invitation page
        $this->get($url)
            ->assertOk()
            ->assertViewIs('aura::auth.user_invitation')
            ->assertSee($team->name)
            ->assertSee($invitation->email);

        $user = User::where('email', $invitation->email)->first();
        $this->assertNull($user);

        $response = $this->post($url, [
            'name' => 'Test User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(config('aura.auth.redirect'));

        $user = User::where('email', $invitation->email)->first();
        $this->assertNotNull($user);

        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertEquals($team->id, $user->current_team_id);

        expect($user->roles->first()->id)->toEqual($invitation->role);

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    });
});

describe('User Invitation Validation', function () {
    it('requires email and role fields', function () {
        $team = Team::first();

        livewire(InviteUser::class, ['team' => $team])
            ->set('form.fields.email', '')
            ->set('form.fields.role', '')
            ->call('save')
            ->assertHasErrors([
                'form.fields.email',
                'form.fields.role',
            ]);
    });

    it('can invite new email address', function () {
        $team = Team::first();

        $user = User::factory()->create(['email' => 'newuser@test.com']);

        livewire(InviteUser::class, ['team' => $team])
            ->set('form', ['fields' => [
                'email' => 'newuser@test.com',
                'role' => Role::first()->id,
            ]])
            ->call('save')
            ->assertHasNoErrors(['form.fields.email']);
    });

    it('prevents inviting user already in team', function () {
        $team = Team::first();

        // Create user and add to team
        $user = User::factory()->create(['email' => 'existing@test.com']);
        $user->update(['fields' => ['roles' => [Role::first()->id]]]);

        // Try to invite same user again
        livewire(InviteUser::class, ['team' => $team])
            ->set('form', ['fields' => [
                'email' => 'existing@test.com',
                'role' => Role::first()->id,
            ]])
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });
});

describe('Invitation Management', function () {
    it('deletes invitation after user registers', function () {
        $team = Team::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'cleanup@test.de',
            'role' => Role::first()->id,
        ]);

        expect(TeamInvitation::count())->toBe(1);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        $this->app['auth']->logout();

        $this->post($url, [
            'name' => 'Cleanup Test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        expect(TeamInvitation::count())->toBe(0);
    });

    it('associates registered user with correct team', function () {
        $team = Team::first();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'teamtest@test.de',
            'role' => Role::first()->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

        $this->app['auth']->logout();

        $this->post($url, [
            'name' => 'Team Test User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'teamtest@test.de')->first();

        expect($user->current_team_id)->toBe($team->id);
        expect($user->belongsToTeam($team))->toBeTrue();
    });
});
