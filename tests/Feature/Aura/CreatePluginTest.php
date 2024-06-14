<?php

use Illuminate\Support\Facades\File;

it('creates a complete plugin', function () {
    $pluginName = 'myvendor/mypackage';
    $pluginDirectory = base_path('plugins/myvendor/mypackage');

    $name = 'mypackage';

    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);

    $this->artisan('aura:plugin myvendor/mypackage')
        ->expectsQuestion('What type of plugin do you want to create?', 'plugin')
        ->expectsOutput('plugin created at '.$pluginDirectory)
        ->expectsOutputToContain('Replacing placeholders')
        ->expectsConfirmation('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?', 'no') // no, because it would adjust the config and make tests fail next time
        ->expectsOutputToContain('Updating composer.json')
        ->expectsOutputToContain('composer.json updated')
        ->expectsOutputToContain('composer dump-autoload')
        ->expectsOutput('Plugin created successfully!')
        ->assertExitCode(0);

    // Assert that the plugin directory and files were created correctly
    expect(File::exists("{$pluginDirectory}/src"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();

    // Assert that the composer.json file was updated correctly
    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    // $composerJson['autoload']['psr-4'] should have key "$pluginName\\"
    expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Mypackage\\");

    // Assert that the service provider was not appended to the app config file correctly
    $configFile = File::get(base_path('config/app.php'));

    expect($configFile)->not->toContain("$pluginName\\MyPackageServiceProvider::class");

    // Delete the plugin directory
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    // Revert composer.json
    File::put(base_path('composer.json'), json_encode($baseComposerJson, JSON_PRETTY_PRINT));
});

it('creates a field plugin', function () {
    $pluginName = 'myvendor/customfield';
    $pluginDirectory = base_path('plugins/myvendor/customfield');

    $name = 'customfield';

    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);

    $this->artisan('aura:plugin myvendor/customfield')
        ->expectsQuestion('What type of plugin do you want to create?', 'plugin-field')
        ->expectsOutput('plugin-field created at '.$pluginDirectory)
        ->expectsOutputToContain('Replacing placeholders')
        ->expectsConfirmation('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?', 'no') // no, because it would adjust the config and make tests fail next time
        ->expectsOutputToContain('Updating composer.json')
        ->expectsOutputToContain('composer.json updated')
        ->expectsOutputToContain('composer dump-autoload')
        ->expectsOutput('Plugin created successfully!')
        ->assertExitCode(0);

    ray($pluginDirectory);
    // Assert that the plugin directory and files were created correctly
    expect(File::exists("{$pluginDirectory}/src"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();

    expect(File::exists("{$pluginDirectory}/src/Customfield.php"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/src/CustomfieldServiceProvider.php"))->toBeTrue();

    // Assert that the composer.json file was updated correctly
    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    // $composerJson['autoload']['psr-4'] should have key "$pluginName\\"
    expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Customfield\\");

    // Assert that the service provider was not appended to the app config file correctly
    $configFile = File::get(base_path('config/app.php'));

    expect($configFile)->not->toContain("$pluginName\\CustomfieldServiceProvider::class");

    // Delete the plugin directory
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    // Revert composer.json
    File::put(base_path('composer.json'), json_encode($baseComposerJson, JSON_PRETTY_PRINT));
});

it('creates a resource plugin', function () {
    $pluginName = 'myvendor/resource';
    $pluginDirectory = base_path('plugins/myvendor/resource');

    $name = 'resource';

    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);

    $this->artisan('aura:plugin myvendor/resource')
        ->expectsQuestion('What type of plugin do you want to create?', 'plugin-resource')
        ->expectsOutput('plugin-resource created at '.$pluginDirectory)
        ->expectsOutputToContain('Replacing placeholders')
        ->expectsConfirmation('Do you want to append '.str($name)->title().'ServiceProvider to config/app.php?', 'no') // no, because it would adjust the config and make tests fail next time
        ->expectsOutputToContain('Updating composer.json')
        ->expectsOutputToContain('composer.json updated')
        ->expectsOutputToContain('composer dump-autoload')
        ->expectsOutput('Plugin created successfully!')
        ->assertExitCode(0);

    // Assert that the plugin directory and files were created correctly
    expect(File::exists("{$pluginDirectory}/src"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();

    expect(File::exists("{$pluginDirectory}/src/Resource.php"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/src/ResourceServiceProvider.php"))->toBeTrue();

    // Assert that the composer.json file was updated correctly
    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    // $composerJson['autoload']['psr-4'] should have key "$pluginName\\"
    expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Resource\\");

    // Assert that the service provider was not appended to the app config file correctly
    $configFile = File::get(base_path('config/app.php'));

    expect($configFile)->not->toContain("$pluginName\\ResourceServiceProvider::class");

    // Delete the plugin directory
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    // Revert composer.json
    File::put(base_path('composer.json'), json_encode($baseComposerJson, JSON_PRETTY_PRINT));
});
