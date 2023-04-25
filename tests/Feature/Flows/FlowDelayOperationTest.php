<?php

use Aura\Flows\Resources\Flow;
use Aura\Flows\Jobs\RunOperation;
use Aura\Flows\Jobs\TriggerFlowOnCreatePostEvent;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow delay operation', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow Description',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
            // Filter more specific
        ],
    ]);

    // Create Operation and Attach to Flow
    $delayOperation = $flow->operations()->create([
        'name' => 'Delay 5s',
        'key' => 'delay',
        'type' => 'Eminiarts\\Aura\\Operations\\Delay',
        'options' => [
            'x' => 2,
            'y' => 2,
            'delay' => '1',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $delayOperation->id]);

    // Create a Resolve Operation and Attach to $delayOperation
    $resolveOperation = $flow->operations()->create([
        'name' => 'Log',
        'key' => 'log',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'message' => 'Log after 1s',
        ],
    ]);

    $delayOperation->update(['resolve_id' => $resolveOperation->id]);

    // Assert Flow has 2 Operations
    $this->assertEquals(2, $flow->operations()->count());

    // Assert $delayOperation has 1 Resolve Operation
    $this->assertEquals($delayOperation->resolve_id, $resolveOperation->id);

    Queue::fake();

    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert TriggerFlowOnPostEvent Job was pushed
    Queue::assertPushed(TriggerFlowOnCreatePostEvent::class);

    // Create a FlowLog
    $flowLog = $flow->logs()->create([
        'post_id' => 1,
        'status' => 'running',
    ]);

    // Because Queue is faked, the RunOperation Job is not pushed to the queue, so we need to run it manually
    $delayOperation->run($post, $flowLog->id);

    // Assert that Job was pushed with a delay
    Queue::assertPushed(RunOperation::class, function ($job) {
        return $job->delay->gt(now()->addSeconds(0)) && $job->delay->lt(now()->addSeconds(2));
    });
});
