<?php

use Aura\Base\Resources\Post;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\TestCase;
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

function createPost($type) {
    return Post::factory()->create(['type' => $type]);
}

function createSuperAdmin()
{
    $user = User::factory()->create();

    auth()->login($user);

    // Create Team
    $team = Team::factory()->create();

    $user->refresh();

    return $user;
}
