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

    public function packageRegistered()
    {
        parent::packageRegistered();


        $this->app->scoped('aura', function (): Aura {
            return new Aura();
        });

        Aura::registerResources([
            \Eminiarts\Aura\Resources\Attachment::class,
            \Eminiarts\Aura\Resources\Flow::class,
            \Eminiarts\Aura\Resources\FlowLog::class,
            \Eminiarts\Aura\Resources\Operation::class,
            \Eminiarts\Aura\Resources\OperationLog::class,
            \Eminiarts\Aura\Resources\Option::class,
            \Eminiarts\Aura\Resources\Page::class,
            \Eminiarts\Aura\Resources\Post::class,
            \Eminiarts\Aura\Resources\Permission::class,
            \Eminiarts\Aura\Resources\Role::class,
            \Eminiarts\Aura\Resources\Team::class,
            \Eminiarts\Aura\Resources\User::class,
        ]);

        dd('hier');


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

        Livewire::component('app.aura.widgets.post-stats', \Eminiarts\Aura\Widgets\PostStats::class);
        Livewire::component('app.aura.widgets.total-posts', \Eminiarts\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \Eminiarts\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \Eminiarts\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \Eminiarts\Aura\Widgets\AvgPostsNumber::class);

        // Register the morph map for the resources
        $resources = Aura::resources()->mapWithKeys(function ($resource) {
            return [$resource => 'Eminiarts\Aura\Resources\\'.str($resource)->title];
        })->toArray();
    }
}
