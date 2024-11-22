<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create a temporary config file for testing
    $configPath = config_path('aura.php');
    if (!File::exists($configPath)) {
        File::put($configPath, '<?php return ' . var_export([
            'teams' => false,
            'features' => [
                'feature1' => true,
                'feature2' => false,
            ],
            'theme' => [
                'color-palette' => 'aura',
                'gray-color-palette' => 'slate',
                'darkmode-type' => 'auto',
                'sidebar-size' => 'standard',
                'sidebar-type' => 'primary',
            ],
        ], true) . ';');
    }
});

afterEach(function () {
    // Clean up the config file after each test
    $configPath = config_path('aura.php');
    if (File::exists($configPath)) {
        File::delete($configPath);
    }
});

it('can install aura with default configuration', function () {
    $this->artisan('aura:install-config')
        ->expectsConfirmation('Do you want to use teams?', 'no')
        ->expectsConfirmation('Do you want to modify the default features?', 'no')
        ->expectsConfirmation('Do you want to allow registration?', 'yes')
        ->expectsConfirmation('Do you want to modify the default theme?', 'no')
        ->assertSuccessful();

    // Verify config was updated
    $config = include(config_path('aura.php'));
    expect($config['teams'])->toBeFalse();
});

it('can customize theme configuration', function () {
    $this->artisan('aura:install-config')
        ->expectsConfirmation('Do you want to use teams?', 'no')
        ->expectsConfirmation('Do you want to modify the default features?', 'no')
        ->expectsConfirmation('Do you want to allow registration?', 'no')
        ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
        ->expectsChoice("Select value for 'color-palette':", 'blue', ['aura', 'blue', 'green'])
        ->expectsChoice("Select value for 'gray-color-palette':", 'zinc', ['slate', 'zinc', 'stone'])
        ->expectsChoice("Select value for 'darkmode-type':", 'dark', ['auto', 'light', 'dark'])
        ->expectsChoice("Select value for 'sidebar-size':", 'compact', ['standard', 'compact'])
        ->expectsChoice("Select value for 'sidebar-type':", 'dark', ['primary', 'light', 'dark'])
        ->assertSuccessful();

    // Verify config was updated
    $config = include(config_path('aura.php'));
    expect($config['theme'])->toMatchArray([
        'color-palette' => 'blue',
        'gray-color-palette' => 'zinc',
        'darkmode-type' => 'dark',
        'sidebar-size' => 'compact',
        'sidebar-type' => 'dark',
    ]);
});
