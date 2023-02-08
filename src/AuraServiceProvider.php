<?php

namespace Eminiarts\Aura;

use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Gate;
use Eminiarts\Aura\Commands\MakeUser;
use Eminiarts\Aura\Policies\PostPolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Spatie\LaravelPackageTools\Package;
use Eminiarts\Aura\Commands\AuraCommand;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class AuraServiceProvider extends PackageServiceProvider
{
    /*
    * This class is a Package Service Provider
    *
    * More info: https://github.com/spatie/laravel-package-tools
    */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura')
            ->hasConfigFile()
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommand(AuraCommand::class)
            ->hasCommand(MakeUser::class)

            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                ->startWith(function (InstallCommand $command) {
                    $command->info('Hello, thank you for installing Aura!');
                })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('eminiarts/aura-cms');
            });
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }

    // boot
    public function boot()
    {
        parent::boot();


        // Register Policies
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Resource::class, PostPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // dd('before gate before');

        Gate::before(function ($user, $ability) {
            return true;
            if ($user->resource->isSuperAdmin()) {
                return true;
            }
        });
    }

    public function packageBooted()
    {
        Component::macro('notify', function ($message) {
            $this->dispatchBrowserEvent('notify', $message);
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
        // $resources = Aura::resources()->mapWithKeys(function ($resource) {
        //     return [$resource => 'Eminiarts\Aura\Resources\\'.str($resource)->title];
        // })->toArray();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->scoped('aura', function (): Aura {
            return new Aura();
        });

        Facades\Aura::registerResources([
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






        // dd('hier', app('aura'), Facades\Aura::getResources());
    }
}
