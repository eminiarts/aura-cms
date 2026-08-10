<?php

namespace Aura\Base;

use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Fields\Field as AuraField;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Aura\Base\Traits\DefaultFields;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class Aura
{
    use DefaultFields;

    private const MAX_NAVIGATION_STABILIZATION_ATTEMPTS = 3;

    /**
     * The user model that should be used by Jetstream.
     *
     * @var string
     */
    public static $userModel = User::class;

    protected array $baselineFields = [];

    protected array $baselineInjectViews = [];

    protected array $baselineResources = [];

    protected array $baselineWidgets = [];

    protected array $config = [];

    protected array $fields = [];

    protected array $injectViews = [];

    protected array $resources = [];

    protected array $widgets = [];

    public function __construct(private readonly ?ComponentSlotRegistry $componentSlots = null) {}

    /**
     * Determine if Aura's published assets are up-to-date.
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public static function assetsAreCurrent()
    {
        if (app()->environment('testing')) {
            return true;
        }

        $publishedPath = public_path('vendor/aura/manifest.json');

        if (! File::exists($publishedPath)) {
            throw new RuntimeException('Aura CMS assets are not published. Please run: php artisan aura:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../resources/dist/manifest.json');
    }

    /**
     * Capture registrations made while the application boots.
     */
    public function captureBaselineState(): void
    {
        $this->baselineFields = $this->fields;
        $this->baselineInjectViews = $this->injectViews;
        $this->baselineResources = $this->resources;
        $this->baselineWidgets = $this->widgets;
        app(FieldProviderRegistry::class)->captureBaselineState();
    }

    public static function checkCondition($model, $field, $post = null)
    {
        return ConditionalLogic::shouldDisplayField($model, $field, $post);
    }

    public function clear()
    {
        $this->clearRoutes();

        Cache::clear();
    }

    public function clearConditionsCache(): void
    {
        ConditionalLogic::clearConditionsCache();
    }

    public static function clearGlobalOptionCache(?Connection $connection = null): void
    {
        VersionedCache::bump('option.global', $connection);
    }

    public function clearRoutes()
    {
        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }

    public function findResourceBySlug($slug)
    {
        // First check direct class match
        if (in_array($slug, $this->getResources())) {
            return app($slug);
        }

        foreach ($this->getResources() as $resourceClass) {
            $resource = app($resourceClass);

            // Check for static $slug property
            if (isset($resource::$slug) && $resource::$slug === $slug) {

                return $resource;
            }

            // Fallback to class name based slug
            $className = Str::afterLast($resourceClass, '\\');
            if (Str::slug($className) === Str::slug($slug)) {
                return $resource;
            }
        }

    }

    public static function findTemplateBySlug($slug)
    {
        return app('Aura\Base\Templates\\'.str($slug)->title);
    }

    public function flushFieldCache(): void
    {
        FieldCacheManager::flush();
    }

    /**
     * Reset process state that may otherwise leak between requests or jobs.
     */
    public function flushState(): void
    {
        $this->fields = $this->baselineFields;
        $this->injectViews = $this->baselineInjectViews;
        $this->resources = $this->baselineResources;
        $this->widgets = $this->baselineWidgets;
        app(FieldProviderRegistry::class)->flushState();

        FieldCacheManager::flush(flushProviderResults: false);
        ScopedScope::flushState();
        TeamScope::flushState();
        User::flushCurrentTeamCacheState();
        static::$userModel = User::class;
    }

    public function getAppFields(): array
    {
        $configuration = config('aura-settings.paths.fields', []);

        if (! is_array($configuration)) {
            $configuration = [];
        }

        $sources = $configuration['discover'] ?? [];

        if (! is_array($sources)) {
            $sources = [];
        }

        if (isset($sources['path'], $sources['namespace'])) {
            $sources = [$sources];
        }

        if (isset($configuration['path'], $configuration['namespace'])) {
            $sources[] = [
                'namespace' => $configuration['namespace'],
                'path' => $configuration['path'],
            ];
        }

        $legacyPath = config('aura.fields.path');
        $legacyNamespace = config('aura.fields.namespace');

        if (is_string($legacyPath) && is_string($legacyNamespace)) {
            $sources[] = [
                'namespace' => $legacyNamespace,
                'path' => $legacyPath,
            ];
        }

        $packageSources = config('aura-field-sources', []);

        if (is_array($packageSources)) {
            $sources = array_merge(array_values($sources), array_values($packageSources));
        }

        $fields = collect($sources)
            ->filter(fn ($source): bool => is_array($source)
                && is_string($source['path'] ?? null)
                && is_string($source['namespace'] ?? null)
                && is_dir($source['path']))
            ->flatMap(fn (array $source): array => $this->getAppFiles(
                $source['path'],
                'Field',
                rtrim($source['namespace'], '\\'),
            ))
            ->merge(is_array($configuration['register'] ?? null) ? $configuration['register'] : [])
            ->map(function ($field): ?string {
                if (! is_string($field) || ! class_exists($field)) {
                    return null;
                }

                $reflection = new ReflectionClass($field);

                if (! $reflection->isSubclassOf(AuraField::class) || ! $reflection->isInstantiable()) {
                    return null;
                }

                return $reflection->getName();
            })
            ->filter()
            ->uniqueStrict()
            ->values()
            ->all();

        sort($fields, SORT_STRING);

        return $fields;
    }

    public function getAppFiles($path, $filter, $namespace)
    {

        return collect(app(Filesystem::class)->allFiles($path))
            ->map(function (SplFileInfo $file): string {
                return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => $class != $filter)
            ->map(fn ($item) => $namespace.'\\'.$item)
            ->unique()->toArray();
    }

    /**
     * Register the App resources
     *
     * @param  array  $resources
     * @return array<class-string<resource>>
     */
    public function getAppResources()
    {
        $path = config('aura-settings.paths.resources.path');

        if (! file_exists($path)) {
            return [];
        }

        $resources = $this->getAppFiles($path, $filter = 'Resource', $namespace = config('aura-settings.paths.resources.namespace'));

        // Filter resources to only include classes that extend Aura\Base\Resource
        return collect($resources)
            ->filter(function ($resourceClass) {
                if (! class_exists($resourceClass)) {
                    return false;
                }

                $reflection = new ReflectionClass($resourceClass);

                return $reflection->isSubclassOf('Aura\\Base\\Resource');
            })
            ->values()
            ->toArray();
    }

    public function getAppWidgets()
    {
        $path = config('aura-settings.widgets.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Widget', $namespace = config('aura-settings.widgets.namespace'));
    }

    public function getFields(): array
    {
        return array_unique($this->fields);
    }

    public function getFieldsWithGroups(): array
    {
        return collect($this->fields)
            ->groupBy(function ($field) {
                $fieldClass = app($field);

                return property_exists($fieldClass, 'optionGroup') && ! empty($fieldClass->optionGroup) ? $fieldClass->optionGroup : 'Fields';
            })
            ->mapWithKeys(function ($fields, $groupName) {
                return [$groupName => collect($fields)->mapWithKeys(function ($field) {
                    return [$field => class_basename($field)];
                })->sortKeys()->toArray()];
            })
            ->sortKeys()
            ->toArray();
    }

    public function getInjectViews(): array
    {
        return $this->injectViews;
    }

    public function getOption($name)
    {
        if (config('aura.teams')) {
            $team = $this->authorizedOptionTeam();

            if (! $team) {
                return [];
            }

            $entry = $team->getOptionEntry($name);

            return $entry['found'] ? $entry['value'] : [];
        }

        $entry = VersionedCache::remember(
            $this->globalOptionCacheNamespace(),
            $name,
            now()->addHour(),
            function () use ($name): array {
                $record = Option::withoutGlobalScope(TeamScope::class)
                    ->where('name', $name)
                    ->first(['value']);

                return [
                    'found' => $record !== null,
                    'value' => $record?->getAttributeValue('value'),
                ];
            },
            $this->optionConnection(),
        );

        return $entry['found'] ? $entry['value'] : [];
    }

    public static function getPath($id)
    {
        $attachment = Attachment::find($id);

        return $attachment ? $attachment->url : null;
    }

    public function getResources(): array
    {
        return array_unique(array_filter($this->resources, function ($resource) {
            return ! is_null($resource);
        }));
    }

    public function getWidgets(): array
    {
        return array_unique($this->widgets);
    }

    public function injectView(string $name): Htmlable
    {
        $hooks = array_map(
            fn (callable $hook): string => (string) app()->call($hook),
            $this->injectViews[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function navigation(): Collection
    {
        for ($attempt = 0; $attempt < self::MAX_NAVIGATION_STABILIZATION_ATTEMPTS; $attempt++) {
            $user = auth()->user();

            if (! $user instanceof Authenticatable) {
                return collect();
            }

            $authorizationContext = $this->navigationAuthorizationContext($user);
            $hookManager = app('hook_manager');
            $revision = $hookManager->revision('navigation');
            $context = $this->navigationStructureCacheContext();
            $payload = VersionedCache::remember(
                'navigation',
                $context,
                3600,
                fn (): array => ['resources' => $this->navigationResourceClasses()],
            );
            $resourceClasses = is_array($payload) && is_array($payload['resources'] ?? null)
                ? $payload['resources']
                : [];
            $definitions = $this->buildNavigationDefinitions($resourceClasses);

            $navigation = $this->groupNavigation(
                $this->visibleNavigationDefinitions($definitions, $user),
            );

            if ($hookManager === app('hook_manager')
                && $revision === $hookManager->revision('navigation')
                && hash_equals($context, $this->navigationStructureCacheContext())
                && $user === auth()->user()
                && hash_equals($authorizationContext, $this->navigationAuthorizationContext($user))) {
                return collect($navigation)->map(fn ($items) => collect($items));
            }
        }

        throw new RuntimeException('Unable to stabilize navigation while hooks are changing.');
    }

    public function option($key)
    {
        return $this->options()[$key] ?? null;
    }

    public function options()
    {
        return config('aura');
    }

    /**
     * Re-read provider versions while retaining field output for unchanged
     * versions.
     */
    public function refreshFieldProviderVersions(): void
    {
        app(FieldProviderRegistry::class)->refreshVersions();
        FieldCacheManager::flush(flushProviderResults: false);
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    public function registerComponentSlots(string $source, array $slots): void
    {
        if ($this->componentSlots === null) {
            throw new LogicException('Component slots require Aura to be resolved through the application container.');
        }

        $this->componentSlots->register($source, $slots);
    }

    /**
     * @param  FieldProvider|class-string<FieldProvider>  $provider
     * @param  array<int, class-string<Contracts\DefinesFields>|string>  $resources
     */
    public function registerFieldProvider(
        FieldProvider|string $provider,
        array $resources = ['*'],
        FieldProviderMode $mode = FieldProviderMode::Append,
        int $priority = 0,
    ): void {
        app(FieldProviderRegistry::class)->register($provider, $resources, $mode, $priority);
    }

    public function registerFields(array $fields): void
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    /**
     * Register a package field discovery source without mutating Aura's nested config.
     */
    public static function registerFieldSource(string $key, string $namespace, string $path): void
    {
        $sources = config('aura-field-sources', []);

        if (! is_array($sources)) {
            $sources = [];
        }

        $sources[$key] = [
            'namespace' => $namespace,
            'path' => $path,
        ];

        config()->set('aura-field-sources', $sources);
    }

    public function registerInjectView(string $name, Closure $callback): void
    {
        $this->injectViews[$name][] = $callback;
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerRoutes($slug, $resource = null)
    {
        $resource = $resource ? (is_object($resource) ? get_class($resource) : $resource) : null;

        Route::domain(config('aura.domain'))
            ->middleware(config('aura-settings.middleware.aura-admin'))
            ->prefix(config('aura.path')) // This is likely 'admin' from your config
            ->name('aura.')
            ->group(function () use ($slug, $resource) {
                Route::get("/{$slug}", $resource ? $resource::indexComponent() : Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", $resource ? $resource::createComponent() : Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", $resource ? $resource::editComponent() : Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", $resource ? $resource::viewComponent() : View::class)->name("{$slug}.view");
            });
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    public function scripts()
    {
        return view('aura::components.layout.scripts');
    }

    public function styles()
    {
        return view('aura::components.layout.styles');
    }

    public static function templates()
    {
        $payload = VersionedCache::remember('templates', 'catalog', now()->addHour(), function (): array {
            $items = collect(app(Filesystem::class)->allFiles(app_path('Aura/Templates')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Template')
                ->values()
                ->all();

            return ['items' => $items];
        });

        return collect($payload['items']);
    }

    public function updateOption($key, $value)
    {
        if (config('aura.teams')) {
            $this->authorizedOptionTeam()?->updateOption($key, $value);
        } else {
            $record = Option::withoutGlobalScopes([app(TeamScope::class)])
                ->withTrashed()
                ->where('name', $key)
                ->first();

            if ($record) {
                $record->fill(['value' => $value]);

                if ($record->trashed()) {
                    $record->restore();
                } else {
                    $record->save();
                }
            } else {
                $record = Option::withoutGlobalScopes([app(TeamScope::class)])
                    ->updateOrCreate(['name' => $key], ['value' => $value]);
            }

            VersionedCache::bump($this->globalOptionCacheNamespace(), $record->getConnection());
        }
    }

    /**
     * Get the name of the user model used by the application.
     *
     * @return string
     */
    public static function userModel()
    {
        return static::$userModel;
    }

    public static function useUserModel(string $model)
    {
        static::$userModel = $model;

        return new static;
    }

    public function varexport($expression, $return = false)
    {
        if (! is_array($expression)) {
            return var_export($expression, $return);
        }
        $export = var_export($expression, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $array = preg_replace(["/\d+\s=>\s/"], [null], $array);
        $export = implode(PHP_EOL, array_filter(['['] + $array));
        if ((bool) $return) {
            return $export;
        } else {
            echo $export;
        }
    }

    public function viteScripts()
    {
        return Vite::getFacadeRoot()
            ->useHotFile('vendor/aura/hot')
            ->useBuildDirectory('vendor/aura')->withEntryPoints([
                'resources/js/app.js',
            ]);
    }

    public function viteStyles()
    {
        return Vite::getFacadeRoot()
            ->useHotFile('vendor/aura/hot')
            ->useBuildDirectory('vendor/aura')->withEntryPoints([
                'resources/css/app.css',
            ]);
    }

    protected function authorizedOptionTeam(): ?Team
    {
        $user = User::authenticatedResource();

        return $user?->authorizedCurrentTeam();
    }

    protected function buildNavigationDefinitions(array $resourceClasses): array
    {
        $resources = collect($resourceClasses)
            ->filter(fn ($resource): bool => is_string($resource) && class_exists($resource))
            ->map(fn ($r) => app($r)->navigation())
            ->filter(fn ($r) => $r['showInNavigation'] ?? true)
            ->sortBy('sort');

        $resources = app('hook_manager')->applyHooks('navigation', $resources->values());

        return collect($resources)
            ->filter(fn ($item): bool => is_array($item))
            ->sortBy('sort')
            ->values()
            ->all();
    }

    protected function globalOptionCacheNamespace(): string
    {
        return 'option.global';
    }

    protected function groupNavigation(array $resources): array
    {
        $grouped = array_reduce($resources, function ($carry, $item) {
            if (isset($item['dropdown']) && $item['dropdown'] !== false) {
                if (! isset($carry[$item['dropdown']])) {
                    $carry[$item['dropdown']] = [];
                }

                $carry[$item['dropdown']]['group'] = $item['group'] ?? '';
                $carry[$item['dropdown']]['dropdown'] = $item['dropdown'];
                $carry[$item['dropdown']]['items'][] = $item;
            } else {
                $carry[] = $item;
            }

            return $carry;
        }, []);

        return collect($grouped)
            ->groupBy('group')
            ->map(fn ($items) => $items->values()->all())
            ->all();
    }

    protected function navigationAuthorizationContext(Authenticatable $user): string
    {
        return hash('sha256', serialize([
            'class' => get_class($user),
            'identifier' => $user->getAuthIdentifier(),
            'team' => config('aura.teams') ? data_get($user, 'current_team_id', 'none') : 'global',
        ]));
    }

    protected function navigationConditionalAllows(mixed $condition): bool
    {
        try {
            if (is_bool($condition)) {
                return $condition;
            }

            if ($condition instanceof Closure) {
                return (bool) $condition();
            }

            if (! is_string($condition) || preg_match('/\A[a-f0-9]{32}\z/D', $condition) !== 1) {
                return false;
            }

            return (bool) app('dynamicFunctions')::call($condition);
        } catch (Throwable) {
            return false;
        }
    }

    protected function navigationItemVisible(array $item, Authenticatable $user): bool
    {
        if (! ($item['showInNavigation'] ?? true)) {
            return false;
        }

        $resource = $item['resource'] ?? null;

        if ($resource !== null) {
            if (! is_string($resource) || ! class_exists($resource)) {
                return false;
            }

            try {
                if (! Gate::forUser($user)->allows('viewAny', app($resource))) {
                    return false;
                }
            } catch (Throwable) {
                return false;
            }
        }

        if (array_key_exists('policy', $item) && ! $this->navigationPolicyAllows($item['policy'], $user)) {
            return false;
        }

        return ! array_key_exists('conditional_logic', $item)
            || $this->navigationConditionalAllows($item['conditional_logic']);
    }

    protected function navigationPolicyAllows(mixed $policy, Authenticatable $user): bool
    {
        $contract = $this->parseNavigationPolicy($policy);

        if ($contract === null) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows($contract['ability'], $contract['arguments']);
        } catch (Throwable) {
            return false;
        }
    }

    protected function navigationResourceClasses(): array
    {
        $resources = collect($this->getResources())
            ->filter(fn ($resource): bool => is_string($resource) && class_exists($resource));

        $keys = $resources->map(fn (string $resource): string => Str::afterLast($resource, '\\'))
            ->reverse()
            ->unique()
            ->reverse()
            ->keys();

        return $resources
            ->filter(fn (string $resource, int $key): bool => $keys->contains($key))
            ->values()
            ->all();
    }

    protected function navigationStructureCacheContext(): string
    {
        $resources = collect($this->getResources())
            ->filter(fn ($resource): bool => is_string($resource))
            ->values()
            ->all();

        return hash('sha256', serialize([
            'resources' => $resources,
            'teams' => (bool) config('aura.teams'),
        ]));
    }

    protected function optionConnection(): Connection
    {
        return (new Option)->getConnection();
    }

    protected function parseNavigationPolicy(mixed $policy): ?array
    {
        if (is_string($policy)) {
            $ability = $policy;
            $arguments = [];
        } elseif (is_array($policy) && array_is_list($policy)) {
            if (count($policy) > 2) {
                return null;
            }

            $ability = $policy[0] ?? null;
            $arguments = $policy[1] ?? [];
        } elseif (is_array($policy)) {
            if (array_diff(array_keys($policy), ['ability', 'arguments']) !== []) {
                return null;
            }

            $ability = $policy['ability'] ?? null;
            $arguments = $policy['arguments'] ?? [];
        } else {
            return null;
        }

        if (! is_string($ability) || trim($ability) === '' || ! VersionedCache::isSafe($arguments)) {
            return null;
        }

        return ['ability' => $ability, 'arguments' => $arguments];
    }

    protected function visibleNavigationDefinitions(array $definitions, Authenticatable $user): array
    {
        return collect($definitions)
            ->map(function ($item) use ($user): ?array {
                if (! is_array($item) || ! $this->navigationItemVisible($item, $user)) {
                    return null;
                }

                foreach (['children', 'items'] as $childrenKey) {
                    if (! array_key_exists($childrenKey, $item)) {
                        continue;
                    }

                    if (! is_array($item[$childrenKey])) {
                        return null;
                    }

                    $item[$childrenKey] = $this->visibleNavigationDefinitions($item[$childrenKey], $user);

                    if ($item[$childrenKey] === []) {
                        return null;
                    }
                }

                unset($item['conditional_logic'], $item['policy']);

                return $item;
            })
            ->filter()
            ->values()
            ->all();
    }
}
