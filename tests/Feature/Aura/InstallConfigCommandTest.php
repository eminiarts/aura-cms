<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// These tests are skipped because they require modifying the actual config file
// which can cause issues in parallel test execution.
// The command functionality is tested manually during package installation.

describe('config installation command', function () {
    it('command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:install-config', $commands))->toBeTrue();
    });

    it('configures teams and registration without interaction', function () {
        $temporaryPath = storage_path('framework/testing/aura-install-'.Str::uuid());
        $originalConfigPath = app()->configPath();
        $originalEnvironmentPath = app()->environmentPath();

        File::ensureDirectoryExists($temporaryPath);
        File::put($temporaryPath.'/aura.php', <<<'PHP'
<?php

return [
    'teams' => true,
    'features' => [],
    'auth' => [
        'registration' => true,
    ],
    'theme' => [],
];
PHP);
        File::put($temporaryPath.'/.env', "APP_ENV=testing\n");

        app()->useConfigPath($temporaryPath);
        app()->useEnvironmentPath($temporaryPath);

        try {
            $this->artisan('aura:install-config', [
                '--no-interaction' => true,
                '--teams' => 'false',
                '--registration' => 'false',
            ])->assertSuccessful();

            $config = include $temporaryPath.'/aura.php';

            expect($config['teams'])->toBeFalse()
                ->and($config['auth']['registration'])->toBeFalse()
                ->and(File::get($temporaryPath.'/.env'))->toContain('AURA_REGISTRATION=false');
        } finally {
            app()->useConfigPath($originalConfigPath);
            app()->useEnvironmentPath($originalEnvironmentPath);
            File::deleteDirectory($temporaryPath);
        }
    });
});

// Note: Full integration tests for config modification should be run
// in isolation to prevent test pollution. The tests below are commented
// out as they modify shared config state.

/*
beforeEach(function () {
    $configContent = <<<'PHP'
<?php

return [
    'teams' => false,
    'features' => [
        'teams' => true,
        'api' => true,
    ],
    'theme' => [
        'color-palette' => 'aura',
        'gray-color-palette' => 'slate',
        'darkmode-type' => 'auto',
        'sidebar-size' => 'standard',
        'sidebar-type' => 'primary',
    ],
];
PHP;
    file_put_contents(config_path('aura.php'), $configContent);
});

afterEach(function () {
    $configPath = config_path('aura.php');
    if (File::exists($configPath)) {
        File::delete($configPath);
    }
    config(['aura' => null]);
});

it('can modify aura configuration', function () {
    $this->artisan('aura:install-config')
        ->expectsConfirmation('Do you want to use teams?', 'yes')
        ->expectsConfirmation('Do you want to modify the default features?', 'no')
        ->expectsConfirmation('Do you want to allow registration?', 'yes')
        ->expectsConfirmation('Do you want to modify the default theme?', 'no')
        ->assertSuccessful();

    $config = include(config_path('aura.php'));
    expect($config['teams'])->toBeTrue();
});
*/
