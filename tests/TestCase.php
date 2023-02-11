<?php

namespace Eminiarts\Aura\Tests;

use Livewire\LivewireServiceProvider;
use Eminiarts\Aura\AuraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Lab404\Impersonate\ImpersonateServiceProvider;
use LivewireUI\Modal\LivewireModalServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

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

    protected function getPackageProviders($app)
    {
        return [
            AuraServiceProvider::class,
            LivewireServiceProvider::class,
            ImpersonateServiceProvider::class,
            LivewireModalServiceProvider::class,
        ];
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
}
