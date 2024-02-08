<?php

use Aura\Flows\Resources\Flow;
use Aura\Base\Models\User;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));
// beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()) && $this->skip('All tests are skipped.'));

test('flow gets triggered on create post and sends a notification to a user', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Notification Flow',
        'description' => 'Notification Flow Description',
        'trigger' => 'resource',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Send Notification',
        'key' => 'notification',
        'type' => 'Aura\\Base\\Operations\\Notification',
        'options' => [
            'x' => 2,
            'y' => 2,
            'message' => 'Post has been created',
            'type' => 'user',
            'user_id' => $this->user->id,
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Notification Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Send Notification']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

    // Assert db notification  has one notification
    $this->assertEquals(1, DB::table('notifications')->count());

    $notification = DB::table('notifications')->first();

    // Assert Notification has correct data
    $this->assertEquals('Post has been created', json_decode($notification->data)->message);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
})->skip();

test('flow gets triggered on create post and sends a notification to a role', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Notification Flow',
        'description' => 'Notification Flow Description',
        'trigger' => 'resource',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Send Notification',
        'key' => 'notification',
        'type' => 'Aura\\Base\\Operations\\Notification',
        'options' => [
            'x' => 2,
            'y' => 2,
            'message' => 'Post has been created',
            'type' => 'role',
            'role_id' => $this->user->resource->roles->first()->id,
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Notification Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Send Notification']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

    // Assert db notification  has one notification
    $this->assertEquals(1, DB::table('notifications')->count());

    $notification = DB::table('notifications')->first();

    // Assert Notification has correct data
    $this->assertEquals('Post has been created', json_decode($notification->data)->message);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
})->skip();
