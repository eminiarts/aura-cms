<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\Permission;
use Illuminate\Support\Facades\Schema;
use Aura\Base\Jobs\GenerateAllResourcePermissions;

test('roles have its custom table', function () {
    $this->assertTrue(Schema::hasTable('roles'));
    
    $expectedColumns = ['id', 'team_id', 'name', 'slug', 'description', 'super_admin', 'permissions', 'created_at', 'updated_at'];
    $actualColumns = Schema::getColumnListing('roles');
    
    foreach ($expectedColumns as $column) {
        $this->assertContains($column, $actualColumns, "The roles table is missing the {$column} column.");
    }
});

test('permissions have its custom table', function () {
    $this->assertTrue(Schema::hasTable('permissions'));
    
    $expectedColumns = ['id', 'team_id', 'name', 'slug', 'created_at', 'updated_at'];
    $actualColumns = Schema::getColumnListing('permissions');
    
    foreach ($expectedColumns as $column) {
        $this->assertContains($column, $actualColumns, "The permissions table is missing the {$column} column.");
    }
});

test('when we create a team, a default super admin should be created', function () {
    $team = Team::factory()->create();
    
    $this->assertDatabaseHas('roles', [
        'team_id' => $team->id,
        'name' => 'Super Admin',
        'super_admin' => true,
    ]);
});

test('when we create a team, all permissions should be created', function () {
    $team = Team::factory()->create();
    
    $this->assertDatabaseHas('permissions', ['team_id' => $team->id]);
    $this->assertGreaterThan(0, Permission::where('team_id', $team->id)->count());
});

test('when creating permissions, we do not want to include the team resource', function () {
    GenerateAllResourcePermissions::dispatch();
    
    $this->assertDatabaseMissing('permissions', [
        'name' => 'Create Teams',
    ]);
});

test('when creating permissions, we want to create these permissions for all resources', function () {
    GenerateAllResourcePermissions::dispatch();
    
    $expectedPermissions = ['View', 'View Any', 'Create', 'Update', 'Restore', 'Delete', 'Force Delete'];
    
    foreach ($expectedPermissions as $permission) {
        $this->assertDatabaseHas('permissions', [
            'name' => "{$permission} Users",
        ]);
    }
});