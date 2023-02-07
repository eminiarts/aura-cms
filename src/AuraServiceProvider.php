<?php

namespace Eminiarts\Aura;

use Spatie\LaravelPackageTools\Package;
use Eminiarts\Aura\Commands\AuraCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Database\Console\Migrations\InstallCommand;

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
            ->hasViews('aura')
            ->hasRoute('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommand(AuraCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                ->startWith(function(InstallCommand $command) {
                    $command->info('Hello, thank you for installing Aura!')
                    })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('your-vendor/your-repo-name');
            });
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}
