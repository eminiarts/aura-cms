<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock Aura::getResources() to return test resources
    Aura::shouldReceive('getResources')
        ->andReturn([
            User::class,
            Permission::class,
        ]);
});

describe('permission creation', function () {
    it('creates permissions for all resources', function () {
        $user = User::factory()->create(['id' => 1]);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful()
            ->expectsOutput('Resource permissions created successfully');

        $resources = [User::class, Permission::class];

        foreach ($resources as $resource) {
            $r = app($resource);
            $slug = $r::$slug;

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

            foreach ($permissionTypes as $type => $displayName) {
                $permission = Permission::where([
                    'slug' => "{$type}-{$slug}",
                    'group' => $r->pluralName(),
                ])->first();

                expect($permission)->not->toBeNull()
                    ->and($permission->name)->toBe($displayName.' '.$r->pluralName())
                    ->and($permission->group)->toBe($r->pluralName());
            }
        }
    });

    it('creates correct number of permissions', function () {
        $user = User::factory()->create(['id' => 1]);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful();

        // 8 permission types * 2 resources
        $expectedPermissionsCount = 8 * 2;
        expect(Permission::count())->toBe($expectedPermissionsCount);
    });
});

describe('duplicate handling', function () {
    it('does not duplicate existing permissions', function () {
        $user = User::factory()->create(['id' => 1]);

        $resource = app(User::class);
        Permission::create([
            'name' => 'View '.$resource->pluralName(),
            'slug' => 'view-'.$resource::$slug,
            'group' => $resource->pluralName(),
        ]);

        $initialCount = Permission::where('slug', 'view-'.$resource::$slug)->count();
        expect($initialCount)->toBe(1);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful();

        $finalCount = Permission::where('slug', 'view-'.$resource::$slug)->count();
        expect($finalCount)->toBe(1);
    });
});

describe('authentication', function () {
    it('authenticates as user ID 1', function () {
        $user = User::factory()->create(['id' => 1]);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful();

        expect(Auth::id())->toBe(1);
    });
});

describe('naming conventions', function () {
    it('creates permissions with correct naming convention', function () {
        $user = User::factory()->create(['id' => 1]);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful();

        $resource = app(User::class);
        $permission = Permission::where('slug', 'view-'.$resource::$slug)->first();

        expect($permission)
            ->name->toBe('View '.$resource->pluralName())
            ->slug->toBe('view-'.$resource::$slug)
            ->group->toBe($resource->pluralName());
    });

    it('displays progress messages for each resource', function () {
        $user = User::factory()->create(['id' => 1]);

        $this->artisan('aura:create-resource-permissions')
            ->expectsOutputToContain('Creating missing permissions for')
            ->assertSuccessful();
    });
});
