<?php

// use Illuminate\Support\Facades\File;
// use function Pest\Laravel\artisan;

// uses(\Aura\Base\Tests\TestCase::class)->in('Feature/Aura');

// beforeEach(function () {
//     $configContent = <<<'PHP'
// <?php

// return [
//     'teams' => false,
//     'features' => [
//         'teams' => true,
//         'api' => true,
//     ],
//     'theme' => [
//         'color-palette' => 'aura',
//         'gray-color-palette' => 'slate',
//         'darkmode-type' => 'auto',
//         'sidebar-size' => 'standard',
//         'sidebar-type' => 'primary',
//     ],
// ];
// PHP;
//     file_put_contents(config_path('aura.php'), $configContent);
// });

// afterEach(function () {
//     // Clean up the config file
//     $configPath = config_path('aura.php');
//     if (File::exists($configPath)) {
//         File::delete($configPath);
//     }
    
//     // Reset config in memory
//     config(['aura' => null]);
    
//     // Clear application instance to ensure fresh state
//     $this->refreshApplication();
// });

// test('it can modify aura configuration', function () {
//     artisan('aura:install-config')
//         ->expectsConfirmation('Do you want to use teams?', 'yes')
//         ->expectsConfirmation('Do you want to modify the default features?', 'no')
//         ->expectsConfirmation('Do you want to allow registration?', 'yes')
//         ->expectsConfirmation('Do you want to modify the default theme?', 'no')
//         ->assertSuccessful();

//     $config = include(config_path('aura.php'));
//     expect($config['teams'])->toBeTrue();
// });

// test('it can modify features configuration', function () {
//     artisan('aura:install-config')
//         ->expectsConfirmation('Do you want to use teams?', 'no')
//         ->expectsConfirmation('Do you want to modify the default features?', 'yes')
//         ->expectsConfirmation("Enable feature 'teams'?", 'no')
//         ->expectsConfirmation("Enable feature 'api'?", 'no')
//         ->expectsConfirmation('Do you want to allow registration?', 'no')
//         ->expectsConfirmation('Do you want to modify the default theme?', 'no')
//         ->assertSuccessful();

//     $config = include(config_path('aura.php'));
//     expect($config['features']['teams'])->toBeFalse();
//     expect($config['features']['api'])->toBeFalse();
// });

// test('it can modify theme configuration', function () {
//     artisan('aura:install-config')
//         ->expectsConfirmation('Do you want to use teams?', 'no')
//         ->expectsConfirmation('Do you want to modify the default features?', 'no')
//         ->expectsConfirmation('Do you want to allow registration?', 'no')
//         ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
//         ->expectsChoice("Select value for 'color-palette':", 'blue', [
//             'aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 
//             'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 
//             'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'
//         ])
//         ->expectsChoice("Select value for 'gray-color-palette':", 'zinc', [
//             'slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 
//             'dark-slate', 'blackout'
//         ])
//         ->expectsChoice("Select value for 'darkmode-type':", 'dark', ['auto', 'light', 'dark'])
//         ->expectsChoice("Select value for 'sidebar-size':", 'compact', ['standard', 'compact'])
//         ->expectsChoice("Select value for 'sidebar-type':", 'dark', ['primary', 'light', 'dark'])
//         ->assertSuccessful();

//     $config = include(config_path('aura.php'));
//     expect($config['theme'])->toMatchArray([
//         'color-palette' => 'blue',
//         'gray-color-palette' => 'zinc',
//         'darkmode-type' => 'dark',
//         'sidebar-size' => 'compact',
//         'sidebar-type' => 'dark',
//     ]);
// });
