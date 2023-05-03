<?php

namespace Eminiarts\Aura\Tests;

use Eminiarts\Aura\AuraServiceProvider;
use Eminiarts\Aura\Providers\FortifyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Intervention\Image\ImageServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Livewire\LivewireServiceProvider;
use LivewireUI\Modal\LivewireModalServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Eminiarts\\Aura\\Database\\Factories\\'.class_basename($modelName).'Factory'
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

    protected function getPackageProviders($app)
    {
        return [
            AuraServiceProvider::class,
            LivewireServiceProvider::class,
            ImpersonateServiceProvider::class,
            LivewireModalServiceProvider::class,
            ImageServiceProvider::class,
            FortifyServiceProvider::class,
            RayServiceProvider::class,
        ];
    }
}
