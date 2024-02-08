<?php

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - get resources operation', function () {
    createSuperAdmin();

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Child Post 1',
        'status' => 'draft',
    ]);
    $secondPost = Post::create([
        'title' => 'Child Post 2',
        'status' => 'draft',
    ]);

    $thirdPost = Post::create([
        'title' => 'Post 3',
        'status' => 'draft',
        'text' => 'This is a test post updated',
        'date' => NOW(),
        'post_id' => $firstPost->id,
    ]);

    // dd($thirdPost->toArray());

    // assert three posts are in DB

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Get Children of updated Post',
        'trigger' => 'resource',
        'options' => [
            'resource' => Post::class,
            'event' => 'updated',
        ],
    ]);

    // Create Operation and Attach to Flow
    $flow->operations()->create([
        'name' => 'Get Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\GetResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'resource' => 'Eminiarts\\Aura\\Resources\\Post',
            'resource_ids' => [$firstPost->id, $secondPost->id],
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    // Assert Flow has 1 Operation
    $this->assertEquals(1, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Get Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Set Post 3 to published
    $thirdPost->update(['status' => 'published']);

    // Assert Post 3 is published
    $this->assertDatabaseHas('posts', ['title' => 'Post 3', 'status' => 'published']);

    // dd the operation_log
    $operationResponse = $flow->operation()->first()->logs()->first()->response;
    // dd(json_decode($operationResponse, true));

    // Assert Operation Response has 2 Posts
    $this->assertEquals(2, count($operationResponse));

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
})->skip();
