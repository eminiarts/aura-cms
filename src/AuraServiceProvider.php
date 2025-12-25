<?php

namespace Aura\Base;

use Aura\Base\Commands\AuraLayoutCommand;
use Aura\Base\Commands\CreateAuraPlugin;
use Aura\Base\Commands\CreateResourceFactory;
use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Commands\CreateResourcePermissions;
use Aura\Base\Commands\CustomizeComponent;
use Aura\Base\Commands\DatabaseToResources;
use Aura\Base\Commands\ExtendUserModel;
use Aura\Base\Commands\InstallConfigCommand;
use Aura\Base\Commands\MakeField;
use Aura\Base\Commands\MakeResource;
use Aura\Base\Commands\MakeUser;
use Aura\Base\Commands\MigrateFromPostsToCustomTable;
use Aura\Base\Commands\MigratePostMetaToMeta;
use Aura\Base\Commands\PublishCommand;
use Aura\Base\Commands\TransferFromPostsToCustomTable;
use Aura\Base\Commands\TransformTableToResource;
use Aura\Base\Commands\UpdateSchemaFromMigration;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\BookmarkPage;
use Aura\Base\Livewire\ChooseTemplate;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\Dashboard;
use Aura\Base\Livewire\EditResourceField;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Modals;
use Aura\Base\Livewire\Navigation;
use Aura\Base\Livewire\Notifications;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\CreateModal;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\EditModal;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Livewire\Resource\ViewModal;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Navigation\Navigation as AuraNavigation;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Widgets\Bar;
use Aura\Base\Widgets\Donut;
use Aura\Base\Widgets\Pie;
use Aura\Base\Widgets\SparklineArea;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Widgets\ValueWidget;
use Aura\Base\Widgets\Widgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
        // Component name to class mapping for Livewire 4.x compatibility
        $componentMap = [
            // Resource components (aura:: prefixed names)
            'aura::resource-index' => Index::class,
            'aura::resource-create' => Create::class,
            'aura::resource-create-modal' => CreateModal::class,
            'aura::resource-edit' => Edit::class,
            'aura::resource-edit-modal' => EditModal::class,
            'aura::resource-view-modal' => ViewModal::class,
            'aura::resource-view' => View::class,

            // Resource components (dot-notation names for full-page components)
            'aura.base.livewire.resource' => Index::class,
            'aura.base.livewire.resource.index' => Index::class,
            'aura.base.livewire.resource.create' => Create::class,
            'aura.base.livewire.resource.create-modal' => CreateModal::class,
            'aura.base.livewire.resource.edit' => Edit::class,
            'aura.base.livewire.resource.edit-modal' => EditModal::class,
            'aura.base.livewire.resource.view-modal' => ViewModal::class,
            'aura.base.livewire.resource.view' => View::class,

            // Table component
            'aura::table' => Table::class,
            'aura.base.livewire.table.table' => Table::class,

            // Attachment component
            'aura::attachment-index' => AttachmentIndex::class,
            'aura.base.livewire.attachment' => AttachmentIndex::class,
            'aura.base.livewire.attachment.index' => AttachmentIndex::class,

            // Top-level Livewire components
            'aura::navigation' => Navigation::class,
            'aura::global-search' => GlobalSearch::class,
            'aura::bookmark-page' => BookmarkPage::class,
            'aura::notifications' => Notifications::class,
            'aura::edit-resource-field' => EditResourceField::class,
            'aura::media-manager' => MediaManager::class,
            'aura::media-uploader' => MediaUploader::class,
            'aura::create-resource' => CreateResource::class,
            'aura::resource-editor' => ResourceEditor::class,
            'aura::invite-user' => InviteUser::class,
            'aura::modals' => Modals::class,
            'aura::plugins-page' => PluginsPage::class,
            'aura::choose-template' => ChooseTemplate::class,

            // Top-level components (dot-notation for full-page)
            'aura.base.livewire.dashboard' => config('aura.components.dashboard'),
            'aura.base.livewire.navigation' => Navigation::class,
            'aura.base.livewire.global-search' => GlobalSearch::class,
            'aura.base.livewire.bookmark-page' => BookmarkPage::class,
            'aura.base.livewire.notifications' => Notifications::class,
            'aura.base.livewire.edit-resource-field' => EditResourceField::class,
            'aura.base.livewire.media-manager' => MediaManager::class,
            'aura.base.livewire.media-uploader' => MediaUploader::class,
            'aura.base.livewire.create-resource' => CreateResource::class,
            'aura.base.livewire.resource-editor' => ResourceEditor::class,
            'aura.base.livewire.invite-user' => InviteUser::class,
            'aura.base.livewire.modals' => Modals::class,
            'aura.base.livewire.plugins-page' => PluginsPage::class,
            'aura.base.livewire.choose-template' => ChooseTemplate::class,
            'aura.base.livewire.settings' => config('aura.components.settings'),
            'aura.base.livewire.profile' => config('aura.components.profile'),

            // Configurable components (from config)
            'aura::settings' => config('aura.components.settings'),
            'aura::profile' => config('aura.components.profile'),
            'aura::dashboard' => config('aura.components.dashboard'),

            // Widgets
            'aura::widgets' => Widgets::class,
            'aura::widgets.value-widget' => ValueWidget::class,
            'aura::widgets.sparkline-area' => SparklineArea::class,
            'aura::widgets.sparkline-bar' => SparklineBar::class,
            'aura::widgets.donut' => Donut::class,
            'aura::widgets.pie' => Pie::class,
            'aura::widgets.bar' => Bar::class,

            // Widgets (dot-notation)
            'aura.base.widgets.widgets' => Widgets::class,
            'aura.base.widgets.value-widget' => ValueWidget::class,
            'aura.base.widgets.sparkline-area' => SparklineArea::class,
            'aura.base.widgets.sparkline-bar' => SparklineBar::class,
            'aura.base.widgets.donut' => Donut::class,
            'aura.base.widgets.pie' => Pie::class,
            'aura.base.widgets.bar' => Bar::class,
        ];

        // Register component resolver for Livewire 4.x compatibility
        Livewire::resolveMissingComponent(function ($name) use ($componentMap) {
            return $componentMap[$name] ?? null;
        });

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
            ->hasConfigFile(['aura', 'aura-settings'])
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoutes('web')
            ->hasMigrations(['create_aura_tables'])
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
                MigrateFromPostsToCustomTable::class,
                TransferFromPostsToCustomTable::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, thank you for installing Aura!');
                    })
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    // ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('aura-cms/base')
                    ->endWith(function (InstallCommand $command) {
                        $command->call('aura:extend-user-model');

                        if ($command->confirm('Do you want to modify the aura configuration?', true)) {
                            $command->call('aura:install-config');
                        }

                        if ($command->confirm('Do you want to run the migrations?', true)) {
                            $command->call('migrate');
                        }

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

        app('aura')::registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
        ]);

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
        // $package->hasRoute('web');
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}
