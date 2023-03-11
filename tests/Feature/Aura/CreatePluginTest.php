<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Snapshots\MatchesSnapshots;

it('creates a complete plugin', function () {
    $pluginName = 'myvendor/mypackage';
    $pluginDirectory = base_path("plugins/myvendor/mypackage");
    
    $name = 'mypackage';
    
    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);
    
    $this->artisan('aura:plugin myvendor/mypackage')
    ->expectsQuestion('Select the type of plugin you want to create', 'Complete plugin')
    ->expectsOutput('Complete plugin created at ' . $pluginDirectory)
    ->expectsOutputToContain('Replacing placeholders')
    ->expectsConfirmation("Do you want to append " . str($name)->title() . "ServiceProvider to config/app.php?", 'no') // no, because it would adjust the config and make tests fail next time
    ->expectsOutputToContain('Updating composer.json')
    ->expectsOutputToContain('composer.json updated')
    ->expectsOutputToContain('composer dump-autoload')
    ->expectsOutput('Plugin created successfully!')
    ->assertExitCode(0)
    ;
    
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
    $pluginDirectory = base_path("plugins/myvendor/customfield");
    
    $name = 'customfield';
    
    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);
    
    $this->artisan('aura:plugin myvendor/customfield')
    ->expectsQuestion('Select the type of plugin you want to create', 'Field plugin')
    ->expectsOutput('Field plugin created at ' . $pluginDirectory)
    ->expectsOutputToContain('Replacing placeholders')
    ->expectsConfirmation("Do you want to append " . str($name)->title() . "ServiceProvider to config/app.php?", 'no') // no, because it would adjust the config and make tests fail next time
    ->expectsOutputToContain('Updating composer.json')
    ->expectsOutputToContain('composer.json updated')
    ->expectsOutputToContain('composer dump-autoload')
    ->expectsOutput('Plugin created successfully!')
    ->assertExitCode(0)
    ;
    
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

it('creates a posttype plugin', function () {
    $pluginName = 'myvendor/posttype';
    $pluginDirectory = base_path("plugins/myvendor/posttype");
    
    $name = 'posttype';
    
    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    $baseComposerJson = json_decode(File::get(base_path('composer.json')), true);
    
    $this->artisan('aura:plugin myvendor/posttype')
    ->expectsQuestion('Select the type of plugin you want to create', 'Posttype plugin')
    ->expectsOutput('Posttype plugin created at ' . $pluginDirectory)
    ->expectsOutputToContain('Replacing placeholders')
    ->expectsConfirmation("Do you want to append " . str($name)->title() . "ServiceProvider to config/app.php?", 'no') // no, because it would adjust the config and make tests fail next time
    ->expectsOutputToContain('Updating composer.json')
    ->expectsOutputToContain('composer.json updated')
    ->expectsOutputToContain('composer dump-autoload')
    ->expectsOutput('Plugin created successfully!')
    ->assertExitCode(0)
    ;
    
    // Assert that the plugin directory and files were created correctly
    expect(File::exists("{$pluginDirectory}/src"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    
    expect(File::exists("{$pluginDirectory}/src/Posttype.php"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/src/PosttypeServiceProvider.php"))->toBeTrue();
    
    // Assert that the composer.json file was updated correctly
    $composerJson = json_decode(File::get(base_path('composer.json')), true);
    
    // $composerJson['autoload']['psr-4'] should have key "$pluginName\\"
    expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Posttype\\");
    
    // Assert that the service provider was not appended to the app config file correctly
    $configFile = File::get(base_path('config/app.php'));

    expect($configFile)->not->toContain("$pluginName\\PosttypeServiceProvider::class");

    // Delete the plugin directory
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    // Revert composer.json
    File::put(base_path('composer.json'), json_encode($baseComposerJson, JSON_PRETTY_PRINT));
});