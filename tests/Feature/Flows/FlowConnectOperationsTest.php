<?php

use Eminiarts\Aura\Resources\Flow;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

uses()->group('flows');

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('flow - an operation can be connected to another operation', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
        ],
    ]);

    // asert flow is created
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);

    // Create Operation and Attach to Flow
    $firstOperation = $flow->operations()->create([
        'name' => 'Get Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Update the fetched Data',
        'key' => 'test-operation-2',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World 2',
        ],
    ]);

    $createFlow = Livewire::test(CreateFlow::class, ['model' => $flow])
        ->call('connectOperation', 'resolve', $firstOperation->id, $resolveOperation->id);

    // Assert firstOperation has resolve_id of resolveOperation->id
    $this->assertEquals($resolveOperation->id, $firstOperation->fresh()->resolve_id);
});

test('flow - operations cannot be looped', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
        ],
    ]);

    // asert flow is created
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);

    // Create Operation and Attach to Flow
    $firstOperation = $flow->operations()->create([
        'name' => 'Get Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Update the fetched Data',
        'key' => 'test-operation-2',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World 2',
        ],
    ]);

    $createFlow = Livewire::test(CreateFlow::class, ['model' => $flow])
        ->call('connectOperation', 'resolve', $firstOperation->id, $resolveOperation->id);

    // Assert firstOperation has resolve_id of resolveOperation->id
    $this->assertEquals($resolveOperation->id, $firstOperation->fresh()->resolve_id);

    $createFlow->call('connectOperation', 'resolve', $resolveOperation->id, $firstOperation->id);

    // Assert firstOperation doesnt have resolve_id of resolveOperation->id
    $this->assertNotEquals($firstOperation->id, $resolveOperation->fresh()->resolve_id);
});

test('flow - connections can be removed', function () {
    createSuperAdmin();

    // Create Flow
    $flow = Flow::create([
        'name' => 'Flow',
        'description' => 'Flow',
        'trigger' => 'post',
        'options' => [
            'resource' => 'Post',
            'event' => 'created',
        ],
    ]);

    // asert flow is created
    $this->assertDatabaseHas('flows', ['name' => 'Flow']);

    // Create Operation and Attach to Flow
    $firstOperation = $flow->operations()->create([
        'name' => 'Get Data',
        'key' => 'test-operation',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World',
        ],
    ]);

    // Attach Operation_id to flow
    $flow->update(['operation_id' => $flow->operations()->first()->id]);

    $resolveOperation = $flow->operations()->create([
        'name' => 'Update the fetched Data',
        'key' => 'test-operation-2',
        'type' => 'Eminiarts\\Aura\\Operations\\Log',
        'options' => [
            'x' => 2,
            'y' => 2,

            'message' => 'Hello World 2',
        ],
    ]);

    $createFlow = Livewire::test(CreateFlow::class, ['model' => $flow])
        ->call('connectOperation', 'resolve', $firstOperation->id, $resolveOperation->id);

    // Assert firstOperation has resolve_id of resolveOperation->id
    $this->assertEquals($resolveOperation->id, $firstOperation->fresh()->resolve_id);

    $createFlow->call('connectOperation', 'resolve', $firstOperation->id, null);

    // Assert firstOperation resolve_id is null
    $this->assertNull($firstOperation->fresh()->resolve_id);
});
