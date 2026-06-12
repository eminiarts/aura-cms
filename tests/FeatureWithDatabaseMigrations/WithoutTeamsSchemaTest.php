<?php

use Aura\Base\Resources\Option;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['aura.teams' => false]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

afterEach(function () {
    config(['aura.teams' => true]);
});

describe('database schema without teams', function () {
    describe('teams table absence', function () {
        it('does not create teams table', function () {
            expect(Schema::hasTable('teams'))->toBeFalse();
        });

        it('does not create team_meta table', function () {
            expect(Schema::hasTable('team_meta'))->toBeFalse();
        });
    });

    describe('users table columns', function () {
        it('does not have current_team_id column', function () {
            expect(Schema::hasColumn('users', 'current_team_id'))->toBeFalse();
        });

        it('has required user columns', function () {
            expect(Schema::hasColumn('users', 'id'))->toBeTrue()
                ->and(Schema::hasColumn('users', 'name'))->toBeTrue()
                ->and(Schema::hasColumn('users', 'email'))->toBeTrue()
                ->and(Schema::hasColumn('users', 'password'))->toBeTrue();
        });
    });

    describe('posts table columns', function () {
        it('does not have team_id column', function () {
            expect(Schema::hasColumn('posts', 'team_id'))->toBeFalse();
        });

        it('has required post columns', function () {
            expect(Schema::hasColumn('posts', 'id'))->toBeTrue()
                ->and(Schema::hasColumn('posts', 'title'))->toBeTrue()
                ->and(Schema::hasColumn('posts', 'content'))->toBeTrue()
                ->and(Schema::hasColumn('posts', 'type'))->toBeTrue();
        });
    });

    describe('roles table columns', function () {
        it('does not have team_id column', function () {
            expect(Schema::hasColumn('roles', 'team_id'))->toBeFalse();
        });

        it('has required role columns', function () {
            expect(Schema::hasColumn('roles', 'id'))->toBeTrue()
                ->and(Schema::hasColumn('roles', 'name'))->toBeTrue()
                ->and(Schema::hasColumn('roles', 'slug'))->toBeTrue()
                ->and(Schema::hasColumn('roles', 'super_admin'))->toBeTrue()
                ->and(Schema::hasColumn('roles', 'permissions'))->toBeTrue();
        });
    });

    describe('permissions table columns', function () {
        it('does not have team_id column', function () {
            expect(Schema::hasColumn('permissions', 'team_id'))->toBeFalse();
        });

        it('has required permission columns', function () {
            expect(Schema::hasColumn('permissions', 'id'))->toBeTrue()
                ->and(Schema::hasColumn('permissions', 'name'))->toBeTrue()
                ->and(Schema::hasColumn('permissions', 'slug'))->toBeTrue();
        });
    });

    describe('options table columns', function () {
        it('exists', function () {
            expect(Schema::hasTable('options'))->toBeTrue();
        });

        it('does not have team_id column', function () {
            expect(Schema::hasColumn('options', 'team_id'))->toBeFalse();
        });

        it('has required option columns', function () {
            expect(Schema::hasColumn('options', 'id'))->toBeTrue()
                ->and(Schema::hasColumn('options', 'name'))->toBeTrue()
                ->and(Schema::hasColumn('options', 'value'))->toBeTrue();
        });
    });

    describe('user_role pivot table', function () {
        it('does not have team_id column', function () {
            expect(Schema::hasColumn('user_role', 'team_id'))->toBeFalse();
        });

        it('has required pivot columns', function () {
            expect(Schema::hasColumn('user_role', 'user_id'))->toBeTrue()
                ->and(Schema::hasColumn('user_role', 'role_id'))->toBeTrue();
        });
    });
});

describe('configuration verification', function () {
    it('confirms teams are disabled', function () {
        expect(config('aura.teams'))->toBeFalse();
    });

    it('confirms user is super admin', function () {
        expect(auth()->user()->isSuperAdmin())->toBeTrue();
    });
});

describe('model behavior without teams', function () {
    it('creates user without team association', function () {
        $user = User::factory()->create();

        expect($user->current_team_id)->toBeNull();
    });

    it('creates role without team_id', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'A test role',
            'super_admin' => false,
            'permissions' => [],
        ]);

        expect($role->team_id)->toBeNull();
    });

    it('creates permission without team_id', function () {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'description' => 'A test permission',
        ]);

        expect($permission->team_id)->toBeNull();
    });

    it('creates option without team_id', function () {
        $option = Option::create([
            'name' => 'test-option',
            'value' => ['key' => 'value'],
        ]);

        expect($option->team_id)->toBeNull();
    });
});
