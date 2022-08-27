<?php

namespace Eminiarts\Aura;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Eminiarts\Aura\Commands\AuraCommand;

class AuraServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('aura')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_aura_table')
            ->hasCommand(AuraCommand::class);
    }
}
