<?php

use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)->in(__DIR__);

uses()->group('fields')->in('Feature/Fields');
uses()->group('flows')->in('Feature/Flows');
uses()->group('table')->in('Feature/Table');
uses()->group('resource')->in('Feature/Resource');

uses(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createSuperAdmin()
{
    // Create Team
    $team = Team::factory()->create();

    // Create Role
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = User::find(1);
    $user->update(['fields' => ['roles' => [$role->id]]]);

    // create a entry in team_user table with team_id and user_id
    $user->teams()->attach($team->id);
}
