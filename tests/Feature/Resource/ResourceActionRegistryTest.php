<?php

use Aura\Base\Contracts\ResourceActionRegistry as ResourceActionRegistryContract;
use Aura\Base\Exceptions\ResourceActionConflict;
use Aura\Base\Resource;
use Aura\Base\Services\ResourceActionRegistry;
use Aura\Base\Traits\HasActions;
use Illuminate\Auth\Access\AuthorizationException;

class ContributedActionResource extends Resource
{
    public array $actions = [
        'vendor.legacy' => ['label' => 'Resource-owned action'],
    ];
}

class ResourceActionHarness
{
    use HasActions;

    public Resource $model;
}

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('the registry contract resolves to one concrete singleton without alias recursion', function () {
    $contract = app(ResourceActionRegistryContract::class);

    expect($contract)->toBeInstanceOf(ResourceActionRegistry::class)
        ->and(app(ResourceActionRegistryContract::class))->toBe($contract)
        ->and(app(ResourceActionRegistry::class))->toBe($contract);
});

test('visibility and execution authorization are evaluated independently', function () {
    $registry = new ResourceActionRegistry;
    $resource = new ContributedActionResource;
    $handlerCalled = false;
    $registry->register('vendor.secure', [
        'label' => 'Secure action',
        'visible' => fn (): bool => true,
        'authorize' => fn (): bool => false,
        'handler' => function () use (&$handlerCalled): void {
            $handlerCalled = true;
        },
    ]);

    expect($registry->actionsFor($resource, null))->toHaveKey('vendor.secure');

    try {
        $registry->execute('vendor.secure', $resource, null);
    } catch (AuthorizationException) {
        // Expected: the server repeats execution authorization.
    }

    expect($handlerCalled)->toBeFalse();
});

test('resource-owned actions retain precedence over package-contributed keys', function () {
    $registry = app(ResourceActionRegistryContract::class);
    $registry->register('vendor.legacy', [
        'label' => 'Package action',
        'handler' => fn (): null => null,
    ]);
    $harness = new ResourceActionHarness;
    $harness->model = new ContributedActionResource;

    expect($harness->getActionsProperty()['vendor.legacy']['label'])->toBe('Resource-owned action');
});

test('duplicate names fail deterministically and worker flush restores boot baseline', function () {
    $registry = new ResourceActionRegistry;
    $definition = ['label' => 'One', 'handler' => fn (): null => null];
    $registry->register('vendor.one', $definition);
    $registry->captureBaselineState();
    $registry->register('vendor.two', ['label' => 'Two', 'handler' => fn (): null => null]);
    $registry->flushState();

    expect($registry->actionsFor(new ContributedActionResource, null))
        ->toHaveKey('vendor.one')
        ->not->toHaveKey('vendor.two');

    $registry->register('vendor.one', $definition);
})->throws(ResourceActionConflict::class, 'already registered');
