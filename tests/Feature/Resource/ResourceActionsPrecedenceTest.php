<?php

use Aura\Base\BaseResource;
use Aura\Base\Resource;

/**
 * Pins the method-vs-property precedence of getActions() / getBulkActions().
 * Both resolve through the single AuraResourceActions implementation: a
 * bulkActions()/actions() method wins, otherwise the matching property is used.
 * Resource used to override getBulkActions() to read the property only; that
 * override is gone so Resource and BaseResource share one convention.
 */

// getActions(): method_exists('actions') wins over the $actions property.
class ActionsMethodResource extends Resource
{
    public array $actions = ['from-property'];

    public function actions()
    {
        return ['from-method'];
    }
}

// getActions(): no actions() method → falls back to the $actions property.
class ActionsPropertyResource extends Resource
{
    public array $actions = ['from-property'];
}

// A Resource subclass that provides BOTH a bulkActions() method and a property.
// The method wins, exactly like getActions().
class BulkActionsResourceModel extends Resource
{
    public array $bulkActions = ['from-property'];

    public function bulkActions()
    {
        return ['from-method'];
    }
}

// getBulkActions(): no bulkActions() method → falls back to the property.
class BulkActionsPropertyResource extends Resource
{
    public array $bulkActions = ['from-property'];
}

// The SAME shape on a BaseResource subclass, which must resolve identically.
class BulkActionsBaseResourceModel extends BaseResource
{
    public array $bulkActions = ['from-property'];

    public function bulkActions()
    {
        return ['from-method'];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('getActions returns the actions() method result when the method exists', function () {
    expect((new ActionsMethodResource)->getActions())->toBe(['from-method']);
});

test('getActions falls back to the $actions property when no method exists', function () {
    expect((new ActionsPropertyResource)->getActions())->toBe(['from-property']);
});

test('getBulkActions returns the bulkActions() method result when the method exists', function () {
    expect((new BulkActionsResourceModel)->getBulkActions())->toBe(['from-method']);
});

test('getBulkActions falls back to the $bulkActions property when no method exists', function () {
    expect((new BulkActionsPropertyResource)->getBulkActions())->toBe(['from-property']);
});

test('Resource and BaseResource resolve getBulkActions identically', function () {
    // One convention: method wins, property is the fallback — the same rule
    // getActions() follows, on both host classes.
    expect((new BulkActionsResourceModel)->getBulkActions())
        ->toBe((new BulkActionsBaseResourceModel)->getBulkActions());
});
