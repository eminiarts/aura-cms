<?php

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('a super admin can perform any action', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $r = Role::first();

    // assert the role is a super admin
    $this->assertTrue($r->fields['super_admin']);

    // Assert name is Super Admin
    $this->assertEquals('Super Admin', $r->title);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);

    $user->update(['fields' => ['roles' => [$r->id]]]);

    // Assert User can do everything with users
    $this->assertTrue($this->user->can('viewAny-users'));
    $this->assertTrue($this->user->can('view-users'));
    $this->assertTrue($this->user->can('create-users'));
    $this->assertTrue($this->user->can('update-users'));
    $this->assertTrue($this->user->can('restore-users'));
    $this->assertTrue($this->user->can('delete-users'));
    $this->assertTrue($this->user->can('forceDelete-users'));

    // Assert User can List posts
    $this->assertTrue($this->user->can('viewAny-posts'));

    // User can Do anything
    $this->assertTrue($this->user->can('do-anything'));
});

test('a admin can perform assigned actions', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => true,
        'update-post' => true,
        'restore-post' => true,
        'delete-post' => true,
        'forceDelete-post' => false,
    ]]);

    // Create Post
    $post = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Admin', $r->fields['name']);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);
    $user->update(['fields' => ['roles' => [$r->id]]]);
    $user->refresh();

    // Assert User can do everything with posts
    $this->assertTrue($user->can('viewAny', $post));
    $this->assertTrue($user->can('view', $post));
    $this->assertTrue($user->can('create', $post));
    $this->assertTrue($user->can('update', $post));
    $this->assertTrue($user->can('restore', $post));
    $this->assertTrue($user->can('delete', $post));

    // User can not force delete
    $this->assertFalse($user->can('forceDelete', $post));
    $this->assertTrue($user->cannot('forceDelete', $post));

    // User can not do anything
    $this->assertFalse($user->can('do-anything'));
});

test('a moderator can only view posts but not edit them', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Moderator', 'slug' => 'admin', 'name' => 'Moderator', 'description' => ' Moderator has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => false,
        'update-post' => false,
        'restore-post' => false,
        'delete-post' => false,
        'forceDelete-post' => false,
    ]]);

    // Create Post
    $post = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Moderator', $r->fields['name']);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);
    $user->update(['fields' => ['roles' => [$r->id]]]);
    $user->refresh();

    // Assert Permissions
    $this->assertTrue($user->can('viewAny', $post));
    $this->assertTrue($user->can('view', $post));
    $this->assertTrue($user->cannot('create', $post));
    $this->assertTrue($user->cannot('update', $post));
    $this->assertTrue($user->cannot('restore', $post));
    $this->assertTrue($user->cannot('delete', $post));

    // User can not do anything
    $this->assertFalse($user->can('do-anything'));
});

test('a moderator can access index page', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Moderator', 'slug' => 'admin', 'name' => 'Moderator', 'description' => ' Moderator has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => false,
        'update-post' => false,
        'restore-post' => false,
        'delete-post' => false,
        'forceDelete-post' => false,
    ]]);

    // Create Post
    $post = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Moderator', $r->fields['name']);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);
    $user->update(['fields' => ['roles' => [$r->id]]]);

    $user->refresh();

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.post.index', ['slug' => $post->type]));

    // Assert Response
    $response->assertStatus(200);

    // Can Not Access Create Page
    $response = $this->actingAs($user)->get(route('aura.post.create', ['slug' => $post->type]));

    // Assert Response
    $response->assertStatus(403);

    // Can Not Access Edit Page
    $response = $this->actingAs($user)->get(route('aura.post.edit', ['slug' => $post->type,  'id' => $post->id]));

    // Assert Response
    $response->assertStatus(403);
});

