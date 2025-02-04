<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Set teams to false for this test
    config(['aura.teams' => false]);

    // Drop all tables and run our migration
    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

});

it('can create a user with role when teams are disabled', function () {
    Config::set('aura.teams', false);
    
    $this->artisan('aura:user')
        ->expectsQuestion('What is your name?', 'John Doe')
        ->expectsQuestion('What is your email?', 'johndoe@example.com')
        ->expectsQuestion('What is your password?', 'password')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
    ]);

    $user = User::where('email', 'johndoe@example.com')->first();
    
    expect($user->roles->count())->toBe(1);

    $role = Role::get();
    expect($role->count())->toBe(1);
    expect($role->first()->super_admin)->toBe(true);
    expect($role->first()->team_id)->toBeNull();
});


