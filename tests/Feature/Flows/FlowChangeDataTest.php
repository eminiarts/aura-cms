<?php

use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Flow;
use Eminiarts\Aura\Resources\OperationLog;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - a flow can change data and pass it to the next operation', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow 1',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Update Operation',
        'key' => 'update-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\UpdateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'type' => 'Post',
            'data' => [
                'title' => 'Updated Title',
                'content' => 'Updated Content',
            ],
        ],
    ]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Log Completed',
        'key' => 'log-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 14,
            'y' => 14,

            'message' => 'Updated Title: {{ $post->title }}',
        ],
    ]);

    // get the first operation and set resolve_id and reject_id
    $firstOperation = $flow->operations()->first();

    // dd($rejectOperation->id);
    $firstOperation->update([
        'resolve_id' => $resolveOperation->id,
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert 2 Flows are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow 1']);

    // Assert 3 Operations are in DB
    $this->assertDatabaseHas('flow_operations', ['name' => 'Update Operation']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Log Completed']);

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'published',
    ]);

    // dd($firstPost);

    // Expect $post->exists to be true
    expect($firstPost->exists)->toBeTrue();

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Updated Title', 'type' => 'Post', 'status' => 'published']);

    // Assert Flow 1 is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow 1 Operation 1  is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operations()->first()->id]);

    // Assert Operation 2 Log options message in DB is correct
    // dd last operation log
    $log = OperationLog::orderBy('id', 'desc')->first();
    expect($log->response['message'])->toBe('Updated Title: Updated Title');

    // dd(OperationLog::orderBy('id', 'desc')->first());
    $this->assertDatabaseHas('flow_operation_logs', ['response->message' => 'Updated Title: Updated Title']);
});
