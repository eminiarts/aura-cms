<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

$permissionTypes = [
    'view' => 'View',
    'viewAny' => 'View Any',
    'create' => 'Create',
    'update' => 'Update',
    'restore' => 'Restore',
    'delete' => 'Delete',
    'forceDelete' => 'Force Delete',
    'scope' => 'Scope',
];

beforeEach(function () {
    // Mock Aura::getResources() to return test resources
    Aura::shouldReceive('getResources')
        ->andReturn([
            User::class,
            Permission::class,
        ]);
});

describe('permission creation', function () use ($permissionTypes) {
    it('creates permissions for all resources', function () use ($permissionTypes) {
        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful()
            ->expectsOutput('Resource permissions created successfully');

        foreach ([User::class, Permission::class] as $resource) {
            $r = app($resource);

            foreach ($permissionTypes as $type => $displayName) {
                $permission = Permission::withoutGlobalScopes()
                    ->where('slug', "{$type}-{$r::$slug}")
                    ->first();

                expect($permission)->not->toBeNull()
                    ->and($permission->name)->toBe($displayName.' '.$r->pluralName())
                    ->and($permission->group)->toBe($r->pluralName());
            }
        }
    });

    it('creates correct number of permissions', function () {
        $this->artisan('aura:create-resource-permissions')->assertSuccessful();

        // 8 permission types * 2 resources
        expect(Permission::withoutGlobalScopes()->count())->toBe(16);
    });

    it('does not log anybody in', function () {
        $this->artisan('aura:create-resource-permissions')->assertSuccessful();

        expect(Auth::check())->toBeFalse();
    });
});

describe('duplicate handling', function () {
    it('is idempotent', function () {
        $this->artisan('aura:create-resource-permissions')->assertSuccessful();
        $this->artisan('aura:create-resource-permissions')->assertSuccessful();

        expect(Permission::withoutGlobalScopes()->count())->toBe(16);
    });
});

describe('team option', function () {
    it('assigns the permissions to the given team', function () {
        $this->artisan('aura:create-resource-permissions', ['--team' => 7])
            ->assertSuccessful();

        expect(Permission::withoutGlobalScopes()->where('team_id', 7)->count())->toBe(16);
    })->skip(fn () => ! config('aura.teams'), 'Teams are disabled.');

    it('rejects a non numeric team id', function () {
        $this->artisan('aura:create-resource-permissions', ['--team' => 'abc'])
            ->expectsOutput('The --team option must be a numeric team ID.')
            ->assertFailed();

        expect(Permission::withoutGlobalScopes()->count())->toBe(0);
    });
});
