<?php

use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Resources\Permission;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Taxonomies\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Aura without teams - pages', function () {
    // Set config to not use teams
    config(['aura.teams' => false]);

    expect(config('aura.teams'))->toBeFalse();

    // Rerun migrations
    $this->artisan('migrate:fresh', ['--env' => 'testing']);
    $this->getEnvironmentSetUp($this->app);

    // Create User
    $user = User::factory()->create();

    // Create Role
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach to User
    $user = User::find($user->id);
    $user->update(['fields' => ['roles' => [$role->id]]]);

    // Refresh User
    $user = $user->refresh();
    $this->actingAs($user);

    // expect Dashboard to be accessible
    $this->get(config('aura.path'))->assertOk();

    // Team Settings Page
    $this->get(route('aura.team.settings'))->assertOk();

    // Profile
    $this->get(route('aura.profile'))->assertOk();

    // Config
    $this->get(route('aura.config'))->assertOk();

    // Index Pages
    $this->get(route('aura.post.index', ['slug' => 'Option']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'User']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'Post']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'Role']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'Permission']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'Attachment']))->assertOk();
    $this->get(route('aura.post.index', ['slug' => 'Option']))->assertOk();

    // Create Pages
    $this->get(route('aura.post.create', ['slug' => 'User']))->assertOk();
    $this->get(route('aura.post.create', ['slug' => 'Post']))->assertOk();
    $this->get(route('aura.post.create', ['slug' => 'Permission']))->assertOk();
    $this->get(route('aura.post.create', ['slug' => 'Role']))->assertOk();
    $this->get(route('aura.post.create', ['slug' => 'Option']))->assertOk();

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
    $this->get(route('aura.post.edit', ['slug' => 'Post', 'id' => $post->id]))->assertOk();
    $this->get(route('aura.post.edit', ['slug' => 'User', 'id' => $user->id]))->assertOk();
    $this->get(route('aura.post.edit', ['slug' => 'Role', 'id' => $role->id]))->assertOk();
    $this->get(route('aura.post.edit', ['slug' => 'Permission', 'id' => $permission->id]))->assertOk();
    $this->get(route('aura.post.edit', ['slug' => 'Option', 'id' => $option->id]))->assertOk();

    // View Pages
    $this->get(route('aura.post.view', ['slug' => 'Post', 'id' => $post->id]))->assertOk();
    $this->get(route('aura.post.view', ['slug' => 'User', 'id' => $user->id]))->assertOk();
    $this->get(route('aura.post.view', ['slug' => 'Role', 'id' => $role->id]))->assertOk();
    $this->get(route('aura.post.view', ['slug' => 'Permission', 'id' => $permission->id]))->assertOk();
    $this->get(route('aura.post.view', ['slug' => 'Option', 'id' => $option->id]))->assertOk();

    // Taxonomies
    $this->get(route('aura.taxonomy.index', ['slug' => 'Tag']))->assertOk();
    $this->get(route('aura.taxonomy.index', ['slug' => 'Category']))->assertOk();
    $this->get(route('aura.taxonomy.create', ['slug' => 'Tag']))->assertOk();
    $this->get(route('aura.taxonomy.create', ['slug' => 'Category']))->assertOk();

    $tag = Tag::create([
        'name' => 'Test Taxonomy',
        'slug' => 'test-taxonomy',
        'description' => 'Test Taxonomy Description',
    ]);

    $this->get(route('aura.taxonomy.view', ['slug' => 'Tag', 'id' => $tag->id]))->assertOk();
    $this->get(route('aura.taxonomy.edit', ['slug' => 'Tag', 'id' => $tag->id]))->assertOk();
});