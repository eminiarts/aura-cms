<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('config installation command', function () {
    it('command is registered', function () {
        $commands = Artisan::all();
        expect(array_key_exists('aura:install-config', $commands))->toBeTrue();
    });

    it('keeps nested theme arrays when modifying the theme', function () {
        $configPath = config_path('aura.php');
        $packageConfig = dirname(__DIR__, 3).'/config/aura.php';
        $backup = File::exists($configPath) ? File::get($configPath) : null;

        File::ensureDirectoryExists(dirname($configPath));
        File::copy($packageConfig, $configPath);

        $colorPalettes = ['aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'];
        $grayPalettes = ['slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 'dark-slate', 'blackout'];

        try {
            $this->artisan('aura:install-config')
                ->expectsConfirmation('Do you want to use teams?', 'yes')
                ->expectsConfirmation('Do you want to modify the default features?', 'no')
                ->expectsConfirmation('Do you want to allow registration?', 'yes')
                ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
                ->expectsChoice("Select value for 'color-palette':", 'aura', $colorPalettes)
                ->expectsChoice("Select value for 'gray-color-palette':", 'slate', $grayPalettes)
                ->expectsChoice("Select value for 'darkmode-type':", 'auto', ['auto', 'light', 'dark'])
                ->expectsChoice("Select value for 'sidebar-size':", 'standard', ['standard', 'compact'])
                ->expectsChoice("Select value for 'sidebar-type':", 'dark', ['primary', 'light', 'dark'])
                ->assertSuccessful();

            $config = include $configPath;

            expect($config['theme']['font'])->toBeArray()
                ->and($config['theme']['font']['family'])->toBeArray()
                ->and($config['theme']['colors'])->toBeArray()
                ->and($config['theme']['colors']['light'])->toBeArray()
                ->and($config['theme']['colors']['dark'])->toBeArray()
                ->and($config['theme']['color-palette'])->toBe('aura')
                ->and($config['teams'])->toBeTrue();
        } finally {
            if ($backup === null) {
                File::delete($configPath);
            } else {
                File::put($configPath, $backup);
            }
        }
    });
});
