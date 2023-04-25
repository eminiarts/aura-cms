<?php

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - a flow can trigger another flow', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow 1',
        'trigger' => 'post',
        'options' => [
            'resource' => Post::class,
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    $flow2 = Flow::create([
        'name' => 'Flow 2',
        'trigger' => 'flow',
        'options' => [],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Log Operation',
        'key' => 'Log-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Log triggered by Post',
        ],
    ]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Trigger the other Flow',
        'key' => 'trigger-flow',
        'type' => 'Eminiarts\\Aura\\Operations\\TriggerFlow',
        'options' => [
            'x' => 14,
            'y' => 14,
            'flow_id' => $flow2->id,
            'response' => 'post',
        ],
    ]);

    // get the first operation and set resolve_id and reject_id
    $firstOperation = $flow->operations()->first();

    // dd($rejectOperation->id);
    $firstOperation->update([
        'resolve_id' => $resolveOperation->id,
    ]);

    $flow2->operations()->create([
        'name' => 'Log Operation 2',
        'key' => 'Log-operation-2',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Log triggered by another Flow',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    $flow2->update(['operation_id' => $flow2->operations()->first()->id]);

    // Assert 2 Flows are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow 1']);
    $this->assertDatabaseHas('flows', ['name' => 'Flow 2']);

    // Assert 3 Operations are in DB
    $this->assertDatabaseHas('flow_operations', ['name' => 'Log Operation']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Trigger the other Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Log Operation 2']);

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'published',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1', 'type' => 'Post', 'status' => 'published']);

    // Assert Flow 1 is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow 1 Operation 1  is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operations()->first()->id]);

    // Assert see in operation_logs response the message of Flow 1 Operation 1
    $operationLog1 = $flow->operations()->first()->logs()->first();

    // assert $operationLog1->response json contains the message of Flow 1 Operation 1
    // dd($operationLog1->response, ['message' => 'Log triggered by Post']);
    $this->assertEquals($operationLog1->response, ['message' => 'Log triggered by Post']);

    // Assert Flow 1 Operation 2  is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operations()->get()[1]->id]);

    // Assert Flow 2 is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow2->id]);

    // Assert Flow 2 Operation 1  is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow2->operations()->first()->id]);

    // Assert see in logs message of Flow 2 Operation 1
    $this->assertDatabaseHas('flow_operation_logs', ['response' => json_encode(['message' => 'Log triggered by another Flow'])]);

    // dd($post->toArray(), $flow->toArray());
});
