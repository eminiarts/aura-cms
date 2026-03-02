<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

// Installation tests are commented out because they modify shared configuration
// which can cause test pollution in parallel execution.
// These tests should be run in isolation during package development.

describe('installation commands', function () {
    it('aura:install-config command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:install-config', $commands))->toBeTrue();
    });

    it('aura:publish command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:publish', $commands))->toBeTrue();
    });

    it('aura:user command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:user', $commands))->toBeTrue();
    });
});

// Note: Full installation workflow tests require:
// 1. Isolated test environment
// 2. Fresh config file creation
// 3. Database seeding
// These are typically tested manually or in a dedicated CI job
