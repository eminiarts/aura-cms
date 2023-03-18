<?php

use Aura\Flows\Resources\Flow;
use Aura\Flows\Resources\Operation;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - get resources operation and manipulate them', function () {
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

    // dd($thirdPost->toArray());

    // assert three posts are in DB

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Get Children of created Post',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
        ],
    ]);

    // Create Operation and Attach to Flow
    $firstOperation = $flow->operations()->create([
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

    $resolveOperation = $flow->operations()->create([
        'name' => 'Update the fetched Data',
        'key' => 'test-operation-2',
        'type' => 'Eminiarts\\Aura\\Operations\\UpdateResource',
        'options' => [
            'x' => 2,
            'y' => 2,

            'type' => 'custom',

            'resource' => 'Eminiarts\\Aura\\Resources\\Post',
            'resource_source' => $flow->operations()->first()->id,
            'resource_ids' => ['0'],
            'data' => [
                'status' => 'published',
            ],
        ],
    ]);

    // Attach resolveOperation_id to firstOperation
    $firstOperation->update(['resolve_id' => $resolveOperation->id]);

    $thirdPost = Post::create([
        'title' => 'Post 3',
        'status' => 'draft',
        'text' => 'This is a test post updated',
        'date' => NOW(),
        'post_id' => $firstPost->id,
    ]);

    // Assert Flow has 1 Operation
    $this->assertEquals(2, $flow->operations()->count());

    // Assert Flow and Operation are in DB
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);
    $this->assertDatabaseHas('flow_operations', ['name' => 'Get Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // dd(Operation::find($resolveOperation->id)->logs()->first()->toArray());

    // Assert Post 1 and 2 are published
    $this->assertDatabaseHas('posts', ['title' => 'Child Post 1', 'status' => 'published']);
    $this->assertDatabaseHas('posts', ['title' => 'Child Post 2', 'status' => 'published']);
});
