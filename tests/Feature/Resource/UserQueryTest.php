<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('superadmin can get all users for his team', function () {

    $team = $this->user->currentTeam;

    User::factory()->count(10)->create([]);

    $users = User::all();

    $this->assertCount(11, $users);
});

// user can not get all users for other teams
test('user can not get all users for other teams', function () {

    $otherUser = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser);
    $users =User::factory()->count(2)->create([
    ]);

    // dd($users->toArray()); 

    $this->actingAs($this->user);
    $users = User::all();

    $this->assertCount(1, $users);
});