<?php

namespace Aura\Base\Tests;

use ReflectionObject;
use Aura\Base\AuraServiceProvider;
use Livewire\LivewireServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Lab404\Impersonate\ImpersonateServiceProvider;
use LivewireUI\Modal\LivewireModalServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

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

        // without exception handling
        // $this->withoutExceptionHandling();

        $migration = include __DIR__.'/../database/migrations/create_aura_tables.php.stub';
        $migration->up();
        $migration2 = include __DIR__.'/../database/migrations/create_flows_table.php.stub';
        $migration2->up();
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
