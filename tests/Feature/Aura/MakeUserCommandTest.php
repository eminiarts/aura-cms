<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;

// beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

it('can create a user with all required fields', function () {
    $this->artisan('aura:user')
        ->expectsQuestion('What is your name?', 'John Doe')
        ->expectsQuestion('What is your email?', 'johndoe@example.com')
        ->expectsQuestion('What is your password?', 'password')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
    ]);

    $this->assertDatabaseHas('teams', [
        'name' => 'John Doe',
    ]);

    $user = User::where('email', 'johndoe@example.com')->first();
    $team = DB::table('teams')->where('user_id', $user->id)->first();

    expect($user->current_team_id)->toEqual($team->id);

    expect($user->roles->count())->toBe(1);

    $role = Role::get();

    expect($role->count())->toBe(1);

    expect($role->first()->super_admin)->toBe(true);
});
