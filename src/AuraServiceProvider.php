<?php

namespace Eminiarts\Aura;

use Eminiarts\Aura\Commands\AuraCommand;
use Eminiarts\Aura\Commands\CreateAuraPlugin;
use Eminiarts\Aura\Commands\CreateResourceMigration;
use Eminiarts\Aura\Commands\CreateResourcePermissions;
use Eminiarts\Aura\Commands\DatabaseToResources;
use Eminiarts\Aura\Commands\MakeField;
use Eminiarts\Aura\Commands\MakePosttype;
use Eminiarts\Aura\Commands\MakeTaxonomy;
use Eminiarts\Aura\Commands\MakeUser;
use Eminiarts\Aura\Commands\PublishCommand;
use Eminiarts\Aura\Commands\TransformTableToResource;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Livewire\Attachment\Index as AttachmentIndex;
use Eminiarts\Aura\Http\Livewire\AuraConfig;
use Eminiarts\Aura\Http\Livewire\BookmarkPage;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Eminiarts\Aura\Http\Livewire\CreateTaxonomy;
use Eminiarts\Aura\Http\Livewire\Dashboard;
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
use Eminiarts\Aura\Http\Livewire\Taxonomy\Create as TaxonomyCreate;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;
use Eminiarts\Aura\Http\Livewire\Taxonomy\View as TaxonomyView;
use Eminiarts\Aura\Http\Livewire\TeamSettings;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;
use Eminiarts\Aura\Http\Livewire\User\Profile;
use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;
use Eminiarts\Aura\Policies\ResourcePolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Widgets\Bar;
use Eminiarts\Aura\Widgets\Donut;
use Eminiarts\Aura\Widgets\Pie;
use Eminiarts\Aura\Widgets\SparklineArea;
use Eminiarts\Aura\Widgets\SparklineBar;
use Eminiarts\Aura\Widgets\ValueWidget;
use Eminiarts\Aura\Widgets\Widgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
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
    }

    public function bootGate()
    {
        if (config('aura.teams')) {
            Gate::policy(Team::class, TeamPolicy::class);
        }

        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function ($user, $ability) {
            //  if ($ability == 'view' && config('aura.resource-view-enabled') === false) {
            //     return false;
            // }

            // if ($user->resource->isSuperAdmin()) {
            //     return true;
            // }
        });

        return $this;
    }

    public function bootLivewireComponents()
    {
        Livewire::component('app.aura.widgets.post-stats', \Eminiarts\Aura\Widgets\PostStats::class);
        // Livewire::component('app.aura.widgets.total-posts', \Eminiarts\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \Eminiarts\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \Eminiarts\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \Eminiarts\Aura\Widgets\AvgPostsNumber::class);
        Livewire::component('aura::post-index', Index::class);
        Livewire::component('aura::post-create', Create::class);
        Livewire::component('aura::post-create-modal', CreateModal::class);
        Livewire::component('aura::post-edit', Edit::class);
        Livewire::component('aura::post-edit-modal', EditModal::class);
        Livewire::component('aura::post-view', View::class);
        Livewire::component('aura::table', app(Table::class));
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::bookmark-page', BookmarkPage::class);
        Livewire::component('aura::dashboard', Dashboard::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-posttype-field', EditPosttypeField::class);
        Livewire::component('aura::media-manager', MediaManager::class);
        Livewire::component('aura::media-uploader', app(MediaUploader::class));
        Livewire::component('aura::attachment-index', AttachmentIndex::class);
        Livewire::component('aura::user-two-factor-authentication-form', TwoFactorAuthenticationForm::class);
        Livewire::component('aura::create-posttype', CreatePosttype::class);
        Livewire::component('aura::create-taxonomy', CreateTaxonomy::class);
        Livewire::component('aura::edit-posttype', Posttype::class);
        Livewire::component('aura::taxonomy-index', TaxonomyIndex::class);
        Livewire::component('aura::taxonomy-edit', TaxonomyEdit::class);
        Livewire::component('aura::taxonomy-create', TaxonomyCreate::class);
        Livewire::component('aura::taxonomy-view', TaxonomyView::class);
        Livewire::component('aura::team-settings', TeamSettings::class);
        Livewire::component('aura::invite-user', InviteUser::class);
        Livewire::component('aura::config', AuraConfig::class);

        Livewire::component('aura::profile', app(Profile::class));

        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        // Widgets
        Livewire::component('aura::widgets', Widgets::class);
        Livewire::component('aura::widgets.value-widget', ValueWidget::class);
        Livewire::component('aura::widgets.sparkline-area', SparklineArea::class);
        Livewire::component('aura::widgets.sparkline-bar', SparklineBar::class);
        Livewire::component('aura::widgets.donut', Donut::class);
        Livewire::component('aura::widgets.pie', Pie::class);
        Livewire::component('aura::widgets.bar', Bar::class);

        return $this;
    }

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
            ->hasCommands([
                AuraCommand::class,
                MakePosttype::class,
                MakeTaxonomy::class,
                MakeUser::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                CreateResourceMigration::class,
                DatabaseToResources::class,
                TransformTableToResource::class,
                CreateResourcePermissions::class,
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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../resources/dist') => public_path("vendor/{$this->package->shortName()}"),
                $this->package->basePath('/../resources/public') => public_path("vendor/{$this->package->shortName()}/public"),
            ], "{$this->package->shortName()}-assets");
        }

        Component::macro('notify', function ($message, $type = 'success') {
            $this->dispatchBrowserEvent('notify', ['message' => $message, 'type' => $type]);
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

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return \Eminiarts\Aura\Aura::checkCondition($model, $field, $post);
        });

        Blade::if('superadmin', function () {
            return auth()->user()->resource->isSuperAdmin();
        });

        Blade::if('local', function () {
            return app()->environment('local');
        });

        Blade::if('production', function () {
            return app()->environment('production');
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

        $this->app->singleton('hook_manager', function ($app) {
            return new HookManager();
        });

        $this->app->scoped('aura', function (): Aura {
            return new Aura();
        });

        // dd(config('aura.resources.user'));

        Aura::registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.post'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
        ]);

        // dd(config('aura.resources.post'));

        if (config('aura.teams')) {
            Aura::registerResources([
                config('aura.resources.team'),
                config('aura.resources.team-invitation'),
            ]);
        }

        Aura::registerTaxonomies([
            config('aura.taxonomies.tag'),
            config('aura.taxonomies.category'),
        ]);

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))->map(function ($field) {
            return 'Eminiarts\Aura\Fields\\'.str($field->getFilename())->replace('.php', '')->title;
        })->toArray();

        Aura::registerFields($fields);


        // Register App Resources
        Aura::registerResources(Aura::getAppResources());
        Aura::registerTaxonomies(Aura::getAppTaxonomies());
        Aura::registerWidgets(Aura::getAppWidgets());
        Aura::registerFields(Aura::getAppFields());
    }

    public function registeringPackage()
    {
        //$package->hasRoute('web');
        //$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}
