<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('superadmin can get all users for his team', function () {

    $team = $this->user->currentTeam;

    $role = Role::first();

    User::factory()->count(10)->create([
        'roles' => [
            $role->id
        ]
    ]);

    $users = User::all();

    $this->assertCount(11, $users);
});

// user can not get all users for other teams
test('user can not get all users for other teams', function () {

    // logout
    auth()->logout();


    $otherUser = User::factory()->create();

    $this->actingAs($otherUser);

    $team = Team::factory()->create();

    $users =User::factory()->count(2)->create([
        'current_team_id' => $team->id
    ]);

    ray($users->toArray()); 

    $this->actingAs($this->user);
    $users = User::all();

    ray($users->toArray())->red(); 

    $this->assertCount(1, $users);
});