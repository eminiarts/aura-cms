<?php

use Eminiarts\Aura\Models\User;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - delete resource operation', function () {
    createSuperAdmin();

    // Create a Post
    $firstPost = Post::create([
        'title' => 'Post to be deleted by Flow 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    $secondPost = Post::create([
        'title' => 'Post to be deleted by Flow 2',
        'content' => 'Test Content 2',
        'type' => 'Post',
        'status' => 'draft',
    ]);

    // assert two posts are in DB
    $this->assertDatabaseHas('posts', ['title' => 'Post to be deleted by Flow 1']);
    $this->assertDatabaseHas('posts', ['title' => 'Post to be deleted by Flow 2']);

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
    $flow->operations()->create([
        'name' => 'Create Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\DeleteResource',
        'options' => [
            'x' => 2,
            'y' => 2,
            'type' => 'custom',
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
    $this->assertDatabaseHas('flow_operations', ['name' => 'Create Data']);

    // Test $flow->operation_id is $flow->operations()->first()->id
    $this->assertEquals($flow->operation_id, $flow->operations()->first()->id);

    // Create a Post
    $thirdPost = Post::create([
        'title' => 'Test Post 1',
        'content' => 'Test Content 1',
        'type' => 'Post',
        'status' => 'published',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post 1', 'type' => 'Post', 'status' => 'published']);

    // Assert firstPost and secondPost are deleted
    $this->assertDatabaseMissing('posts', ['title' => 'Post to be deleted by Flow 1']);

    // Assert Post is soft deleted
    // $this->assertSoftDeleted('posts', ['title' => 'Post to be deleted by Flow 2']);

    $this->assertDatabaseMissing('posts', ['title' => 'Post to be deleted by Flow 2']);

    // Assert Flow is triggered when Post is created
    $this->assertDatabaseHas('flow_logs', ['flow_id' => $flow->id]);

    // Assert Flow Operation is triggered when Post is created
    $this->assertDatabaseHas('flow_operation_logs', ['operation_id' => $flow->operation_id]);
});
