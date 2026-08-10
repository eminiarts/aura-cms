<?php

namespace Aura\Base;

use Aura\Base\Commands\AuraLayoutCommand;
use Aura\Base\Commands\CreateAuraPlugin;
use Aura\Base\Commands\CreateResourceFactory;
use Aura\Base\Commands\CreateResourceMigration;
use Aura\Base\Commands\CreateResourcePermissions;
use Aura\Base\Commands\CustomizeCommand;
use Aura\Base\Commands\DatabaseToResources;
use Aura\Base\Commands\ExtendUserModel;
use Aura\Base\Commands\InstallConfigCommand;
use Aura\Base\Commands\MakeField;
use Aura\Base\Commands\MakeResource;
use Aura\Base\Commands\MakeUser;
use Aura\Base\Commands\MigrateFromPostsToCustomTable;
use Aura\Base\Commands\MigratePostMetaToMeta;
use Aura\Base\Commands\PublishCommand;
use Aura\Base\Commands\RunGlobalSearchWorker;
use Aura\Base\Commands\TransferFromPostsToCustomTable;
use Aura\Base\Commands\TransformTableToResource;
use Aura\Base\Commands\UpdateSchemaFromMigration;
use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Facades\Aura as AuraFacade;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\AttachmentDetails;
use Aura\Base\Livewire\BookmarkPage;
use Aura\Base\Livewire\ChooseTemplate;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotCandidateValidator;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\ComponentSlots\DefaultLivewireComponentSlotBridge;
use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspector;
use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspectorFactory;
use Aura\Base\Livewire\ComponentSlots\LivewireComponentSlotBridge;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\EditResourceField;
use Aura\Base\Livewire\EmbeddedComponentAuthorizationHook;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\MediaFieldAuthorization;
use Aura\Base\Livewire\MediaTable;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\ModalActionRegistry;
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
use Aura\Base\Livewire\Styleguide;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Livewire\TwoFactorAuthenticationForm;
use Aura\Base\Livewire\UserTeams;
use Aura\Base\Navigation\Navigation as AuraNavigation;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Policies\UserPolicy;
use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Aura\Base\Providers\AuraEloquentUserProvider;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\EmbeddedComponentAuthorizer;
use Aura\Base\Services\EmbeddedComponentContextStore;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\TransactionRollbackCallbacks;
use Aura\Base\Widgets\Bar;
use Aura\Base\Widgets\Donut;
use Aura\Base\Widgets\Pie;
use Aura\Base\Widgets\SparklineArea;
use Aura\Base\Widgets\SparklineBar;
use Aura\Base\Widgets\ValueWidget;
use Aura\Base\Widgets\Widgets;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Laravel\Octane\Events\RequestReceived;
use Livewire\Component;
use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuraServiceProvider extends PackageServiceProvider
{
    protected $commands = [
        AuraLayoutCommand::class,
    ];

    public function boot()
    {
        $this->configureAuraAuthProviders();

        parent::boot();

        $this->app->booted(function (): void {
            $this->app->make(ComponentSlotRegistry::class)->finalize();
            AuraFacade::captureBaselineState();
        });
    }

    public function bootGate()
    {
        if (config('aura.teams')) {
            Gate::policy(Team::class, TeamPolicy::class);
        }

        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Global Admin: an instance-level operator that transcends the tenant
        // boundary. The package resolves it from the users.global_admin flag so
        // the policy bypasses keyed on isAuraGlobalAdmin() become live out of the
        // box. Host apps may redefine this gate in their own service provider —
        // app providers boot after package providers, so a later Gate::define
        // wins. A required $user param means guests are denied automatically.
        Gate::define(User::GLOBAL_ADMIN_GATE, function ($user) {
            if (! $user instanceof User || $user->getAuthIdentifier() === null) {
                return false;
            }

            return (bool) $user->getConnection()
                ->table($user->getTable())
                ->useWritePdo()
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->value('global_admin');
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
            'aura::media-table' => MediaTable::class,
            'aura.base.livewire.table.table' => Table::class,

            // Attachment component
            'aura::attachment-index' => AttachmentIndex::class,
            'aura.base.livewire.attachment' => AttachmentIndex::class,
            'aura.base.livewire.attachment.index' => AttachmentIndex::class,

            // Top-level Livewire components
            'aura::navigation' => Navigation::class,
            'aura::bookmark-page' => BookmarkPage::class,
            'aura::notifications' => Notifications::class,
            'aura::edit-resource-field' => EditResourceField::class,
            'edit-field' => EditResourceField::class,
            'aura::media-uploader' => MediaUploader::class,
            'aura::attachment-details' => AttachmentDetails::class,
            'aura::create-resource' => CreateResource::class,
            'aura::resource-editor' => ResourceEditor::class,
            'aura::invite-user' => InviteUser::class,
            'aura::user-teams' => UserTeams::class,
            'aura::modals' => Modals::class,
            'aura::plugins-page' => PluginsPage::class,
            'aura::styleguide' => Styleguide::class,
            'aura::choose-template' => ChooseTemplate::class,
            'aura::two-factor-authentication-form' => TwoFactorAuthenticationForm::class,
            'aura::user-two-factor-authentication-form' => TwoFactorAuthenticationForm::class,

            // Top-level components (dot-notation for full-page)
            'aura.base.livewire.dashboard' => config('aura.components.dashboard'),
            'aura.base.livewire.navigation' => Navigation::class,
            'aura.base.livewire.bookmark-page' => BookmarkPage::class,
            'aura.base.livewire.notifications' => Notifications::class,
            'aura.base.livewire.edit-resource-field' => EditResourceField::class,
            'aura.base.livewire.media-uploader' => MediaUploader::class,
            'aura.base.livewire.create-resource' => CreateResource::class,
            'aura.base.livewire.resource-editor' => ResourceEditor::class,
            'aura.base.livewire.invite-user' => InviteUser::class,
            'aura.base.livewire.user-teams' => UserTeams::class,
            'aura.base.livewire.modals' => Modals::class,
            'aura.base.livewire.plugins-page' => PluginsPage::class,
            'aura.base.livewire.styleguide' => Styleguide::class,
            'aura.base.livewire.choose-template' => ChooseTemplate::class,
            'aura.base.livewire.two-factor-authentication-form' => TwoFactorAuthenticationForm::class,
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

        $this->app->make(ComponentSlotRegistry::class)->install($componentMap);

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
            ->hasMigrations([
                'create_aura_tables',
                'consolidate_per_team_admin_roles',
                'add_global_admin_to_users',
                'add_soft_deletes_to_options',
                'enforce_unique_option_identity',
                'add_owner_identity_to_options',
                'create_embedded_resource_incarnations',
                'upgrade_embedded_resource_incarnations',
            ])
            ->runsMigrations()
            ->hasCommands([
                InstallConfigCommand::class,
                MakeResource::class,
                MakeUser::class,
                CustomizeCommand::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                RunGlobalSearchWorker::class,
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

                            // Seed the base Role Catalog (admin + user Global Roles)
                            // so a fresh install works in both Teams-on and
                            // Teams-off mode without hand-seeding. Idempotent.
                            RoleCatalogSeeder::seed();
                        }

                        if ($command->confirm('Do you want to create a user?', true)) {
                            $command->call('aura:user');
                        }
                    });
            });

    }

    public function packageBooted()
    {
        $aura = $this->app->make(Aura::class);
        $aura->registerFields($aura->getAppFields());

        /** @var array<int, Authenticatable|null> $syncAuthenticatedUsers */
        $syncAuthenticatedUsers = [];
        $resetWorkerState = function (): void {
            Auth::forgetGuards();
            AuraFacade::flushState();
        };

        Queue::before(function ($event) use (&$syncAuthenticatedUsers, $resetWorkerState): void {
            if ($event->connectionName === 'sync') {
                $syncAuthenticatedUsers[] = Auth::user();
            }

            $resetWorkerState();
        });

        $finishWorkerBoundary = function ($event) use (&$syncAuthenticatedUsers, $resetWorkerState): void {
            $resetWorkerState();

            if ($event->connectionName !== 'sync' || $syncAuthenticatedUsers === []) {
                return;
            }

            $authenticatedUser = array_pop($syncAuthenticatedUsers);

            if ($authenticatedUser) {
                Auth::setUser($authenticatedUser);
            }
        };

        Queue::after($finishWorkerBoundary);
        Queue::exceptionOccurred($finishWorkerBoundary);

        Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
            if (preg_match('/^\s*(select|pragma|show|describe|explain)\b/i', $event->sql) === 1
                || ! $this->app->resolved(EmbeddedComponentContextStore::class)
            ) {
                return;
            }

            $this->app->make(EmbeddedComponentContextStore::class)->flushIncarnations();
        });

        // Laravel Octane keeps a single PHP process alive across many requests,
        // so Aura's process-level static state (field caches, resource registry,
        // conditional-logic cache, team/scope statics and the user model) must be
        // reset at every request/task/tick boundary to prevent leakage between
        // requests, users and teams. Octane is an optional dependency, so the
        // event classes are referenced by name (guarded by class_exists) and
        // nothing breaks when octane is not installed.
        if (class_exists(RequestReceived::class)) {
            $events = $this->app['events'];

            foreach ([
                'Laravel\Octane\Events\RequestReceived',
                'Laravel\Octane\Events\RequestHandled',
                'Laravel\Octane\Events\RequestTerminated',
                'Laravel\Octane\Events\TaskReceived',
                'Laravel\Octane\Events\TaskTerminated',
                'Laravel\Octane\Events\TickReceived',
                'Laravel\Octane\Events\TickTerminated',
                'Laravel\Octane\Events\WorkerErrorOccurred',
            ] as $octaneEvent) {
                $events->listen($octaneEvent, $resetWorkerState);
            }
        }

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
                                ->whereColumn($model->getQualifiedKeyName(), $metaTable.'.'.$metaForeignKey)
                                ->where($metaTable.'.metable_type', $model->getMorphClass())
                                ->where($metaTable.'.key', $column)
                                ->where($metaTable.'.value', 'like', '%'.$search.'%');
                        });
                    } else {
                        $query->orWhere($model->getTable().'.'.$column, 'like', '%'.$search.'%');
                    }
                }
            });
        });

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return Aura::checkCondition($model, $field, $post);
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

        $this->registerModalActions();

        $this
            ->bootGate()
            ->bootLivewireComponents();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->singleton(TransactionRollbackCallbacks::class);
        $this->app->make('auth')->provider('aura-eloquent', function ($app, array $config) {
            return new AuraEloquentUserProvider($app['hash'], $config['model']);
        });

        $this->configureAuraAuthProviders();

        $this->app->singleton('hook_manager', function ($app) {
            return new HookManager;
        });

        $this->app->singleton('dynamicFunctions', function ($app) {
            return new Facades\DynamicFunctions;
        });

        $this->app->singleton('dynamic_functions', function ($app) {
            return new DynamicFunctions;
        });

        $this->app->singleton('navigation', function ($app) {
            return new AuraNavigation;
        });

        $this->app->singleton(FieldProviderRegistry::class);
        $this->app->singleton(ModalActionRegistry::class);
        $this->app->singleton(PreferenceRegistry::class, function (): PreferenceRegistry {
            return (new PreferenceRegistry)
                ->register(new PreferenceDefinition(
                    key: 'table.view',
                    type: PreferenceValueType::String,
                    default: 'list',
                    scopes: [PreferenceScope::User, PreferenceScope::Team, PreferenceScope::Everyone],
                    resourceAware: true,
                    allowedValues: ['list', 'kanban'],
                    legacyKeys: ['table_view.{resource}'],
                ))
                ->register(new PreferenceDefinition(
                    key: 'table.columns',
                    type: PreferenceValueType::Array,
                    default: [],
                    scopes: [PreferenceScope::User, PreferenceScope::Team, PreferenceScope::Everyone],
                    resourceAware: true,
                    itemType: PreferenceValueType::String,
                    list: true,
                    legacyKeys: ['columns.{resource}'],
                ))
                ->register(new PreferenceDefinition(
                    key: 'navigation.sidebar.groups',
                    type: PreferenceValueType::Array,
                    default: [],
                    scopes: [PreferenceScope::User],
                    itemType: PreferenceValueType::String,
                    list: true,
                    legacyKeys: ['sidebar'],
                ))
                ->register(new PreferenceDefinition(
                    key: 'navigation.sidebar.collapsed',
                    type: PreferenceValueType::Boolean,
                    default: false,
                    scopes: [PreferenceScope::User],
                    legacyKeys: ['sidebarToggled'],
                ));
        });
        $this->app->singleton(PreferenceManager::class);

        // Register before Livewire boots its built-in SupportEvents hook. This
        // lets secure embedded components authorize `__dispatch` before an
        // event listener can execute, including later calls in one batch.
        ComponentHookRegistry::register(EmbeddedComponentAuthorizationHook::class);

        // Bind the concrete Aura instance as a process-persistent singleton so
        // its resource/field registrations and captured baseline survive across
        // requests on a long-running worker (Octane). Octane clears facade and
        // scoped container instances on every request; without a real singleton
        // the facade would re-resolve a fresh, empty Aura and lose every
        // registration after the first request. Per-request mutable state is
        // reset back to the boot baseline via Aura::flushState() instead.
        $this->app->singleton(Aura::class);

        // Package discovery can load eminiarts/aura-cms before livewire/livewire
        // (alphabetical order). Component-slot services resolve livewire.finder /
        // livewire.factory during register when Aura is made below, so ensure
        // Livewire has bound those services first. Application::register() is a
        // no-op if Livewire is already registered.
        $this->app->register(LivewireServiceProvider::class);

        $this->app->singleton(ComponentSlotCandidateValidator::class);
        $this->app->singleton(
            LivewireCollisionInspectorFactory::class,
            fn ($app): LivewireCollisionInspectorFactory => new LivewireCollisionInspectorFactory(
                $app->make('livewire.finder'),
                $app->make('livewire.factory'),
            ),
        );
        $this->app->singleton(
            LivewireCollisionInspector::class,
            fn ($app): LivewireCollisionInspector => $app->make(LivewireCollisionInspectorFactory::class)->make(),
        );
        $this->app->singleton(
            LivewireComponentSlotBridge::class,
            fn ($app): LivewireComponentSlotBridge => new DefaultLivewireComponentSlotBridge(
                $app->make(LivewireCollisionInspector::class),
                $app->make('livewire.finder'),
                $app->make('livewire.factory'),
            ),
        );
        $this->app->singleton(ComponentSlotRegistry::class);

        $this->app->scoped(EmbeddedComponentAuthorizer::class);
        $this->app->scoped(EmbeddedComponentContextStore::class);
        $this->app->scoped(EmbeddedResourceIncarnationGuard::class);
        $this->app->scoped(EmbeddedResourceIncarnationStore::class);

        $this->app->scoped('aura', function ($app): AuraFacade {
            return $app->make(AuraFacade::class);
        });

        $aura = $this->app->make(Aura::class);
        $aura->registerResources([
            config('aura.resources.attachment'),
            config('aura.resources.option'),
            config('aura.resources.permission'),
            config('aura.resources.role'),
            config('aura.resources.user'),
        ]);

        if (config('aura.teams')) {
            $aura->registerResources([
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

        $aura->registerFields($fields);

        // Register App Resources
        $aura->registerResources($aura->getAppResources());
        $aura->registerWidgets($aura->getAppWidgets());
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }

    protected function registerModalActions(): void
    {
        $actions = app(ModalActionRegistry::class);
        $resourceIdentifier = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_int($value) && ! is_string($value)) {
                $fail('The '.$attribute.' must be a valid resource identifier.');
            }
        };
        $resourceFor = static function (array $arguments): Resource {
            $type = $arguments['type'] ?? null;
            $resource = is_string($type) ? AuraFacade::findResourceBySlug($type) : null;

            if (! $resource instanceof Resource) {
                abort(404);
            }

            return $resource;
        };

        foreach (['aura::resource-create-modal', 'resource.create-modal'] as $action) {
            $actions->register(
                $action,
                'aura::resource-create-modal',
                [
                    'arguments.type' => ['required', 'string'],
                    'arguments.params' => ['sometimes', 'array'],
                    'arguments.params.for' => ['sometimes', 'string'],
                    'arguments.params.id' => ['sometimes', $resourceIdentifier],
                ],
                static fn (array $arguments): mixed => Gate::authorize('create', $resourceFor($arguments)),
            );
        }

        foreach ([
            'aura::resource-edit-modal' => 'update',
            'aura::resource-view-modal' => 'view',
        ] as $action => $ability) {
            $actions->register(
                $action,
                $action,
                [
                    'arguments.id' => ['sometimes', $resourceIdentifier],
                    'arguments.resource' => ['required', $resourceIdentifier],
                    'arguments.type' => ['required', 'string'],
                ],
                static function (array $arguments) use ($ability, $resourceFor): void {
                    $record = $resourceFor($arguments)->newQuery()->find($arguments['resource']);

                    abort_unless($record, 404);
                    Gate::authorize($ability, $record);
                },
            );
        }

        $actions->register(
            'aura::invite-user',
            'aura::invite-user',
            authorize: static function (): void {
                abort_unless(config('aura.teams'), 404);
                $team = data_get(auth()->user(), 'currentTeam');
                abort_unless($team, 404);
                Gate::authorize('invite-users', $team);
            },
        );
        $actions->register(
            'aura::create-resource',
            'aura::create-resource',
            authorize: static function (): void {
                abort_if(app()->environment('production'), 403);
                $user = auth()->user();
                abort_unless($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin(), 403);
            },
        );
        $actions->register(
            'aura::media-manager',
            'aura::media-manager',
            [
                'arguments.model' => ['sometimes', 'string'],
                'arguments.resource' => ['sometimes', 'string'],
                'arguments.selected' => ['present', 'array'],
                'arguments.selected.*' => [$resourceIdentifier],
                'arguments.slug' => ['required', 'string'],
            ],
            static function (array $arguments): void {
                $resource = app(MediaFieldAuthorization::class)->normalizeResourceReference(
                    $arguments['resource'] ?? null,
                    $arguments['model'] ?? null,
                );
                app(MediaFieldAuthorization::class)->authorizeField(
                    $resource,
                    $arguments['slug'],
                    $arguments['selected'],
                );
            },
        );

        $actions->register(
            ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
            [
                'arguments.model' => ['required', 'string'],
                'arguments.modalAttributes.persistent' => ['sometimes', 'boolean'],
                'arguments.modalAttributes.modalClasses' => ['sometimes', 'string', 'max:255'],
                'arguments.modalAttributes.slideOver' => ['sometimes', 'boolean'],
                'arguments.ownerToken' => ['required', 'string'],
                'arguments.selected' => ['present', 'array'],
                'arguments.selected.*' => [$resourceIdentifier],
                'arguments.slug' => ['required', 'string'],
            ],
        );
    }

    private function configureAuraAuthProviders(): void
    {
        foreach (config('auth.providers', []) as $name => $provider) {
            $model = is_array($provider) ? ($provider['model'] ?? null) : null;

            if (is_array($provider)
                && ($provider['driver'] ?? null) === 'eloquent'
                && is_string($model)
                && is_a($model, User::class, true)
            ) {
                config()->set("auth.providers.{$name}.driver", 'aura-eloquent');
            }
        }
    }
}
