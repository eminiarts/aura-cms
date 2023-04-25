<?php

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('current');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - create resource operation', function () {
    createSuperAdmin();


    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow Description',
        'trigger' => 'post',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    //ray()->showQueries();

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Create Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\CreateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'resource' => 'Eminiarts\\Aura\\Resources\\Attachment',
            'data' => [
                'title' => 'Post created by Flow',
                'status' => 'draft',
            ],
        ],
    ]);

    // dd($flow->operations);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);


    // ray()->stopShowingQueries();


    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Create Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Assert operation options resource is Eminiarts\Aura\Resources\Attachment
    $this->assertEquals('Eminiarts\\Aura\\Resources\\Attachment', $flow->operations()->first()->options['resource']);

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'published',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1', 'type' => 'Post', 'status' => 'published']);

    // Assert Post 2 is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Post created by Flow', 'status' => 'draft', 'type' => 'Attachment']);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
});

test('flow - cannot create post of same type on create', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow Description',
        'trigger' => 'post',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Create Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\CreateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'resource' => 'Eminiarts\\Aura\\Resources\\Post',
            'data' => [
                'title' => 'Post created by Flow',
                'status' => 'draft',
            ],
        ],
    ]);

    $rejectOperation = $flow->operations()->create([
        'name' => 'Reject Operation',
        'key' => 'reject-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 14,
            'y' => 14,
            'message' => 'Second Post was not created',
        ],
    ]);

    // get the first operation and set resolve_id and reject_id
    $firstOperation = $flow->operations()->first();

    // dd($rejectOperation->id);
    $firstOperation->update([
        'reject_id' => $rejectOperation->id,
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 2 Operation
    $this->assertEquals(2, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Create Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'published',
    ]);

    // dd('hier');

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1', 'type' => 'Post', 'status' => 'published']);

    // Assert Post 2 is not in the DB
    $this->assertDatabaseMissing('posts', ['title' => 'Post created by Flow', 'status' => 'draft', 'type' => 'Post']);

    // Assert Reject Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $rejectOperation->id]);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);

    // dd($post->toArray(), $flow->toArray());
});
