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

afterAll(function () {
    // Ensure the environment variable is set before migrations run
    putenv('AURA_TEAMS=true');
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
    $this->get(route('aura.option.index'))->assertOk();
    $this->get(route('aura.user.index'))->assertOk();
    $this->get(route('aura.post.index'))->assertOk();
    $this->get(route('aura.role.index'))->assertOk();
    $this->get(route('aura.permission.index'))->assertOk();
    $this->get(route('aura.attachment.index'))->assertOk();
    $this->get(route('aura.option.index'))->assertOk();

    // Create Pages
    $this->get(route('aura.user.create'))->assertOk();
    $this->get(route('aura.post.create'))->assertOk();
    $this->get(route('aura.permission.create'))->assertOk();
    $this->get(route('aura.role.create'))->assertOk();
    $this->get(route('aura.option.create'))->assertOk();

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
    $this->get(route('aura.post.edit', ['id' => $post->id]))->assertOk();
    $this->get(route('aura.user.edit', ['id' => $user->id]))->assertOk();
    $this->get(route('aura.role.edit', ['id' => $role->id]))->assertOk();
    $this->get(route('aura.permission.edit', ['id' => $permission->id]))->assertOk();
    $this->get(route('aura.option.edit', ['id' => $option->id]))->assertOk();

    // View Pages
    $this->get(route('aura.post.view', ['id' => $post->id]))->assertOk();
    $this->get(route('aura.user.view', ['id' => $user->id]))->assertOk();
    $this->get(route('aura.role.view', ['id' => $role->id]))->assertOk();
    $this->get(route('aura.permission.view', ['id' => $permission->id]))->assertOk();
    $this->get(route('aura.option.view', ['id' => $option->id]))->assertOk();

});
