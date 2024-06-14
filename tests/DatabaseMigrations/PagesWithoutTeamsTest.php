<?php

use Aura\Base\Resources\Option;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeAll(function () {
    // Ensure the environment variable is set before migrations run
    putenv('AURA_TEAMS=false');
});

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});


test('Aura without teams - pages', function () {
    expect(config('aura.teams'))->toBeFalse();

    // expect Dashboard to be accessible
    $this->get(config('aura.path'))->assertOk();

    // Team Settings Page
    $this->get(route('aura.settings'))->assertOk();

    // Profile
    $this->get(route('aura.profile'))->assertOk();

    $user = User::first();
    $role = Role::first();

    // Config
    // $this->get(route('aura.config'))->assertOk(); // Not available atm

    // Index Pages
    $this->get(route('aura.resource.index', ['slug' => 'Option']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'User']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'Post']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'Role']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'Permission']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'Attachment']))->assertOk();
    $this->get(route('aura.resource.index', ['slug' => 'Option']))->assertOk();

    // Create Pages
    $this->get(route('aura.resource.create', ['slug' => 'User']))->assertOk();
    $this->get(route('aura.resource.create', ['slug' => 'Post']))->assertOk();
    $this->get(route('aura.resource.create', ['slug' => 'Permission']))->assertOk();
    $this->get(route('aura.resource.create', ['slug' => 'Role']))->assertOk();
    $this->get(route('aura.resource.create', ['slug' => 'Option']))->assertOk();

    $post = Post::create([
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test Post Content',
    ]);

    $permission = Permission::create([
        'name' => 'Test Permission',
        'slug' => 'test-permission',
        'description' => 'Test Permission Description',
    ]);

    $option = Option::create([
        'name' => 'Test Option',
        'value' => 'test-option',
    ]);

    // Edit Pages
    $this->get(route('aura.resource.edit', ['slug' => 'Post', 'id' => $post->id]))->assertOk();
    $this->get(route('aura.resource.edit', ['slug' => 'User', 'id' => $user->id]))->assertOk();
    $this->get(route('aura.resource.edit', ['slug' => 'Role', 'id' => $role->id]))->assertOk();
    $this->get(route('aura.resource.edit', ['slug' => 'Permission', 'id' => $permission->id]))->assertOk();
    $this->get(route('aura.resource.edit', ['slug' => 'Option', 'id' => $option->id]))->assertOk();

    // View Pages
    $this->get(route('aura.resource.view', ['slug' => 'Post', 'id' => $post->id]))->assertOk();
    $this->get(route('aura.resource.view', ['slug' => 'User', 'id' => $user->id]))->assertOk();
    $this->get(route('aura.resource.view', ['slug' => 'Role', 'id' => $role->id]))->assertOk();
    $this->get(route('aura.resource.view', ['slug' => 'Permission', 'id' => $permission->id]))->assertOk();
    $this->get(route('aura.resource.view', ['slug' => 'Option', 'id' => $option->id]))->assertOk();

});
