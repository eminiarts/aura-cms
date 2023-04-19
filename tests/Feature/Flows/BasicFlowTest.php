<?php

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - log operation', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Test Flow',
        'description' => 'Test Flow Description',
        'trigger' => 'post',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Test Operation',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,
            'message' => 'Test Message',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Test Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Test Operation']);

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

    // Assert Flow gets triggered when Post is created

    // Assert Message is in Laravel Log
    $this->assertStringContainsString('Test Message', file_get_contents(storage_path('logs/laravel.log')));

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);

    // dd($post->toArray(), $flow->toArray());
});

test('chained flow gets triggered on create post', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Test Flow',
        'description' => 'Test Flow Description',
        'trigger' => 'post',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Test Operation',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,
            'message' => 'Test Message 2',
        ],
    ]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Resolve Operation',
        'key' => 'resolve-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 14,
            'y' => 2,
            'message' => null,
        ],
    ]);

    $rejectOperation = $flow->operations()->create([
        'name' => 'Reject Operation',
        'key' => 'reject-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 14,
            'y' => 14,
            'message' => 'Reject Message',
        ],
    ]);

    // get the first operation and set resolve_id and reject_id
    $firstOperation = $flow->operations()->first();

    // dd($rejectOperation->id);
    $firstOperation->update([
        'resolve_id' => $resolveOperation->id,
        'reject_id' => $rejectOperation->id,
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(3, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Test Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Test Operation']);

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

    // Assert Message is in Laravel Log
    $this->assertStringContainsString('Test Message', file_get_contents(storage_path('logs/laravel.log')));

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
});
