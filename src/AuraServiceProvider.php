<?php

namespace Eminiarts\Aura;

use Eminiarts\Aura\Commands\AuraCommand;
use Eminiarts\Aura\Commands\MakeUser;
use Eminiarts\Aura\Http\Livewire\EditPosttypeField;
use Eminiarts\Aura\Http\Livewire\GlobalSearch;
use Eminiarts\Aura\Http\Livewire\Navigation;
use Eminiarts\Aura\Http\Livewire\Notifications;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Policies\PostPolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

        // dd('before gate before');
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

        $this
        ->bootGate()
        ->bootLivewireComponents();
    }

    public function bootLivewireComponents()
    {
        Livewire::component('aura::post-index', Index::class);
        Livewire::component('aura::post-create', Create::class);
        Livewire::component('aura::post-create', Edit::class);
        Livewire::component('aura::table', Table::class);
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-posttype-field', EditPosttypeField::class);
        


        return $this;
    }

    public function bootGate()
    {
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Resource::class, PostPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function ($user, $ability) {
            return true;
            if ($user->resource->isSuperAdmin()) {
                return true;
            }
        });

        return $this;
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

        Facades\Aura::registerTaxonomies([
            \Eminiarts\Aura\Taxonomies\Tag::class,
            \Eminiarts\Aura\Taxonomies\Category::class,
        ]);

        // dd('hier', app('aura'), Facades\Aura::getResources());
    }
}
