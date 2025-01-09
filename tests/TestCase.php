<?php

namespace Aura\Base\Tests;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Intervention\Image\ImageServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\LivewireServiceProvider;
use LivewireUI\Modal\LivewireModalServiceProvider;
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

        // Manually swap or bind the upload class in your container so it never hits S3.
        app()->singleton(\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl::class, function () {
            return new class extends \Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl
            {
                public function forS3($file, $visibility = '')
                {
                    return [];
                }
            };
        });

        // Add this before the Factory setup
        config()->set('app.env', 'testing');
        config()->set('filesystems.default', 'local');

        // Disable file upload URL signing for tests
        config()->set('livewire.temporary_file_upload.middleware', null);
        config()->set('livewire.temporary_file_upload.upload_url', null);

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

        // Disable Livewire file upload URL signing
        $app['config']->set('livewire.temporary_file_upload.disk', 'local');
        $app['config']->set('livewire.temporary_file_upload.middleware', null);
        $app['config']->set('livewire.temporary_file_upload.upload_url', null);

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

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            ImpersonateServiceProvider::class,
            LivewireModalServiceProvider::class,
            ImageServiceProvider::class,
            FortifyServiceProvider::class,
            AuthServiceProvider::class,
            AuraServiceProvider::class,
            RayServiceProvider::class,
        ];
    }
}