test('a admin can access all pages', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => true,
        'update-post' => true,
        'restore-post' => true,
        'delete-post' => true,
        'forceDelete-post' => false,
    ]]);

    // Create Post
    $post = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => []]);

    // without exception handling
    // $this->withoutExceptionHandling();

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Admin', $r->fields['name']);

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);
    $user->update(['fields' => ['roles' => [$r->id]]]);

    $user->refresh();

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.post.index', ['slug' => $post->type]));

    // Assert Response
    $response->assertStatus(200);

    // Can Not Access Create Page
    $response = $this->actingAs($user)->get(route('aura.post.create', ['slug' => $post->type]));

    // Assert Response
    $response->assertStatus(200);

    // Can Not Access Edit Page
    $response = $this->actingAs($user)->get(route('aura.post.edit', ['slug' => $post->type, 'id' => $post->id]));

    // Assert Response
    $response->assertStatus(200);
});

test('scoped posts', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => true,
        'update-post' => true,
        'restore-post' => true,
        'delete-post' => true,
        'forceDelete-post' => false,
        'scope-post' => true,
    ]]);

    // Second User
    $user2 = User::factory()->create();

    // Create Post
    $post = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => [], 'user_id' => 1]);

    // Post 2
    $post2 = Post::create(['type' => 'Post', 'title' => 'Test Post', 'slug' => 'test-post', 'name' => 'Test Post', 'description' => 'Test Post', 'fields' => [], 'user_id' => 2]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Post', 'id' => $post->id]);
    $this->assertDatabaseHas('posts', ['type' => 'Post', 'id' => $post2->id]);

    $r = Role::first();

    // Attach to User
    $this->user->resource->update(['fields' => ['roles' => [$r->id]]]);

    $user = \Eminiarts\Aura\Resources\User::find(1);

    $user2->resource->update(['fields' => ['roles' => [$r->id]]]);

    $user2->refresh();

    // User 1

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.post.index', ['slug' => $post->type]));

    // Assert Response
    $response->assertStatus(200);

    // Can Access Edit Page of Post 1
    $response = $this->actingAs($user)->get(route('aura.post.edit', ['slug' => $post->type, 'id' => $post->id]));

    // Assert Response
    $response->assertStatus(200);

    // Can not access Edit Page of Post 2
    $response = $this->actingAs($user)->get(route('aura.post.edit', ['slug' => $post2->type, 'id' => $post2->id]));

    // Assert Response is unauthorized
    $response->assertStatus(403);

    // User 2

    // Can not access Edit Page of Post 1
    $response = $this->actingAs($user2)->get(route('aura.post.edit', ['slug' => $post->type, 'id' => $post->id]));

    // Assert Response
    $response->assertStatus(403);

    // User 2 can Edit Post 2
    $response = $this->actingAs($user2)->get(route('aura.post.edit', ['slug' => $post2->type, 'id' => $post2->id]));

    // Assert Response
    $response->assertStatus(200);
});

test('scoped query on index page', function () {
    // Make Sure Query is scoped on Index
})->todo();

test('user can only delete his own posts', function () {
    // Make Sure Query is scoped on Delete
})->todo();

test('a admin can access users', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-user' => true,
        'view-user' => true,
        'create-user' => true,
        'update-user' => true,
        'restore-user' => true,
        'delete-user' => true,
        'scope-user' => false,
        'forceDelete-user' => false,
    ]]);

    // Create Post
    $post = User::factory()->create();

    // assert there is a role in the db
    $this->assertDatabaseHas('users', ['id' => $post->id]);

    $r = Role::first();

    // Attach to User
    $user = \Eminiarts\Aura\Resources\User::find(1);
    $user->update(['fields' => ['roles' => [$r->id]]]);

    // Access Index Page
    $response = $this->actingAs($this->user)->get(route('aura.post.index', ['slug' => 'User']));

    // Assert Response
    $response->assertStatus(200);

    // Can Access Create Page
    $response = $this->actingAs($this->user)->get(route('aura.post.create', ['slug' => 'User']));

    // Assert Response
    $response->assertStatus(200);

    // Can Access Edit Page
    $response = $this->actingAs($this->user)->get(route('aura.post.edit', ['slug' => 'User', 'id' => $post->id]));

    // Assert Response
    $response->assertStatus(200);
});
