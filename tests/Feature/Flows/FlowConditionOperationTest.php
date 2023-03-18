<?php

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow condition operation', function () {
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

    // In Laravel

    // Create Operation and Attach to Flow
    $delayOperation = $flow->operations()->create([
        'name' => 'Condition',
        'key' => 'condition',
        'type' => 'Eminiarts\\Aura\\Operations\\Condition',
        'options' => [
            'x' => 2,
            'y' => 2,
            'condition' => [
                'return $post->user_id == 1;',
            ],
        ],
    ]);

    // I want to be able to create a condition operation: let's say we start with a simple condition: auth()->user()->id === 1, how do I create this condition? and how do i save the condition to the database?

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $delayOperation->id]);

    // Create a Resolve Operation and Attach to $delayOperation
    $resolveOperation = $flow->operations()->create([
        'name' => 'Log',
        'key' => 'log',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'message' => 'Condition is true',
        ],
    ]);

    $delayOperation->update(['resolve_id' => $resolveOperation->id]);

    // Create a Reject Operation and Attach to $delayOperation
    $rejectOperation = $flow->operations()->create([
        'name' => 'Log',
        'key' => 'log',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'message' => 'Condition is false',
        ],
    ]);

    $delayOperation->update(['reject_id' => $rejectOperation->id]);

    // Assert Flow has 2 Operations
    $this->assertEquals(3, $flow->operations()->count());
    $this->assertEquals($delayOperation->resolve_id, $resolveOperation->id);
    $this->assertEquals($delayOperation->reject_id, $rejectOperation->id);

    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);
});
