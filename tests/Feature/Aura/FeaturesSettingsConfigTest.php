<?php

// beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('check aura features', function () {
    expect(config('aura.features.global_search'))->toBeTrue();
    expect(config('aura.features.bookmarks'))->toBeTrue();
    expect(config('aura.features.last_visited_pages'))->toBeTrue();
    expect(config('aura.features.notifications'))->toBeTrue();
    expect(config('aura.features.plugins'))->toBeTrue();
    expect(config('aura.features.resource_editor'))->toBe(config('app.env') == 'production' ? false : true);
    expect(config('aura.features.theme_options'))->toBeTrue();
    expect(config('aura.features.global_config'))->toBeTrue();
    expect(config('aura.features.user_profile'))->toBeTrue();
    expect(config('aura.features.create_resource'))->toBeTrue();
    expect(config('aura.features.resource_view'))->toBeTrue();
    expect(config('aura.features.resource_edit'))->toBeTrue();
});
