<?php

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;

class InstallConfigCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->afterApplicationCreated(function () {
            // Create a temporary config file for testing
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
    }

    protected function tearDown(): void
    {
        // Clean up the config file after each test
        $configPath = config_path('aura.php');
        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_modify_aura_configuration()
    {
        $this->artisan('aura:install-config')
            ->expectsConfirmation('Do you want to use teams?', 'yes')
            ->expectsConfirmation('Do you want to modify the default features?', 'no')
            ->expectsConfirmation('Do you want to allow registration?', 'yes')
            ->expectsConfirmation('Do you want to modify the default theme?', 'no')
            ->assertSuccessful();

        $config = include(config_path('aura.php'));
        $this->assertTrue($config['teams']);
    }

    /** @test */
    public function it_can_modify_features_configuration()
    {
        $this->artisan('aura:install-config')
            ->expectsConfirmation('Do you want to use teams?', 'no')
            ->expectsConfirmation('Do you want to modify the default features?', 'yes')
            ->expectsConfirmation("Enable feature 'teams'?", 'no')
            ->expectsConfirmation("Enable feature 'api'?", 'no')
            ->expectsConfirmation('Do you want to allow registration?', 'no')
            ->expectsConfirmation('Do you want to modify the default theme?', 'no')
            ->assertSuccessful();

        $config = include(config_path('aura.php'));
        $this->assertFalse($config['features']['teams']);
        $this->assertFalse($config['features']['api']);
    }

    /** @test */
    public function it_can_modify_theme_configuration()
    {
        $this->artisan('aura:install-config')
            ->expectsConfirmation('Do you want to use teams?', 'no')
            ->expectsConfirmation('Do you want to modify the default features?', 'no')
            ->expectsConfirmation('Do you want to allow registration?', 'no')
            ->expectsConfirmation('Do you want to modify the default theme?', 'yes')
            ->expectsChoice("Select value for 'color-palette':", 'blue', [
                'aura', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 
                'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 
                'mountain-meadow', 'sandal', 'slate', 'gray', 'zinc', 'neutral', 'stone'
            ])
            ->expectsChoice("Select value for 'gray-color-palette':", 'zinc', [
                'slate', 'purple-slate', 'gray', 'zinc', 'neutral', 'stone', 'blue', 'smaragd', 
                'dark-slate', 'blackout'
            ])
            ->expectsChoice("Select value for 'darkmode-type':", 'dark', ['auto', 'light', 'dark'])
            ->expectsChoice("Select value for 'sidebar-size':", 'compact', ['standard', 'compact'])
            ->expectsChoice("Select value for 'sidebar-type':", 'dark', ['primary', 'light', 'dark'])
            ->assertSuccessful();

        $config = include(config_path('aura.php'));
        $this->assertEquals([
            'color-palette' => 'blue',
            'gray-color-palette' => 'zinc',
            'darkmode-type' => 'dark',
            'sidebar-size' => 'compact',
            'sidebar-type' => 'dark',
        ], array_intersect_key($config['theme'], [
            'color-palette' => '',
            'gray-color-palette' => '',
            'darkmode-type' => '',
            'sidebar-size' => '',
            'sidebar-type' => '',
        ]));
    }

    protected function getPackageProviders($app)
    {
        return [
            'Aura\Base\AuraServiceProvider',
            'Lab404\Impersonate\ImpersonateServiceProvider',
            'Laravel\Fortify\FortifyServiceProvider',
            'Livewire\LivewireServiceProvider',
            'LivewireUI\Modal\LivewireModalServiceProvider',
            'Intervention\Image\ImageServiceProvider',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Prevent database migrations
        $app['config']->set('database.default', 'null');
    }
}
