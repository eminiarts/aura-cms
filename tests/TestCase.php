<?php

namespace Eminiarts\Aura\Tests;

use Livewire\LivewireServiceProvider;
use Eminiarts\Aura\AuraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Eminiarts\\Aura\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AuraServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');


        $migration = include __DIR__.'/../database/migrations/create_aura_tables.php.stub';
        $migration->up();
        $migration2 = include __DIR__.'/../database/migrations/create_flows_table.php.stub';
        $migration2->up();
    }
}
