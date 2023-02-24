<?php

use livewire;
use Livewire\Livewire;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\TeamInvitation;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;

uses()->group('current');


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
    $this->get(route('register'))->assertOk();
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
