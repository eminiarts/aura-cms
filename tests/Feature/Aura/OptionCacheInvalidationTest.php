<?php

use Aura\Base\Facades\Aura;

/**
 * Aura::getOption() caches for an hour under `{team}.aura.{name}` (teams) or
 * `aura.{name}` (no teams). Aura::updateOption() writes the option row but used
 * to leave those entries in place, so a write stayed invisible for the rest of
 * the TTL — including within the same request.
 */
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('updateOption is visible to the very next getOption', function () {
    // Warm the cache with the empty default first — that is the case that used
    // to stick.
    expect(Aura::getOption('cache-invalidation'))->toBe([]);

    Aura::updateOption('cache-invalidation', ['flag' => true]);

    expect(Aura::getOption('cache-invalidation'))->toBe(['flag' => true]);
});

test('a second updateOption overwrites the previously cached value', function () {
    Aura::updateOption('cache-invalidation', ['flag' => true]);
    expect(Aura::getOption('cache-invalidation'))->toBe(['flag' => true]);

    Aura::updateOption('cache-invalidation', ['flag' => false]);

    expect(Aura::getOption('cache-invalidation'))->toBe(['flag' => false]);
});
