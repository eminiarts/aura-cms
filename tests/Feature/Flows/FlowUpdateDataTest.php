<?php

use Aura\Base\Models\User;
use Aura\Base\Resources\Post;
use Aura\Flows\Resources\Flow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - data update operation', function () {
    createSuperAdmin();

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow Description',
        'trigger' => 'resource',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Update Data',
        'key' => 'test-operation',
        'type' => 'Aura\\Base\\Operations\\UpdateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'type' => 'custom',
            'resource' => 'Aura\\Base\\Resources\\Post',
            'resource_ids' => [$firstPost->id],
            'data' => [
                'title' => 'Test Post 1 Updated',
                'status' => 'published',
            ],
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Update Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $secondPost = Post::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content 2',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 2']);

    // Assert Post 1 is updated
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1 Updated', 'status' => 'published']);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);

    // dd($post->toArray(), $flow->toArray());
})->skip();

test('flow - data update multiple operation', function () {
    createSuperAdmin();

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'draft',
    ]);
    $secondPost = Post::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content 2',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow Description',
        'trigger' => 'resource',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Update Data',
        'key' => 'test-operation',
        'type' => 'Aura\\Base\\Operations\\UpdateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'type' => 'custom',
            'resource' => 'Aura\\Base\\Resources\\Post',
            'resource_ids' => [$firstPost->id, $secondPost->id],
            'data' => [
                'status' => 'published',
            ],
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Update Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $thirdPost = Post::create([
        'title' => 'Test Post 3',
        'content' => 'Test Content 3',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 2']);

    // Assert Post 1 and 2 are updated and published
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1', 'status' => 'published']);
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 2', 'status' => 'published']);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);

    // dd($post->toArray(), $flow->toArray());
})->skip();
