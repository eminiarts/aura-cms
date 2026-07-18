<?php

use Aura\Base\BaseResource;
use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Resource;

/**
 * Pins the explicit field-definition contract introduced in issue #40 step 8.
 * Both host classes must declare `implements DefinesFields`, and the untyped
 * `[]` default from AuraResourceConfiguration must still satisfy it for a bare
 * BaseResource subclass.
 */
class DefinesFieldsBareBaseResource extends BaseResource {}

test('Resource implements the DefinesFields contract', function () {
    expect(is_subclass_of(Resource::class, DefinesFields::class))->toBeTrue();
});

test('BaseResource implements the DefinesFields contract', function () {
    expect(is_subclass_of(BaseResource::class, DefinesFields::class))->toBeTrue();
});

test('a bare BaseResource subclass returns the [] getFields default', function () {
    expect(DefinesFieldsBareBaseResource::getFields())->toBe([]);
});
