<?php

namespace Aura\Base;

use Livewire\Livewire;
use Livewire\Component;
use Aura\Base\Widgets\Bar;
use Aura\Base\Widgets\Pie;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Aura\Base\Widgets\Donut;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Livewire\Modals;
use Aura\Base\Widgets\Widgets;
use Aura\Base\Commands\MakeUser;
use Aura\Base\Commands\MakeField;
use Aura\Base\Livewire\Dashboard;
use Aura\Base\Livewire\CreateFlow;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\Navigation;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Support\Facades\DB;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Commands\MakeResource;
use Aura\Base\Livewire\BookmarkPage;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Widgets\SparklineArea;
use Illuminate\Support\Facades\Gate;
use Aura\Base\Livewire\EditOperation;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Notifications;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View;
use Illuminate\Support\Facades\Blade;
use Aura\Base\Commands\PublishCommand;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Commands\ExtendUserModel;
use Aura\Base\Livewire\Resource\Create;
use Spatie\LaravelPackageTools\Package;
use Aura\Base\Commands\CreateAuraPlugin;
use Aura\Base\Commands\AuraLayoutCommand;
use Aura\Base\Livewire\EditResourceField;
use Illuminate\Database\Eloquent\Builder;
use Aura\Base\Commands\CustomizeComponent;
use Aura\Base\Livewire\Resource\EditModal;
use Aura\Base\Commands\DatabaseToResources;
use Aura\Base\Commands\InstallConfigCommand;
use Aura\Base\Livewire\Resource\CreateModal;
use Aura\Base\Commands\CreateResourceFactory;
use Aura\Base\Commands\MigratePostMetaToMeta;
use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Commands\TransformTableToResource;
use Aura\Base\Commands\CreateResourcePermissions;
use Aura\Base\Commands\UpdateSchemaFromMigration;
use Aura\Base\Livewire\TwoFactorAuthenticationForm;
use Illuminate\Database\Eloquent\Relations\Relation;
use Aura\Base\Navigation\Navigation as AuraNavigation;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;

class AuraServiceProvider extends PackageServiceProvider
{
    protected $commands = [
        // ... other commands ...
        Commands\AuraLayoutCommand::class,
    ];

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

