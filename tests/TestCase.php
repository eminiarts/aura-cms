<?php

namespace Aura\Base\Tests;

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Intervention\Image\ImageServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\LivewireServiceProvider;
use LivewireUI\Modal\LivewireModalServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionObject;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use InteractsWithViews;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Aura\\Base\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // load the aura config
        // config()->set('aura', require __DIR__.'/../config/aura.php');

        // without exception handling
        // $this->withoutExceptionHandling();

        $migration = require __DIR__.'/../database/migrations/create_aura_tables.php.stub';
        $migration->up();
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
