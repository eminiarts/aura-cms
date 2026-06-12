<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Store original composer.json
    $this->originalComposerJson = File::get(base_path('composer.json'));
    $this->pluginsDirectory = base_path('plugins');
});

afterEach(function () {
    // Clean up any created plugins
    $pluginDirectories = [
        base_path('plugins/myvendor/mypackage'),
        base_path('plugins/myvendor/customfield'),
        base_path('plugins/myvendor/resource'),
        base_path('plugins/testvendor/testplugin'),
    ];

    foreach ($pluginDirectories as $dir) {
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    // Clean up empty vendor directories
    if (File::isDirectory(base_path('plugins/myvendor')) && empty(File::directories(base_path('plugins/myvendor')))) {
        File::deleteDirectory(base_path('plugins/myvendor'));
    }
    if (File::isDirectory(base_path('plugins/testvendor')) && empty(File::directories(base_path('plugins/testvendor')))) {
        File::deleteDirectory(base_path('plugins/testvendor'));
    }

    // Revert composer.json
    File::put(base_path('composer.json'), $this->originalComposerJson);
});

describe('complete plugin', function () {
    it('creates a complete plugin with correct structure', function () {
        $pluginDirectory = base_path('plugins/myvendor/mypackage');

        $this->artisan('aura:plugin', ['name' => 'myvendor/mypackage'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin')
            ->expectsOutput('plugin created at '.$pluginDirectory)
            ->expectsOutputToContain('Replacing placeholders')
            ->expectsConfirmation('Do you want to append MypackageServiceProvider to config/app.php?', 'no')
            ->expectsOutputToContain('Updating composer.json')
            ->expectsOutputToContain('composer.json updated')
            ->expectsOutputToContain('composer dump-autoload')
            ->expectsOutput('Plugin created successfully!')
            ->assertExitCode(0);

        expect(File::isDirectory("{$pluginDirectory}/src"))->toBeTrue();
        expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();
        expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    });

    it('updates composer.json with autoload entry', function () {
        $this->artisan('aura:plugin', ['name' => 'myvendor/mypackage'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin')
            ->expectsConfirmation('Do you want to append MypackageServiceProvider to config/app.php?', 'no')
            ->assertExitCode(0);

        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Mypackage\\");
    });
});

describe('field plugin', function () {
    it('creates a field plugin with field class', function () {
        $pluginDirectory = base_path('plugins/myvendor/customfield');

        $this->artisan('aura:plugin', ['name' => 'myvendor/customfield'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin-field')
            ->expectsOutput('plugin-field created at '.$pluginDirectory)
            ->expectsConfirmation('Do you want to append CustomfieldServiceProvider to config/app.php?', 'no')
            ->expectsOutput('Plugin created successfully!')
            ->assertExitCode(0);

        expect(File::exists("{$pluginDirectory}/src/Customfield.php"))->toBeTrue();
        expect(File::exists("{$pluginDirectory}/src/CustomfieldServiceProvider.php"))->toBeTrue();
    });

    it('updates composer.json with field plugin autoload', function () {
        $this->artisan('aura:plugin', ['name' => 'myvendor/customfield'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin-field')
            ->expectsConfirmation('Do you want to append CustomfieldServiceProvider to config/app.php?', 'no')
            ->assertExitCode(0);

        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Customfield\\");
    });
});

describe('resource plugin', function () {
    it('creates a resource plugin with resource class', function () {
        $pluginDirectory = base_path('plugins/myvendor/resource');

        $this->artisan('aura:plugin', ['name' => 'myvendor/resource'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin-resource')
            ->expectsOutput('plugin-resource created at '.$pluginDirectory)
            ->expectsConfirmation('Do you want to append ResourceServiceProvider to config/app.php?', 'no')
            ->expectsOutput('Plugin created successfully!')
            ->assertExitCode(0);

        expect(File::exists("{$pluginDirectory}/src/Resource.php"))->toBeTrue();
        expect(File::exists("{$pluginDirectory}/src/ResourceServiceProvider.php"))->toBeTrue();
    });

    it('updates composer.json with resource plugin autoload', function () {
        $this->artisan('aura:plugin', ['name' => 'myvendor/resource'])
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin-resource')
            ->expectsConfirmation('Do you want to append ResourceServiceProvider to config/app.php?', 'no')
            ->assertExitCode(0);

        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Resource\\");
    });
});

// Note: widget plugin type is defined in command but stub doesn't exist yet

describe('interactive mode', function () {
    it('prompts for plugin name when not provided', function () {
        $pluginDirectory = base_path('plugins/testvendor/testplugin');

        $this->artisan('aura:plugin')
            ->expectsQuestion('What is the name of your plugin?', 'testvendor/testplugin')
            ->expectsQuestion('What type of plugin do you want to create?', 'plugin')
            ->expectsConfirmation('Do you want to append TestpluginServiceProvider to config/app.php?', 'no')
            ->expectsOutput('Plugin created successfully!')
            ->assertExitCode(0);

        expect(File::isDirectory($pluginDirectory))->toBeTrue();
    });
});
