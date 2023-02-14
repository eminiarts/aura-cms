<?php

namespace Eminiarts\Aura;

use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Gate;
use Eminiarts\Aura\Commands\MakeUser;
use Illuminate\Filesystem\Filesystem;
use Eminiarts\Aura\Policies\PostPolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Spatie\LaravelPackageTools\Package;
use Eminiarts\Aura\Commands\AuraCommand;
use Eminiarts\Aura\Commands\MakePosttype;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Finder\SplFileInfo;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Http\Livewire\Navigation;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Http\Livewire\GlobalSearch;
use Eminiarts\Aura\Http\Livewire\MediaManager;
use Eminiarts\Aura\Http\Livewire\EditOperation;
use Eminiarts\Aura\Http\Livewire\MediaUploader;
use Eminiarts\Aura\Http\Livewire\Notifications;
use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Eminiarts\Aura\Http\Livewire\EditPosttypeField;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;
use Eminiarts\Aura\Http\Livewire\Attachment\Index as AttachmentIndex;

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
            ->hasCommand(MakePosttype::class)
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
        Livewire::component('app.aura.widgets.post-stats', \Eminiarts\Aura\Widgets\PostStats::class);
        Livewire::component('app.aura.widgets.total-posts', \Eminiarts\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \Eminiarts\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \Eminiarts\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \Eminiarts\Aura\Widgets\AvgPostsNumber::class);

        Livewire::component('aura::post-index', Index::class);
        Livewire::component('aura::post-create', Create::class);
        Livewire::component('aura::post-edit', Edit::class);
        Livewire::component('aura::table', Table::class);
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-posttype-field', EditPosttypeField::class);
        Livewire::component('aura::media-manager', MediaManager::class);
        Livewire::component('aura::media-uploader', MediaUploader::class);
        Livewire::component('aura::attachment-index', AttachmentIndex::class);
        Livewire::component('aura::user-two-factor-authentication-form', TwoFactorAuthenticationForm::class);
        Livewire::component('aura::create-posttype', CreatePosttype::class);


        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        return $this;
    }

    public function bootGate()
    {
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Resource::class, PostPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function ($user, $ability) {
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

        Aura::registerTaxonomies([
            \Eminiarts\Aura\Taxonomies\Tag::class,
            \Eminiarts\Aura\Taxonomies\Category::class,
        ]);

        // Register App Resources
        Aura::registerResources(Aura::getAppResources());
    }
}
