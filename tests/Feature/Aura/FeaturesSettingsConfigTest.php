<?php

use Aura\Base\AuraServiceProvider;

// beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('check aura features', function () {
    expect(config('aura.features.global_search'))->toBeTrue();
    expect(config('aura.features.bookmarks'))->toBeTrue();
    expect(config('aura.features.notifications'))->toBeTrue();
    expect(config('aura.features.plugins'))->toBeTrue();
    expect(config('aura.features.resource_editor'))->toBeFalse();
    expect(config('aura.features.settings'))->toBeTrue();
    expect(config('aura.features.profile'))->toBeTrue();
    expect(config('aura.features.create_resource'))->toBeTrue();
    expect(config('aura.features.custom_tables_for_resources'))->toBeFalse();
});

test('an older config with features.user_profile still turns the profile on or off', function (bool $enabled) {
    $this->actingAs(createSuperAdmin());

    // A host's published `features` array replaces the package one, so the new key is missing.
    $features = config('aura.features');
    unset($features['profile']);
    config(['aura.features' => [...$features, 'user_profile' => $enabled]]);

    AuraServiceProvider::applyLegacyConfigKeys();

    expect(config('aura.features.profile'))->toBe($enabled);

    $this->get(route('aura.profile'))->assertStatus($enabled ? 200 : 403);
})->with([
    'enabled' => true,
    'disabled' => false,
]);

test('features.profile wins over the legacy features.user_profile key', function () {
    config(['aura.features.profile' => false, 'aura.features.user_profile' => true]);

    AuraServiceProvider::applyLegacyConfigKeys();

    expect(config('aura.features.profile'))->toBeFalse();
});
