<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('a super admin can perform any action', function () {
    $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('roles', ['id' => $role->id]);

    $r = Role::first();

    // assert the role is a super admin
    $this->assertTrue($r->fields['super_admin']);

    // Assert name is Super Admin
    $this->assertEquals('Super Admin', $r->name);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);

    $user->update(['roles' => [$r->id]]);

    // dd(auth()->user()->can('viewAny', $user));

    // Assert User can do everything with users
    expect($this->user->can('viewAny', $user))->toBeTrue();
    expect($this->user->can('view', $user))->toBeTrue();
    expect($this->user->can('create', $user))->toBeTrue();
    expect($this->user->can('update', $user))->toBeTrue();
    expect($this->user->can('restore', $user))->toBeTrue();
    expect($this->user->can('delete', $user))->toBeTrue();
    expect($this->user->can('forceDelete', $user))->toBeTrue();

    // Assert User can List posts
    expect($this->user->hasPermission('viewAny-posts'))->toBeTrue();

    // User can Do anything
    expect($this->user->hasPermission('do-anything'))->toBeTrue();
});

test('a admin can perform assigned actions', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
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
    $this->assertDatabaseHas('roles', ['id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Admin', $r->name);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);
    $user->update(['roles' => [$r->id]]);
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
    $role = Role::create(['name' => 'Moderator', 'slug' => 'admin', 'name' => 'Moderator', 'description' => ' Moderator has can perform almost everything.', 'super_admin' => false, 'permissions' => [
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
    $this->assertDatabaseHas('roles', ['id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Moderator', $r->name);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);
    $user->update(['roles' => [$r->id]]);
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

    // $this->withoutExceptionHandling();

    $role = Role::create(['name' => 'Moderator', 'slug' => 'admin', 'description' => ' Moderator has can perform almost everything.', 'super_admin' => false, 'permissions' => [
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
    $this->assertDatabaseHas('roles', ['id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Moderator', $r->name);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);
    $user->update(['roles' => [$r->id]]);

    $user->refresh();

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.index'));

    // Assert Response
    $response->assertStatus(200);

    // Attempt to Access Create Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.create'));

    // Assert that the action is unauthorized
    $response->assertForbidden();

    // Can Not Access Edit Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.edit', ['id' => $post->id]));

    // Assert Response
    $response->assertStatus(403);
});

test('a admin can access all pages', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
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

    $this->assertDatabaseHas('roles', ['id' => $role->id]);

    $r = Role::first();

    // assert the role is not a super admin
    $this->assertNotTrue($r->fields['super_admin']);

    // Assert name is Admin
    $this->assertEquals('Admin', $r->name);

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);
    $user->update(['roles' => [$r->id]]);

    $user->refresh();

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.index'));

    // dd($response);

    // Assert Response
    $response->assertStatus(200);

    // Can Not Access Create Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.create'));

    // Assert Response
    $response->assertStatus(200);

    // Can Not Access Edit Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.edit', ['id' => $post->id]));

    // Assert Response
    $response->assertStatus(200);
});

test('scoped posts', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
        'viewAny-post' => true,
        'view-post' => true,
        'create-post' => true,
        'update-post' => true,
        'restore-post' => true,
        'delete-post' => true,
        'forceDelete-post' => false,
        'scope-post' => true,
    ]]);

    // $this->withoutExceptionHandling();

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
    $this->user->update(['roles' => [$r->id]]);

    $user = \Aura\Base\Resources\User::find(1);

    $user2->update(['roles' => [$r->id]]);

    $user2->refresh();

    // User 1
    // $this->withoutExceptionHandling();

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.index'));

    // Assert Response
    $response->assertStatus(200);

    // Can Access Edit Page of Post 1
    $response = $this->actingAs($user)->get(route('aura.'.$post->getSlug().'.edit', ['id' => $post->id]));

    // Assert Response
    $response->assertStatus(200);

    // Can not access Edit Page of Post 2
    $response = $this->actingAs($user)->get(route('aura.'.$post2->getSlug().'.edit', ['id' => $post2->id]));

    // Assert Response is unauthorized
    $response->assertStatus(403);

    // User 2

    // Can not access Edit Page of Post 1
    $response = $this->actingAs($user2)->get(route('aura.'.$post->getSlug().'.edit', ['id' => $post->id]));

    // Assert Response
    $response->assertStatus(403);

    // User 2 can Edit Post 2
    $response = $this->actingAs($user2)->get(route('aura.'.$post2->getSlug().'.edit', ['id' => $post2->id]));

    // Assert Response
    $response->assertStatus(200);
});

