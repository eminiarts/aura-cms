<?php

use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    // Mock Aura::getResources() to return a test resource
    Aura::shouldReceive('getResources')
        ->andReturn([
            \Aura\Base\Resources\User::class
        ]);
});

test('it can create permissions for resources', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);
    
    // Run the command
    $this->artisan('aura:create-resource-permissions')
        ->assertSuccessful();

    // Get all resources
    $resources = [User::class];
    
    // For each resource, verify that all necessary permissions were created
    foreach ($resources as $resource) {
        $r = app($resource);
        $slug = $r::$slug;
        
        // List of permissions that should exist for each resource
        $permissionTypes = [
            'view', 'viewAny', 'create', 'update', 
            'restore', 'delete', 'forceDelete', 'scope'
        ];
        
        foreach ($permissionTypes as $type) {
            expect(Permission::where([
                'slug' => "{$type}-{$slug}",
                'group' => $r->pluralName(),
            ])->exists())->toBeTrue();
        }
    }
});

test('it does not duplicate existing permissions', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);
    
    // Run the command twice
    $this->artisan('aura:create-resource-permissions')->assertSuccessful();
    $this->artisan('aura:create-resource-permissions')->assertSuccessful();
    
    // Get the first resource to test with
    $resources = [User::class];
    $resource = app($resources[0]);
    $slug = $resource::$slug;
    
    // Check that we only have one instance of each permission
    $viewPermissions = Permission::where('slug', "view-{$slug}")->count();
    expect($viewPermissions)->toBe(1);
});