            // if ($user->isSuperAdmin()) {
            //     return true;
            // }
        });

        return $this;
    }

    public function bootLivewireComponents()
    {
        Livewire::component('aura::resource-index', app(Index::class));
        Livewire::component('aura::resource-create', app(Create::class));
        Livewire::component('aura::resource-create-modal', app(CreateModal::class));
        Livewire::component('aura::resource-edit', app(Edit::class));
        Livewire::component('aura::resource-edit-modal', app(EditModal::class));
        Livewire::component('aura::resource-view', app(View::class));
        Livewire::component('aura::table', app(Table::class));
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::bookmark-page', BookmarkPage::class);
        Livewire::component('aura::dashboard', Dashboard::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-resource-field', EditResourceField::class);
        Livewire::component('aura::media-manager', MediaManager::class);
        Livewire::component('aura::media-uploader', app(MediaUploader::class));
        Livewire::component('aura::attachment-index', AttachmentIndex::class);
        Livewire::component('aura::user-two-factor-authentication-form', TwoFactorAuthenticationForm::class);
        Livewire::component('aura::create-resource', CreateResource::class);
        Livewire::component('aura::resource-editor', ResourceEditor::class);
        Livewire::component('aura::settings', app(config('aura.components.settings')));
        Livewire::component('aura::invite-user', InviteUser::class);

        Livewire::component('aura::profile', app(config('aura.components.profile')));
        Livewire::component('aura::modals', Modals::class);

        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        Livewire::component('aura::plugins-page', PluginsPage::class);

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
            ->hasConfigFile('aura-settings')
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoutes('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommands([
                InstallConfigCommand::class,
                MakeResource::class,
                MakeUser::class,
                CustomizeComponent::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                CreateResourceMigration::class,
                DatabaseToResources::class,
                TransformTableToResource::class,
                CreateResourcePermissions::class,
                ExtendUserModel::class,
                UpdateSchemaFromMigration::class,
                CreateResourceFactory::class,
                AuraLayoutCommand::class,
                MigratePostMetaToMeta::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, thank you for installing Aura!');
                    })
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('aura-cms/base')
                    ->endWith(function (InstallCommand $command) {
                        $command->call('aura:extend-user-model');

                        if ($command->confirm('Do you want to create a user?', true)) {
                            $command->call('aura:user');
                        }
                    });
            });

    }

    public function packageBooted()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../resources/dist') => public_path("vendor/{$this->package->shortName()}"),
                $this->package->basePath('/../resources/libs') => public_path("vendor/{$this->package->shortName()}/libs"),
                $this->package->basePath('/../resources/public') => public_path("vendor/{$this->package->shortName()}/public"),
            ], "{$this->package->shortName()}-assets");
        }

        Component::macro('notify', function ($message, $type = 'success') {
            $this->dispatch('notify', message: $message, type: $type);
        });

        // Search in multiple columns
        Builder::macro('searchIn', function ($columns, $search, $model) {
            return $this->where(function ($query) use ($columns, $search, $model) {
                foreach (Arr::wrap($columns) as $column) {
                    if ($model->isMetaField($column)) {
                        $metaTable = $model->getMetaTable();
                        $metaForeignKey = $model->getMetaForeignKey();

                        $query->orWhereExists(function ($subquery) use ($metaTable, $metaForeignKey, $column, $search, $model) {
                            $subquery->select(DB::raw(1))
                                ->from($metaTable)
                                ->whereColumn($model->getTable().'.id', $metaTable.'.'.$metaForeignKey)
                                ->where($metaTable.'.key', $column)
                                ->where($metaTable.'.value', 'like', '%'.$search.'%');
                        });
                    } else {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        });

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return \Aura\Base\Aura::checkCondition($model, $field, $post);
        });

        Blade::if('superadmin', function () {
            return auth()->user()->isSuperAdmin();
        });

        Blade::if('local', function () {
            return app()->environment('local');
        });

        Blade::if('production', function () {
            return app()->environment('production');
        });

        Blade::directive('auraStyles', function (string $expression) {
            return "<?php echo app('aura')::styles(); ?>";
        });

        Blade::directive('auraScripts', function (string $expression) {
            return "<?php echo app('aura')::scripts(); ?>";
        });

        // Register the morph map for the resources
        // $resources = Aura::resources()->mapWithKeys(function ($resource) {
        //     return [$resource => 'Aura\Base\Resources\\'.str($resource)->title];
        // })->toArray();

        // Defer route loading until after all providers have booted
        // TEMP: DISABLED ROUTES
        // $this->app->booted(function () {
        //     $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // });

        // Register the morph map to handle both user classes
        // Relation::morphMap([
        //     'Aura\Base\Resources\User' => 'App\Models\User',
        //     'Aura\Base\Resources\User' => 'Aura\Base\Resources\User',
        // ]);

        // Relation::morphMap([
        //     'Aura\Base\Resources\User' => 'App\Models\User',
        //     'Aura\Base\Resources\User' => 'Aura\Base\Resources\User',
        // ]);

        // ray('relation');

        $this
            ->bootGate()
            ->bootLivewireComponents();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->singleton('hook_manager', function ($app) {
            return new HookManager;
        });

        $this->app->singleton('dynamicFunctions', function ($app) {
            return new \Aura\Base\Facades\DynamicFunctions;
        });

        $this->app->singleton('dynamic_functions', function ($app) {
            return new DynamicFunctions;
        });

        $this->app->singleton('navigation', function ($app) {
            return new AuraNavigation;
        });

        $this->app->scoped('aura', function (): Aura {
            return app(Aura::class);
        });

        // dd(config('aura.resources.user'));

        // dd(app('aura'));
        app('aura')::registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.post'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
            config('aura.resources.tag'),
        ]);

        // dd(config('aura.resources.post'));

        if (config('aura.teams')) {
            app('aura')::registerResources([
                config('aura.resources.team'),
                config('aura.resources.team-invitation'),
            ]);
        }

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))
            ->map(function ($field) {
                $className = 'Aura\Base\Fields\\'.str($field->getFilename())->replace('.php', '');

                return $className !== 'Aura\Base\Fields\Field' ? $className : null;
            })
            ->filter()
            ->toArray();

        app('aura')::registerFields($fields);

        // Register App Resources
        app('aura')::registerResources(app('aura')::getAppResources());
        app('aura')::registerWidgets(app('aura')::getAppWidgets());
        app('aura')::registerFields(app('aura')::getAppFields());
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