test('a admin can access users', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => ' Admin has can perform almost everything.', 'super_admin' => false, 'permissions' => [
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

    // dd($post->toArray());

    // assert there is a role in the db
    $this->assertDatabaseHas('users', ['id' => $post->id]);

    $r = Role::first();

    // Attach to User
    $user = \Aura\Base\Resources\User::find(1);
    $user->update(['roles' => [$r->id]]);

    // Assert User has Admin Role
    $this->assertTrue($user->roles->contains('slug', 'admin'));

    // Access Index Page
    $response = $this->actingAs($user)->get(route('aura.user.index'));

    // Assert Response
    $response->assertStatus(200);

    // Can Access Create Page
    $response = $this->actingAs($user)->get(route('aura.user.create'));
    // Assert Response
    $response->assertStatus(200);

    $newUser = User::factory()->create();

    // Can Access Edit Page
    $response = $this->actingAs($user)->get(route('aura.user.edit', ['id' => $newUser->id]));

    // Assert Response
    $response->assertStatus(200);
});

test('scoped query on index page', function () {
    // Create a role with necessary permissions
    $role = Role::create([
        'name' => 'Post Viewer',
        'slug' => 'post_viewer',
        'description' => 'Can view posts',
        'super_admin' => false,
        'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'scope-post' => true,
        ],
    ]);

    // Create a user and assign the role
    $user = User::factory()->create();
    $user->update(['roles' => [$role->id]]);
    $this->actingAs($user);

    // Create posts
    $userPosts = Post::factory()->count(3)->create(['user_id' => $user->id]);
    $otherPosts = Post::factory()->count(2)->create();

    // Make the request
    $response = $this->get(route('aura.post.index'));

    // Assert the response
    $response->assertStatus(200);
    $response->assertSee($userPosts[0]->title)
        ->assertSee($userPosts[1]->title)
        ->assertSee($userPosts[2]->title)
        ->assertDontSee($otherPosts[0]->title)
        ->assertDontSee($otherPosts[1]->title);
});

test('user can only delete his own posts', function () {
    // Create a role with necessary permissions
    $role = Role::create([
        'name' => 'Post Manager',
        'slug' => 'post_manager',
        'description' => 'Can manage own posts',
        'super_admin' => false,
        'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'create-post' => true,
            'update-post' => true,
            'delete-post' => true,
            'scope-post' => true,
        ],
    ]);

    // Create a user and assign the role
    $user = User::factory()->create();
    $user->update(['roles' => [$role->id]]);
    $this->actingAs($user);

    $userPost = Post::factory()->create(['user_id' => $user->id]);
    $otherPost = Post::factory()->create();

    Aura::fake();
    Aura::setModel($userPost);

    // Attempt to delete user's own post
    Livewire::test(Edit::class, ['id' => $userPost->id])
        ->call('singleAction', 'delete')
        ->assertDispatched('notify')
        ->assertSuccessful();

    $this->assertDatabaseMissing('posts', ['id' => $userPost->id]);

    // Attempt to delete another user's post
    Livewire::test(Edit::class, ['slug' => 'Post', 'id' => $otherPost->id])
        ->assertForbidden();

    $this->assertDatabaseHas('posts', ['id' => $otherPost->id]);
});
