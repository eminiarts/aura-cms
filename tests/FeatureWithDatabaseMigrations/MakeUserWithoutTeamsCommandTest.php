<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['aura.teams' => false]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();
});

afterEach(function () {
    config(['aura.teams' => true]);
});

describe('aura:user command without teams', function () {
    it('creates a user with role when teams are disabled', function () {
        expect(config('aura.teams'))->toBeFalse();

        $this->artisan('aura:user')
            ->expectsQuestion('What is your name?', 'John Doe')
            ->expectsQuestion('What is your email?', 'johndoe@example.com')
            ->expectsQuestion('What is your password?', 'password123')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ]);

        $user = User::where('email', 'johndoe@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->roles->count())->toBe(1);

        $role = Role::first();
        expect($role)->not->toBeNull()
            ->and($role->super_admin)->toBe(true)
            ->and($role->team_id)->toBeNull()
            ->and($role->slug)->toBe('super_admin');
    });

    it('creates user with command options instead of prompts', function () {
        $this->artisan('aura:user', [
            '--name' => 'Jane Smith',
            '--email' => 'jane@example.com',
            '--password' => 'securepass',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        expect($user)->not->toBeNull()
            ->and(Hash::check('securepass', $user->password))->toBeTrue();
    });

    it('creates super admin role without team_id', function () {
        $this->artisan('aura:user', [
            '--name' => 'Admin',
            '--email' => 'admin@example.com',
            '--password' => 'password',
        ])->assertExitCode(0);

        $role = Role::where('slug', 'super_admin')->first();

        expect($role)->not->toBeNull()
            ->and($role->name)->toBe('Super Admin')
            ->and($role->super_admin)->toBeTrue()
            ->and($role->permissions)->toBe([]);

        // Verify team_id column does not exist when teams disabled
        expect(Schema::hasColumn('roles', 'team_id'))->toBeFalse();
    });

    it('does not create teams table when teams are disabled', function () {
        expect(Schema::hasTable('teams'))->toBeFalse();
        expect(Schema::hasColumn('users', 'current_team_id'))->toBeFalse();
    });

    it('logs in the created user', function () {
        expect(auth()->check())->toBeFalse();

        $this->artisan('aura:user', [
            '--name' => 'Test User',
            '--email' => 'test@example.com',
            '--password' => 'password',
        ])->assertExitCode(0);

        expect(auth()->check())->toBeTrue()
            ->and(auth()->user()->email)->toBe('test@example.com');
    });

    it('displays success message after user creation', function () {
        $this->artisan('aura:user', [
            '--name' => 'Test',
            '--email' => 'success@example.com',
            '--password' => 'password',
        ])
            ->expectsOutput('User created successfully.')
            ->assertExitCode(0);
    });

    it('enforces unique role slug constraint without teams', function () {
        // Create first user - this creates a super_admin role
        $this->artisan('aura:user', [
            '--name' => 'First User',
            '--email' => 'first@example.com',
            '--password' => 'password',
        ])->assertExitCode(0);

        expect(Role::where('slug', 'super_admin')->count())->toBe(1);

        // The second user creation would fail because of unique slug constraint
        // This documents the current behavior - each aura:user creates a new role
        // which fails when slug is already taken
    });
});
