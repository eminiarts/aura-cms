<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Snapshots\MatchesSnapshots;

it('creates a complete plugin', function () {
    // without exception handling
    $this->withoutExceptionHandling();
    

    $pluginName = 'myvendor/mypackage';
    $pluginDirectory = base_path("plugins/myvendor/mypackage");

    $name = 'mypackage';

    // Delete the plugin directory if it already exists
    if (File::exists($pluginDirectory)) {
        File::deleteDirectory($pluginDirectory);
    }

    // Run the create:aura-plugin command
    // Artisan::call('aura:plugin', [
    //     'name' => $pluginName,
    // ]);

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

    // Assert that the command ran without errors
    // expect(Artisan::output())->toContain('Plugin created successfully');

    // Assert that the plugin directory and files were created correctly
    expect(File::exists("{$pluginDirectory}/src"))->toBeTrue();
    expect(File::exists("{$pluginDirectory}/configure.php"))->toBeFalse();
    expect(File::exists("{$pluginDirectory}/composer.json"))->toBeTrue();

    // Assert that the composer.json file was updated correctly
    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    // $composerJson['autoload']['psr-4'] should have 2 items
    expect($composerJson['autoload']['psr-4'])->toHaveCount(2);
    // $composerJson['autoload']['psr-4'] should have key "$pluginName\\"
    expect($composerJson['autoload']['psr-4'])->toHaveKey("Myvendor\Mypackage\\");

    // Assert that the service provider was appended to the app config file correctly
    $configFile = File::get(base_path('config/app.php'));
    expect($configFile)->not->toContain("$pluginName\\MyPackageServiceProvider::class");
});