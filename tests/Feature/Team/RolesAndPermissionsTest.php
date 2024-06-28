<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Config;
use Aura\Base\Resources\Option;
use function Pest\Livewire\livewire;

use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Jobs\GenerateAllResourcePermissions;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('roles have its custom table', function () {
});

test('permissions have its custom table', function () {
});



test('when we create a team, a default super admin should be created', function () {
});

test('when we create a team, all permissions should be created', function () {
});

test('when creating permissions, we do not want to include the team resource', function () {
 
 GenerateAllResourcePermissions::dispatch();
});


test('when creating permissions, we want to create these permissions for all resources', function () {
 
 GenerateAllResourcePermissions::dispatch();

 // Check that these permissions exist
    //  View Users
    // View Any Users
    // Create Users
    // Update Users
    // Restore Users
    // Delete Users
    // Force Delete Users
    // Scope Users 
});

// How do we allow custom Permissions? And how do we check them?
// Different Permissions Tests, scope, etc