<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Schema;

// Before each test, create a Superadmin and login
beforeEach(function () {

    // Set teams to false for this test
    config(['aura.teams' => false]);

    // Create a fresh database schema
    $this->artisan('migrate:fresh');

    // Run our specific migration
    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $this->actingAs($this->user = createSuperAdminWithoutTeam());

    Aura::fake();
    Aura::setModel(new Post);
});

afterEach(function () {
    // Restore original config value
    config(['aura.teams' => true]);
});

test('Aura without teams - pages', function () {

    $this->withoutExceptionHandling();

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

    Aura::setModel(new Permission);
    $this->get(route('aura.permission.create'))->assertOk();

    Aura::setModel(new Role);
    $this->get(route('aura.role.create'))->assertOk();

    Aura::setModel(new Option);
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
