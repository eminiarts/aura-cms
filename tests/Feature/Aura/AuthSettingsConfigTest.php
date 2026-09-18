<?php

// beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

// Test Post Create Pages
test('check auth settings', function () {
    expect(config('aura.auth.registration'))->toBeTrue();
    expect(config('aura.auth.redirect'))->toBe('/admin');
    expect(config('aura.auth.2fa'))->toBeTrue();
    expect(config('aura.auth.user_invitations'))->toBeTrue();
    expect(config('aura.auth.invitation_expiry'))->toBe(7);
    expect(config('aura.auth.create_teams'))->toBeTrue();
});

test('auth redirect follows AURA_PATH', function () {
    $_ENV['AURA_PATH'] = $_SERVER['AURA_PATH'] = 'backend';

    try {
        $config = require dirname(__DIR__, 3).'/config/aura.php';
    } finally {
        unset($_ENV['AURA_PATH'], $_SERVER['AURA_PATH']);
    }

    expect($config['auth']['redirect'])->toBe('/backend');
    expect($config['path'])->toBe('backend');
});
