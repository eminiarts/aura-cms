<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Jobs\GenerateResourcePermissions;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function resourcePermissionTenantConnection(): Connection
{
    config()->set('database.connections.resource_permission_tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    DB::purge('resource_permission_tenant');

    Schema::connection('resource_permission_tenant')->create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->text('description')->nullable();
        $table->string('group')->nullable();
        $table->foreignId('user_id')->nullable();

        if (config('aura.teams')) {
            $table->foreignId('team_id')->nullable();
            $table->unique(['slug', 'team_id']);
        } else {
            $table->unique('slug');
        }

        $table->timestamps();
    });

    return DB::connection('resource_permission_tenant');
}

beforeEach(function () {
    // Mock Aura::getResources() to return test resources
    Aura::shouldReceive('getResources')
        ->andReturn([
            User::class,
            Permission::class,
        ]);
});

afterEach(function () {
    DB::purge('resource_permission_tenant');
});

it('keeps serialized resource permission generation on its explicit database in both team modes', function () {
    $defaultConnection = DB::connection();
    $tenantConnection = resourcePermissionTenantConnection();
    $collision = [
        'id' => 980000,
        'name' => 'Connection Collision',
        'slug' => 'connection-collision',
        'group' => 'Connection',
        'user_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    if (config('aura.teams')) {
        $collision['team_id'] = null;
    }

    $defaultConnection->table('permissions')->insert($collision);
    $tenantConnection->table('permissions')->insert($collision);

    $job = unserialize(serialize(new GenerateResourcePermissions(
        User::class,
        $tenantConnection->getName(),
    )));
    $job->handle();

    expect($tenantConnection->table('permissions')->count())->toBe(9)
        ->and($defaultConnection->table('permissions')->count())->toBe(1)
        ->and($tenantConnection->table('permissions')->where('slug', 'view-user')->exists())->toBeTrue()
        ->and($defaultConnection->table('permissions')->where('slug', 'view-user')->exists())->toBeFalse();
});

it('refuses a queued resource permission job when its named database identity changes', function () {
    $tenantConnection = resourcePermissionTenantConnection();
    $job = new GenerateResourcePermissions(User::class, $tenantConnection->getName());
    $replacementDatabase = tempnam(sys_get_temp_dir(), 'aura-connection-drift-');

    expect($replacementDatabase)->toBeString();

    try {
        config()->set('database.connections.resource_permission_tenant.database', $replacementDatabase);
        DB::purge('resource_permission_tenant');

        expect(fn () => $job->handle())
            ->toThrow(RuntimeException::class, 'database connection identity changed');
    } finally {
        DB::purge('resource_permission_tenant');
        @unlink($replacementDatabase);
    }
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
        $attributes = [
            'name' => 'View '.$resource->pluralName(),
            'slug' => 'view-'.$resource::$slug,
            'group' => $resource->pluralName(),
        ];

        if (config('aura.teams')) {
            Permission::createGlobalForSystem($attributes);
        } else {
            Permission::withoutGlobalScopes()->create($attributes);
        }

        $initialCount = Permission::withoutGlobalScopes()->where('slug', 'view-'.$resource::$slug)->count();
        expect($initialCount)->toBe(1);

        $this->artisan('aura:create-resource-permissions')
            ->assertSuccessful();

        $finalCount = Permission::withoutGlobalScopes()->where('slug', 'view-'.$resource::$slug)->count();
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
