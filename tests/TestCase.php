<?php

namespace Aura\Base\Tests;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionObject;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use InteractsWithViews;
    use LazilyRefreshDatabase;

    // use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Mock file uploads are handled by the mock file in tests/Mocks/
        // Laravel's real-time facade system will automatically use the mock class

        // Add this before the Factory setup
        config()->set('app.env', 'testing');
        config()->set('filesystems.default', 'local');

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Aura\\Base\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Add these lines
        config()->set('app.env', 'testing');
        config()->set('livewire.temporary_file_upload.disk', 'local');

        // load the aura config
        // config()->set('aura', require __DIR__.'/../config/aura.php');

        // without exception handling
        // $this->withoutExceptionHandling();

        $migration = require __DIR__.'/../database/migrations/create_aura_tables.php.stub';
        $migration->up();
    }

    protected function defineEnvironment($app)
    {
        // Prevent actual file upload handling
        $app['config']->set('livewire.temporary_file_upload.disk', 'local');
        $app['config']->set('livewire.temporary_file_upload.middleware', null);
    }

    // protected function tearDown(): void
    // {
    //     $refl = new ReflectionObject($this);
    //     foreach ($refl->getProperties() as $prop) {
    //         if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
    //             $prop->setAccessible(true);
    //             $prop->setValue($this, null);
    //         }
    //     }

    //     parent::tearDown();

    // }

    /**
     * Get the Intervention Image service provider class.
     *
     * Intervention Image v3 moved the Laravel service provider to a separate class.
     * - v2: Intervention\Image\ImageServiceProvider
     * - v3: Intervention\Image\Laravel\ServiceProvider
     */
    protected function getImageServiceProvider(): string
    {
        // v3 service provider location
        if (class_exists(\Intervention\Image\Laravel\ServiceProvider::class)) {
            return \Intervention\Image\Laravel\ServiceProvider::class;
        }

        // v2 fallback
        return \Intervention\Image\ImageServiceProvider::class;
    }

    protected function getPackageProviders($app)
    {
        // Disable file uploads in Livewire before loading the provider
        $app['config']->set('livewire.temporary_file_upload.directory', null);
        $app['config']->set('livewire.temporary_file_upload.middleware', null);
        $app['config']->set('livewire.temporary_file_upload.upload_url', null);
        $app['config']->set('livewire.temporary_file_upload.rules', null);

        return [
            LivewireServiceProvider::class,
            FortifyServiceProvider::class,
            AuthServiceProvider::class,
            AuraServiceProvider::class,
            ImpersonateServiceProvider::class,
            RayServiceProvider::class,
            $this->getImageServiceProvider(),
        ];
    }
}
