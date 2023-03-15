<?php

namespace Eminiarts\Aura;

use Eminiarts\Aura\Commands\AuraCommand;
use Eminiarts\Aura\Commands\CreateAuraPlugin;
use Eminiarts\Aura\Commands\MakeField;
use Eminiarts\Aura\Commands\MakePosttype;
use Eminiarts\Aura\Commands\MakeUser;
use Eminiarts\Aura\Commands\PublishCommand;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Livewire\Attachment\Index as AttachmentIndex;
use Eminiarts\Aura\Http\Livewire\AuraConfig;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Eminiarts\Aura\Http\Livewire\EditOperation;
use Eminiarts\Aura\Http\Livewire\EditPosttypeField;
use Eminiarts\Aura\Http\Livewire\GlobalSearch;
use Eminiarts\Aura\Http\Livewire\MediaManager;
use Eminiarts\Aura\Http\Livewire\MediaUploader;
use Eminiarts\Aura\Http\Livewire\Navigation;
use Eminiarts\Aura\Http\Livewire\Notifications;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\CreateModal;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\EditModal;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Post\View;
use Eminiarts\Aura\Http\Livewire\Posttype;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyCreate;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;
use Eminiarts\Aura\Http\Livewire\TeamSettings;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;
use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;
use Eminiarts\Aura\Policies\PostPolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuraServiceProvider extends PackageServiceProvider
{
    // boot
    public function boot()
    {
        parent::boot();

       // ray('boot');
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

    public function bootLivewireComponents()
    {
        Livewire::component('app.aura.widgets.post-stats', \Eminiarts\Aura\Widgets\PostStats::class);
        Livewire::component('app.aura.widgets.total-posts', \Eminiarts\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \Eminiarts\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \Eminiarts\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \Eminiarts\Aura\Widgets\AvgPostsNumber::class);
        Livewire::component('aura::post-index', Index::class);
        Livewire::component('aura::post-create', Create::class);
        Livewire::component('aura::post-create-modal', CreateModal::class);
        Livewire::component('aura::post-edit', Edit::class);
        Livewire::component('aura::post-edit-modal', EditModal::class);
        Livewire::component('aura::post-view', View::class);
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
        Livewire::component('aura::edit-posttype', Posttype::class);
        Livewire::component('aura::taxonomy-index', TaxonomyIndex::class);
        Livewire::component('aura::taxonomy-edit', TaxonomyEdit::class);
        Livewire::component('aura::taxonomy-create', TaxonomyCreate::class);
        Livewire::component('aura::team-settings', TeamSettings::class);
        Livewire::component('aura::invite-user', InviteUser::class);
        Livewire::component('aura::config', AuraConfig::class);
        
        

        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        return $this;
    }

    public function registeringPackage() {

        // ray('registering package');
        //$package->hasRoute('web');
        //$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /*
    * This class is a Package Service Provider
    *
    * More info: https://github.com/spatie/laravel-package-tools
    */
    public function configurePackage(Package $package): void
    {
        // ray('configuring package');

        $package
            ->name('aura')
            ->hasConfigFile()
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommands([
                AuraCommand::class, 
                MakePosttype::class, 
                MakeUser::class, 
                CreateAuraPlugin::class, 
                MakeField::class,
                PublishCommand::class,
            ])
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
            \Eminiarts\Aura\Resources\TeamInvitation::class,
            \Eminiarts\Aura\Resources\User::class,
        ]);

        Aura::registerTaxonomies([
            \Eminiarts\Aura\Taxonomies\Tag::class,
            \Eminiarts\Aura\Taxonomies\Category::class,
        ]);

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))->map(function ($field) {
            return 'Eminiarts\Aura\Fields\\'.str($field->getFilename())->replace('.php', '')->title;
        })->toArray();

        Aura::registerFields($fields);

        // Register App Resources
        Aura::registerResources(Aura::getAppResources());
        Aura::registerResources(Aura::getAppTaxonomies());
        Aura::registerWidgets(Aura::getAppWidgets());
        Aura::registerFields(Aura::getAppFields());
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }

    
}
