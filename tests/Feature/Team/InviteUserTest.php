<?php

use Livewire\Livewire;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\TeamInvitation;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

test('user gets correct role', function () {
});


test('Team Invitation can be created', function () {
    $team = $this->user->currentTeam;

    $teamInvitation = TeamInvitation::create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
        'team_id' => $team->id,
    ]);

    dd('hier', $teamInvitation->toArray());


    $invitation = $team->teamInvitations()->create([
        'email' => 'test@test.ch',
        'role' => Role::first()->id,
    ]);
});
