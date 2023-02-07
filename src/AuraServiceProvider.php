<?php

namespace Eminiarts\Aura;

use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Facades\Aura;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Eminiarts\Aura\Commands\AuraCommand;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

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
            ->publishesServiceProvider('FortifyServiceProvider')

            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                ->startWith(function (InstallCommand $command) {
                    $command->info('Hello, thank you for installing Aura!');
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

    public function boot()
    {
        parent::boot();

        Component::macro('notify', function ($message) {
            $this->dispatchBrowserEvent('notify', $message);
        });

        Gate::before(function ($user, $ability) {
            // return true;
            if ($user->resource->isSuperAdmin()) {
                return true;
            }
        });

        // Search in multiple columns
        Builder::macro('searchIn', function ($columns, $search) {
            return $this->where(function ($query) use ($columns, $search) {
                foreach (Arr::wrap($columns) as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                    // $query->orWhere($column, 'like', $search . '%');
                }
            });
        });

        Livewire::component('app.aura.widgets.post-stats', \App\Aura\Widgets\PostStats::class);
        Livewire::component('app.aura.widgets.total-posts', \App\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \App\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \App\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \App\Aura\Widgets\AvgPostsNumber::class);

        // Register the morph map for the resources
        $resources = Aura::resources()->mapWithKeys(function ($resource) {
            return [$resource => 'App\Aura\Resources\\'.str($resource)->title];
        })->toArray();
    }
}
