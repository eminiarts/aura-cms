<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Config;
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

// Before each test, create a Superadmin and login
beforeEach(function () {

    $this->withoutExceptionHandling();

    $this->actingAs($this->user = createSuperAdmin());

    // config('aura.teams')
    config(['aura.teams' => true]);
});

test('user can be invited', function () {

    $this->withoutExceptionHandling();
    // Test InviteUser Livewire Component
    $component = Livewire::test(InviteUser::class)
        ->call('save')
        ->assertHasErrors(['form.fields.email' => 'required'])
        ->set('form.fields.email', 'test@test.ch')
        ->call('save')
        ->assertHasErrors(['form.fields.role' => 'required'])
        ->set('form.fields.role', 1)
        ->call('save')
        ->assertHasNoErrors();

    // DB should have 1 TeamInvitation
    $this->assertEquals(1, TeamInvitation::count());

    $invitation = TeamInvitation::first();

    $invitation->fresh();

    // DB should have 1 TeamInvitation with correct email
    expect($invitation->email)->toBe('test@test.ch');
});

test('user gets correct role', function () {

    expect(config('aura.teams'))->toBeTrue();

    ray()->clearScreen();

    // Create a new Role
    $role = Role::create([
        'title' => 'Test Role',
        'slug' => 'test_role',
        'permissions' => [
            'test_permission' => true,
        ],
    ]);

    // Test InviteUser Livewire Component
    $component = Livewire::test(InviteUser::class)
        ->call('save')
        ->assertHasErrors(['form.fields.email' => 'required'])
        ->set('form.fields.email', 'test@test.ch')
        ->call('save')
        ->assertHasErrors(['form.fields.role' => 'required'])
        ->set('form.fields.role', $role->id)
        ->call('save')
        ->assertHasNoErrors();

    // DB should have 1 TeamInvitation
    $this->assertEquals(1, TeamInvitation::count());

    $invitation = TeamInvitation::first();

    $invitation->fresh();

    // DB should have 1 TeamInvitation with correct email
    expect($invitation->email)->toBe('test@test.ch');

    // Go to the Registration Page and make sure it works
    $url = URL::signedRoute('aura.invitation.register', [$invitation->team, $invitation]);

    ray($url);

    // Log out
    $this->app['auth']->logout();

    // As a Guest
    $this->assertGuest();

    // Visit $url and assert Ok
    $response = $this->get($url);

    $response->assertOk();

    // Register the user and see if the role is correct
    $response = $this->post($url, [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Check if the user is created in the database
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@test.ch',
    ]);

    // Assert that the user is logged in
    $this->assertAuthenticated();

    // Assert is on dashboard
    $response->assertRedirect(config('aura.auth.redirect'));

    // Check if the Team Invitation is deleted
    $this->assertDatabaseMissing('team_invitations', [
        'email' => 'test@test.ch',
    ]);

    // Check if the user has the correct role
    $user = User::where('email', 'test@test.ch')->first();

    ray($user);
    ray($user->hasRole('test_role'));
    ray($user->roles);

    expect($user->hasRole('test_role'))->toBeTrue();
    expect($user->hasRole('super_admin'))->toBeFalse();
});

test('Team Invitation can be created', function () {
    $team = $this->user->currentTeam;

    $invitation = $team->teamInvitations()->create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
    ]);

    // DB should have 1 TeamInvitation with correct email
    expect($invitation->email)->toBe('test@test.ch');

    // expect $invitation->exists to be true
    expect($invitation->exists)->toBeTrue();
});

// create a test to see if /register route is available
test('register route is available', function () {

    $this->withoutExceptionHandling();

    // log the user out
    $this->app['auth']->logout();

    // assert that the user is logged out
    $this->assertGuest();

    // Get the register view
    $this->get(route('aura.register'))->assertOk();
});

test('user email is prefilled in the registration', function () {
    $team = $this->user->currentTeam;

    $invitation = $team->teamInvitations()->create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
    ]);

    // DB should have 1 TeamInvitation with correct email
    expect($invitation->email)->toBe('test@test.ch');

    // expect $invitation->exists to be true
    expect($invitation->exists)->toBeTrue();
});

test('user_invitations can be enabled', function () {
    livewire(Config::class)
        ->set('form.fields.user_invitations', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(config('aura.auth.user_invitations'))->toBeTrue();

    expect(app('aura')::option('user_invitations'))->toBeTrue();
});

test('user_invitations can be disabled', function () {
    livewire(Config::class)
        ->set('form.fields.user_invitations', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(config('aura.auth.user_invitations'))->toBeTrue();

    expect(app('aura')::option('user_invitations'))->toBeTrue();
});

test('user can register using an invitation', function () {
    $team = Team::first();

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'invite@test.de',
        'role' => Role::first()->id,
    ]);

    // Generate the signed URL
    $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);

    // Make the request to the signed URL
    $response = $this->get($url);

    // Should be 302 because is logged in
    $response->assertStatus(302);

    // log the user out
    $this->app['auth']->logout();

    // assert that the user is logged out
    $this->assertGuest();

    // Make the request to the signed URL
    $response = $this->get($url);

    // Assert that the response is OK and contains the registration view
    $response->assertOk()->assertViewIs('aura::auth.user_invitation');

    // Assert team name is in the view
    $response->assertSee($team->name);

    // Assert email is in the view
    $response->assertSee($invitation->email);

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

    $this->assertEquals([$invitation->role], $user->fields['roles']);

    $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
});

test('email and role are required in the invite user component', function () {
    $team = Team::first();

    livewire(InviteUser::class, ['team' => $team])
        ->set('form.fields.email', '')
        ->set('form.fields.role', '')
        ->call('save')
        ->assertHasErrors([
            'form.fields.email',
            'form.fields.role',
        ]);

    $user = User::factory()->create(['email' => 'invited@test.com']);

    livewire(InviteUser::class, ['team' => $team])
        ->set('form', ['fields' => [
            'email' => 'invited@test.com',
            'role' => Role::first()->id,
        ]])
        ->call('save')
        ->assertHasNoErrors([
            'form.fields.email',
        ]);

    // Attach the user to the team
    $user = User::find($user->id);
    $user->update(['fields' => ['roles' => [Role::first()->id]]]);
    $user->teams()->attach($team->id);

    livewire(InviteUser::class, ['team' => $team])
        ->set('form', ['fields' => [
            'email' => 'invited@test.com',
            'role' => Role::first()->id,
        ]])
        ->call('save')
        ->assertHasErrors([
            'form.fields.email',
        ]);
});
