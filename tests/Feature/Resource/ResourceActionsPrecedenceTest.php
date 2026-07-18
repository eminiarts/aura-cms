<?php

use Aura\Base\BaseResource;
use Aura\Base\Resource;

/**
 * Characterization tests for getActions() / getBulkActions() probing (design §1
 * ResourceActions, §5 step 5). Pins the method-vs-property precedence AND the
 * fact that Resource::getBulkActions() (the class override on Resource.php:172)
 * wins over the AuraModelConfig trait version — the two behave differently.
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
// Resource::getBulkActions() (the class override) returns the PROPERTY, ignoring
// the method entirely.
class BulkActionsResourceModel extends Resource
{
    public array $bulkActions = ['from-property'];

    public function bulkActions()
    {
        return ['from-method'];
    }
}

// The SAME shape on a BaseResource subclass. BaseResource does NOT override
// getBulkActions(), so it uses the AuraModelConfig trait version, which DOES
// call the bulkActions() method.
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

test('Resource::getBulkActions (class override) returns the property, ignoring the bulkActions() method', function () {
    // Resource.php:172 overrides getBulkActions() to `return $this->bulkActions;`
    // — the method_exists('bulkActions') branch of the trait is never reached.
    expect((new BulkActionsResourceModel)->getBulkActions())->toBe(['from-property']);
});

test('BaseResource (trait version) calls the bulkActions() method instead of returning the property', function () {
    // BaseResource has no getBulkActions() override, so the AuraModelConfig trait
    // version runs and its method_exists('bulkActions') branch wins.
    expect((new BulkActionsBaseResourceModel)->getBulkActions())->toBe(['from-method']);
});

test('the two getBulkActions implementations diverge for identical method+property shapes', function () {
    // This is the concrete difference the design flags as "duplicated": the class
    // override (Resource) is property-only; the trait version (BaseResource) is
    // method-first. The decomposition MUST keep both.
    $resource = new BulkActionsResourceModel;
    $base = new BulkActionsBaseResourceModel;

    expect($resource->getBulkActions())->toBe(['from-property'])
        ->and($base->getBulkActions())->toBe(['from-method'])
        ->and($resource->getBulkActions())->not->toBe($base->getBulkActions());
});
