<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
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

it('user roles can be set via update', function () {
    $this->user = createSuperAdmin();

    $roleData = [
        'name' => 'New Role',
        'slug' => 'new role',
        'description' => 'New Role',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => Team::first()->id,
    ];

    $role = Role::create($roleData);

    $this->user->update(['roles' => [$role->id]]);

    $this->user->refresh();

    // ray($this->user->roles, $role->toArray());

    expect($this->user->roles->count())->toBe(1);
    expect($this->user->roles->first()->super_admin)->toBe(true);
    expect($this->user->current_team_id)->toBe(Team::first()->id);
    expect($this->user->roles->first()->name)->toBe('New Role');
});
