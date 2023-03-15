<?php

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Livewire\AuraConfig;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;
use Eminiarts\Aura\Providers\RouteServiceProvider;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\TeamInvitation;
use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use function Pest\Livewire\livewire;

uses()->group('current');

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);

    // Enable Team Registration
    Aura::setOption('team_registration', true);
});

test('user can be invited', function () {
    // Test InviteUser Livewire Component
    $component = Livewire::test(InviteUser::class, ['resource' => 'user'])
    ->call('save')
    ->assertHasErrors(['post.fields.email' => 'required'])
    ->set('post.fields.email', 'test@test.ch')
    ->call('save')
    ->assertHasErrors(['post.fields.role' => 'required'])
    ->set('post.fields.role', 1)
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
    livewire(AuraConfig::class)
    ->set('post.fields.user_invitations', true)
    ->call('save')
    ->assertHasNoErrors();

    expect(Aura::option('user_invitations'))->toBeTrue();

    expect(app('aura')::option('user_invitations'))->toBeTrue();
});

test('user_invitations can be disabled', function () {
    livewire(AuraConfig::class)
    ->set('post.fields.user_invitations', true)
    ->call('save')
    ->assertHasNoErrors();

    expect(Aura::option('user_invitations'))->toBeTrue();

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

    $response->assertRedirect(RouteServiceProvider::HOME);

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
        ->set('post.fields.email', '')
        ->set('post.fields.role', '')
        ->call('save')
        ->assertHasErrors([
            'post.fields.email',
            'post.fields.role',
        ]);

    $user = User::factory()->create(['email' => 'invited@test.com']);

    livewire(InviteUser::class, ['team' => $team])
        ->set('post', ['fields' => [
            'email' => 'invited@test.com',
            'role' => Role::first()->id,
        ]])
        ->call('save')
        ->assertHasNoErrors([
            'post.fields.email',
        ]);

    // Attach the user to the team
    $user = User::find($user->id);
    $user->update(['fields' => ['roles' => [Role::first()->id]]]);
    $user->teams()->attach($team->id);

    livewire(InviteUser::class, ['team' => $team])
            ->set('post', ['fields' => [
                'email' => 'invited@test.com',
                'role' => Role::first()->id,
            ]])
            ->call('save')
            ->assertHasErrors([
                'post.fields.email',
            ]);
});
