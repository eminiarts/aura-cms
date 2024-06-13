<?php

// beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

// Test Post Create Pages
test('check auth settings', function () {
    expect(config('aura.auth.registration'))->toBeTrue();
    expect(config('aura.auth.redirect'))->toBe('/admin');
    expect(config('aura.auth.2fa'))->toBeTrue();
    expect(config('aura.auth.user_invitations'))->toBeTrue();
    expect(config('aura.auth.create_teams'))->toBeTrue();
});
