<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Team tests require the teams schema.');
    }
});

describe('with teams enabled', function () {
    beforeEach(function () {
        Config::set('aura.teams', true);
    });

    it('creates user with interactive prompts', function () {
        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'John Doe')
            ->expectsQuestion('What is your email?', 'johndoe@example.com')
            ->expectsQuestion('What is your password?', 'password')
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->expectsOutput('User created successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'global_admin' => false,
        ]);
    });

    it('creates team for user', function () {
        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'Jane Smith')
            ->expectsQuestion('What is your email?', 'jane@example.com')
            ->expectsQuestion('What is your password?', 'password')
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->assertExitCode(0);

        $this->assertDatabaseHas('teams', [
            'name' => 'Jane Smith',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $team = DB::table('teams')->where('user_id', $user->id)->first();

        expect($user->current_team_id)->toEqual($team->id);
    });

    it('assigns super admin role to user', function () {
        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'Admin User')
            ->expectsQuestion('What is your email?', 'admin@example.com')
            ->expectsQuestion('What is your password?', 'password')
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@example.com')->first();

        expect($user->roles->count())->toBe(1);

        $role = Role::first();
        expect($role->super_admin)->toBeTrue();
    });

    it('creates user with command-line options', function () {
        $this->artisan('aura:user', [
            '--name' => 'CLI User',
            '--email' => 'cli@example.com',
            '--password' => 'clipassword',
        ])
            // Partial-option interactive runs still prompt for Global Admin.
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->expectsOutput('User created successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'CLI User',
            'email' => 'cli@example.com',
            'global_admin' => false,
        ]);
    });

    it('hashes the password correctly', function () {
        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'Secure User')
            ->expectsQuestion('What is your email?', 'secure@example.com')
            ->expectsQuestion('What is your password?', 'mysecretpassword')
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->assertExitCode(0);

        $user = User::where('email', 'secure@example.com')->first();

        expect(Hash::check('mysecretpassword', $user->password))->toBeTrue();
    });

    it('creates a Global Admin via the --global-admin option', function () {
        $this->artisan('aura:user', [
            '--name' => 'Ops Operator',
            '--email' => 'ops@example.com',
            '--password' => 'opspassword',
            '--global-admin' => true,
        ])->assertExitCode(0);

        $user = User::where('email', 'ops@example.com')->first();

        expect($user->global_admin)->toBeTrue();
    });

    it('creates a Global Admin via the interactive confirmation', function () {
        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'Interactive GA')
            ->expectsQuestion('What is your email?', 'interactive-ga@example.com')
            ->expectsQuestion('What is your password?', 'password')
            ->expectsConfirmation('Should this user be a Global Admin?', 'yes')
            ->assertExitCode(0);

        $user = User::where('email', 'interactive-ga@example.com')->first();

        expect($user->global_admin)->toBeTrue();
    });

    it('does not grant Global Admin without the option', function () {
        $this->artisan('aura:user', [
            '--name' => 'Plain User',
            '--email' => 'plain@example.com',
            '--password' => 'plainpassword',
        ])
            // Declining the prompt leaves the flag off.
            ->expectsConfirmation('Should this user be a Global Admin?', 'no')
            ->assertExitCode(0);

        $user = User::where('email', 'plain@example.com')->first();

        expect($user->global_admin)->toBeFalse();
    });
});

describe('user roles management', function () {
    it('can update user roles', function () {
        $user = createSuperAdmin();

        $roleData = [
            'name' => 'New Role',
            'slug' => 'new_role',
            'description' => 'New Role Description',
            'super_admin' => true,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => Team::first()->id,
        ];

        $role = Role::create($roleData);

        $user->update(['roles' => [$role->id]]);
        $user->refresh();

        expect($user->roles->count())->toBe(1);
        expect($user->roles->first()->name)->toBe('New Role');
        expect($user->roles->first()->super_admin)->toBeTrue();
    });

    it('can replace user role', function () {
        $user = createSuperAdmin();
        $team = Team::first();
        $originalRole = $user->roles->first();

        $newRole = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'description' => 'Editor Role',
            'super_admin' => false,
            'permissions' => [],
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        // Replace existing role with new one
        $user->update(['roles' => [$newRole->id]]);
        $user->refresh();

        expect($user->roles->count())->toBe(1);
        expect($user->roles->first()->name)->toBe('Editor');
    });
});

// Note: Tests for teams disabled should be run with phpunit-without-teams.xml config
// as the database schema has NOT NULL constraint on team_id in the user_role pivot table
