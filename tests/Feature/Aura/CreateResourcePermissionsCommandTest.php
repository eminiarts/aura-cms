<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    // Mock Aura::getResources() to return test resources
    Aura::shouldReceive('getResources')
        ->andReturn([
            \Aura\Base\Resources\User::class,
            \Aura\Base\Resources\Permission::class,
        ]);
});

test('it can create permissions for resources', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);

    // Run the command
    $this->artisan('aura:create-resource-permissions')
        ->assertSuccessful()
        ->expectsOutput('Resource permissions created successfully');

    // Get all resources
    $resources = [User::class, Permission::class];

    // For each resource, verify that all necessary permissions were created
    foreach ($resources as $resource) {
        $r = app($resource);
        $slug = $r::$slug;

        // List of permissions that should exist for each resource
        $permissionTypes = [
            'view' => 'View',
            'viewAny' => 'View Any',
            'create' => 'Create',
            'update' => 'Update',
            'restore' => 'Restore',
            'delete' => 'Delete',
            'forceDelete' => 'Force Delete',
            'scope' => 'Scope'
        ];

        foreach ($permissionTypes as $type => $displayName) {
            $permission = Permission::where([
                'slug' => "{$type}-{$slug}",
                'group' => $r->pluralName(),
            ])->first();

            expect($permission)->not->toBeNull()
                ->and($permission->name)->toBe($displayName . ' ' . $r->pluralName())
                ->and($permission->group)->toBe($r->pluralName());
        }
    }
});

test('it does not duplicate existing permissions', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);

    // Create a permission manually first
    $resource = app(User::class);
    Permission::create([
        'name' => 'View ' . $resource->pluralName(),
        'slug' => 'view-' . $resource::$slug,
        'group' => $resource->pluralName(),
    ]);

    // Get initial count
    $initialCount = Permission::where('slug', 'view-' . $resource::$slug)->count();
    expect($initialCount)->toBe(1);

    // Run the command
    $this->artisan('aura:create-resource-permissions')->assertSuccessful();

    // Verify no duplicates were created
    $finalCount = Permission::where('slug', 'view-' . $resource::$slug)->count();
    expect($finalCount)->toBe(1);
});

test('it authenticates as user ID 1', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);
    
    // Run the command
    $this->artisan('aura:create-resource-permissions')->assertSuccessful();

    // Verify the authenticated user is correct
    expect(Auth::id())->toBe(1);
});

test('it creates permissions with correct naming convention', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);

    // Run the command
    $this->artisan('aura:create-resource-permissions')->assertSuccessful();

    // Test specific permission naming
    $resource = app(User::class);
    $permission = Permission::where('slug', 'view-' . $resource::$slug)->first();

    expect($permission)
        ->name->toBe('View ' . $resource->pluralName())
        ->slug->toBe('view-' . $resource::$slug)
        ->group->toBe($resource->pluralName());
});

test('it handles multiple resources correctly', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);

    // Run the command
    $this->artisan('aura:create-resource-permissions')
        ->assertSuccessful()
        ->expectsOutput('Resource permissions created successfully');

    // Count total permissions created
    $expectedPermissionsCount = 8 * 2; // 8 permissions types * 2 resources
    $actualPermissionsCount = Permission::count();

    expect($actualPermissionsCount)->toBe($expectedPermissionsCount);
});
