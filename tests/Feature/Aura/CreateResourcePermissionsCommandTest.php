<?php

use Aura\Base\Resources\Permission;
use Aura\Base\Resources\User;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    // Clear permissions table
    Schema::dropIfExists('permissions');

    // Create permissions table with the correct structure
    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('title');
        $table->string('slug')->unique();
        $table->string('group')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
        $table->timestamps();
    });
});

it('can create permissions for resources', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);
    
    // Run the command
    $this->artisan('aura:create-resource-permissions')
        ->assertExitCode(0);

    // Get all resources
    $resources = Aura::getResources();
    
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
            $this->assertDatabaseHas('permissions', [
                'slug' => "{$type}-{$slug}",
                'group' => $r->pluralName(),
            ]);
        }
    }
});

it('does not duplicate existing permissions', function () {
    // Create a user for authentication
    $user = User::factory()->create(['id' => 1]);
    
    // Run the command twice
    $this->artisan('aura:create-resource-permissions');
    $this->artisan('aura:create-resource-permissions');
    
    // Get the first resource to test with
    $resources = Aura::getResources();
    $resource = app($resources[0]);
    $slug = $resource::$slug;
    
    // Check that we only have one instance of each permission
    $viewPermissions = Permission::where('slug', "view-{$slug}")->count();
    expect($viewPermissions)->toBe(1);
});
