<?php

namespace Aura\Base;

use Aura\Base\Contracts\ResourceActionRegistry;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegistry;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Aura\Base\Settings\SettingsPage;
use Aura\Base\Settings\SettingsRegistry;
use Aura\Base\Settings\SettingsStore;
use Aura\Base\Traits\DefaultFields;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Livewire;
use LogicException;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use WeakMap;

class Aura
{
    use DefaultFields;

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

    /** @var WeakMap<Request, string>|null */
    private ?WeakMap $livewireAreas = null;

    public function area(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        if (Livewire::isLivewireRequest() && $request->route()?->named('*livewire.update')) {
            // The update endpoint has no area of its own: take it from the
            // page route the component was rendered on (a checksummed path).
            // Matching routes is not free, so resolve once per request.
            $this->livewireAreas ??= new WeakMap;

            return $this->livewireAreas[$request] ??= $this->livewireArea($request);
        }

        if (app()->runningInConsole() && ! $request->route()) {
            return null;
        }

        return $request->route()?->defaults['aura_area'] ?? 'team';
    }

    /**
     * Determine if Aura's published assets are up-to-date.
     *
     * Matching manifests alone are not enough — every Vite-referenced file
     * under public/vendor/aura must also exist.
     *
     * @throws RuntimeException
     */
    public static function assetsAreCurrent(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        return Support\PublishedAssets::areCurrent();
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

        if (app()->bound(RecordLayoutRegistry::class)) {
            app(RecordLayoutRegistry::class)->captureBaselineState($this->getResources());
        }

        if (app()->bound(SettingsRegistry::class)) {
            app(SettingsRegistry::class)->captureBaselineState();
        }
    }

    public static function checkCondition($model, $field, $post = null)
    {
        return ConditionalLogic::shouldDisplayField($model, $field, $post);
    }

    public function clear()
    {
        $this->clearRoutes();

        // Deliberately a full flush: Aura's cache keys are not enumerable. The
        // navigation key is per user x team x resource-set hash
        // (navigationCacheKey()) and option keys are `aura.{arbitrary name}` /
        // `{team}.aura.{arbitrary name}`, so there is no finite list to forget
        // and the default file/database stores support neither tags nor prefix
        // scans. Scoped invalidation lives on the individual writers
        // (updateOption(), Team/User::updateOption()).
        Cache::clear();
    }

    public function clearConditionsCache()
    {
        return ConditionalLogic::clearConditionsCache();
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
        return app('Aura\Base\Templates\\'.Str::studly($slug));
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

        if (app()->bound(RecordLayoutRegistry::class)) {
            app(RecordLayoutRegistry::class)->flushState();
        }

        if (app()->bound(SettingsRegistry::class)) {
            app(SettingsRegistry::class)->flushState();
        }

        ConditionalLogic::clearConditionsCache();
        Resource::flushFieldCache();
        ScopedScope::flushState();
        TeamScope::flushState();
        static::$userModel = User::class;

        if (app()->bound(ResourceActionRegistry::class)) {
            app(ResourceActionRegistry::class)->flushState();
        }
    }

    /**
     * Discover the app's own field classes, the counterpart to
     * getAppResources(). Both read the canonical `aura-settings.paths.*` keys.
     *
     * @return array<int,class-string>
     */
    public function getAppFields()
    {
        $path = config('aura-settings.paths.fields.path');

        if (! $path || ! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, 'Field', config('aura-settings.paths.fields.namespace'));
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
     * @return array<class-string<\Aura\Base\Resource>>
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

                $reflection = new \ReflectionClass($resourceClass);

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
        if (config('aura.teams') && optional(optional(auth()->user())->resource)->currentTeam) {
            return Cache::remember(auth()->user()->current_team_id.'.aura.'.$name, now()->addHour(), function () use ($name) {
                $option = auth()->user()->currentTeam->getOption($name);

                if ($option) {
                    if (is_string($option)) {
                        $settings = json_decode($option, true);
                    } else {
                        $settings = $option;
                    }
                } else {
                    $settings = [];
                }

                return $settings;

            });
        }

        return Cache::remember('aura.'.$name, now()->addHour(), function () use ($name) {

            $option = Option::where('name', $name)->first();

            if ($option) {
                if (is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    $settings = $option->value;
                }
            } else {
                $settings = [];
            }

            return $settings;
        });

    }

    public static function getPath($id)
    {
        $attachment = app(config('aura.resources.attachment', Attachment::class))::find($id);

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

    public function navigation()
    {
        // Necessary to add TeamIds?

        // Cache plain arrays only. With cache.serializable_classes=false (common
        // hardening default), Collection payloads become __PHP_Incomplete_Class
        // and the sidebar foreach treats property values as group lists — strings
        // then blow up with "foreach() argument must be of type array|object".
        $cacheKey = $this->navigationCacheKey();

        $payload = Cache::remember($cacheKey, 3600, function () {

            $resources = collect($this->getResources());

            // filter resources by permission and check if user has viewAny permission
            $resources = $resources->filter(function ($resource) {
                if (class_exists($resource)) {
                    $resource = app($resource);
                } else {
                    return false;
                }

                return ($this->area() === null || in_array($this->area(), $resource::$areas, true))
                    && auth()->user()->can('viewAny', $resource);
            });

            // If a Resource is overriden, we want to remove the original from the navigation
            $keys = $resources->map(function ($resource) {
                return Str::afterLast($resource, '\\');
            })->reverse()->unique()->reverse()->keys();

            $resources = $resources->filter(function ($value, $key) use ($keys) {
                return $keys->contains($key);
            })
                ->map(function ($resource) {
                    $item = app($resource)->navigation();
                    // A resource in several areas may pin its menu item to one.
                    $item['area'] ??= $this->area() ?? 'team';

                    return $item;
                })
                ->filter(fn ($r) => $r['showInNavigation'] ?? true)
                ->sortBy('sort');

            $resources = app('hook_manager')->applyHooks('navigation', $resources->values());

            $resources = $resources->sortBy('sort')->filter(function ($value, $key) {
                if ($this->area() !== null && ($value['area'] ?? 'team') !== $this->area()) {
                    return false;
                }

                if (isset($value['conditional_logic'])) {
                    return app('dynamicFunctions')::call($value['conditional_logic']);
                }

                return true;
            });

            $grouped = array_reduce(collect($resources)->toArray(), function ($carry, $item) {
                if (isset($item['dropdown']) && $item['dropdown'] !== false) {
                    if (! isset($carry[$item['dropdown']])) {
                        $carry[$item['dropdown']] = [];
                    }
                    // Check if 'group' key exists before using it
                    if (isset($item['group'])) {
                        $carry[$item['dropdown']]['group'] = $item['group'];
                    } else {
                        $carry[$item['dropdown']]['group'] = ''; // Default empty group if not set
                    }
                    $carry[$item['dropdown']]['dropdown'] = $item['dropdown'];
                    $carry[$item['dropdown']]['items'][] = $item;
                } else {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            return collect($grouped)
                ->groupBy('group')
                ->map(fn ($group) => $group->values()->all())
                ->all();
        });

        // Revive incomplete/legacy Collection payloads written before this fix.
        if (! is_array($payload)) {
            Cache::forget($cacheKey);

            return $this->navigation();
        }

        return collect($payload);
    }

    /**
     * The registered resource list is part of the key so that registering a
     * resource (e.g. a freshly generated one) invalidates the cached sidebar
     * instead of staying invisible until the TTL expires.
     *
     * The cached entries hold absolute route URLs, so the request root is part
     * of the key too: a sidebar built from the console (http://localhost) or
     * another domain must not leak those hosts into later page renders.
     */
    public function navigationCacheKey(): string
    {
        return 'user-'.auth()->id()
            .'-'.($this->area() ?? 'none')
            .($this->area() === 'global' ? '' : '-'.auth()->user()->current_team_id)
            .'-navigation-'.md5(implode(',', $this->getResources()).'|'.implode(',', $this->settingsPageSlugs()).'|'.url('/'));
    }

    public function option($key)
    {
        return $this->options()[$key] ?? null;
    }

    public function options()
    {
        return config('aura');
    }

    public function registerFields(array $fields): void
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function registerInjectView(string $name, Closure $callback): void
    {
        $this->injectViews[$name][] = $callback;
    }

    /**
     * @param  list<RecordLayoutPanel>  $panels
     */
    public function registerRecordLayoutPanels(string $source, array $panels): void
    {
        if (! app()->bound(RecordLayoutRegistry::class)) {
            throw new LogicException('Record layout panels require Aura to be resolved through the application container.');
        }

        app(RecordLayoutRegistry::class)->register($source, $panels);
    }

    public function registerResources(array $resources): void
    {
        foreach ($resources as $resource) {
            if (is_string($resource) && class_exists($resource)
                && in_array('team', $resource::$areas, true)
                && $resource::getSlug() === config('aura.global_admin.path')) {
                throw new LogicException('The resource slug ['.$resource::getSlug().'] is reserved for the Global Admin area.');
            }
        }

        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerRoutes($slug, $resource = null)
    {
        $resource = $resource ? (is_object($resource) ? get_class($resource) : $resource) : null;

        $areas = $resource ? $resource::$areas : ['team'];

        if (in_array('team', $areas, true) && $slug === config('aura.global_admin.path')) {
            throw new LogicException("The resource slug [{$slug}] is reserved for the Global Admin area.");
        }

        foreach (array_unique($areas) as $area) {
            if (! in_array($area, ['team', 'global'], true)) {
                throw new LogicException("Unknown Aura area [{$area}].");
            }

            Route::domain(config('aura.domain'))
                ->middleware(array_merge(config('aura-settings.middleware.aura-admin'), $area === 'global' ? ['can:AuraGlobalAdmin'] : []))
                ->prefix(trim(config('aura.path').($area === 'global' ? '/'.config('aura.global_admin.path') : ''), '/'))
                ->name($area === 'global' ? 'aura.global.' : 'aura.')
                ->group(function () use ($slug, $resource, $area) {
                    $routes = $slug === 'attachment' ? [
                        'index' => ["/{$slug}", AttachmentIndex::class],
                    ] : [
                        'index' => ["/{$slug}", $resource ? $resource::indexComponent() : Index::class],
                        'create' => ["/{$slug}/create", $resource ? $resource::createComponent() : Create::class],
                        'edit' => ["/{$slug}/{id}/edit", $resource ? $resource::editComponent() : Edit::class],
                        'view' => ["/{$slug}/{id}", $resource ? $resource::viewComponent() : View::class],
                    ];

                    foreach ($routes as $action => [$path, $component]) {
                        Route::get($path, $component)->name("{$slug}.{$action}")
                            ->defaults('slug', $slug)->defaults('aura_area', $area);
                    }
                });
        }
    }

    /** @param  list<SettingsPage>  $pages */
    public function registerSettingsPages(string $source, array $pages): void
    {
        if (! app()->bound(SettingsRegistry::class)) {
            throw new LogicException('Settings pages require Aura to be resolved through the application container.');
        }

        app(SettingsRegistry::class)->register($source, $pages);
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    /**
     * Route name for a resource page in the current area. Falls back to the
     * other area's route when the resource is not registered in this one, so
     * links to a global-only resource never point at a missing team route.
     */
    public function resourceRouteName(string $slug, string $action = 'index'): string
    {
        $team = "aura.{$slug}.{$action}";
        $global = "aura.global.{$slug}.{$action}";

        if ($this->area() === 'global' || ! Route::has($team)) {
            return Route::has($global) ? $global : $team;
        }

        return $team;
    }

    public function scripts()
    {
        return view('aura::components.layout.scripts');
    }

    public function setting(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        return app(SettingsStore::class)->get($key, $default, $teamId);
    }

    public function styles()
    {
        return view('aura::components.layout.styles');
    }

    public static function templates()
    {
        return Cache::remember('aura.templates', now()->addHour(), function () {
            $filesystem = app(Filesystem::class);

            $files = collect($filesystem->allFiles(app_path('Aura/Templates')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Template');

            return $files;
        });
    }

    public function updateOption($key, $value)
    {
        if (config('aura.teams')) {
            auth()->user()->currentTeam->updateOption($key, $value);
        } else {
            Option::withoutGlobalScopes([app(TeamScope::class)])->updateOrCreate(['name' => $key], ['value' => $value]);
        }

        // Invalidate the exact keys getOption() reads. Team::updateOption()
        // forgets its own `team.{id}.{key}` cache entry, which is a different
        // key from the one getOption() writes — without this a write stays
        // invisible for the rest of the hour-long TTL.
        Cache::forget('aura.'.$key);

        if ($teamId = optional(auth()->user())->current_team_id) {
            Cache::forget($teamId.'.aura.'.$key);
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

    private function livewireArea(Request $request): string
    {
        // Rebuild the page request exactly like Livewire's persistent
        // middleware (makeFakeRequest): swap the update path for the original
        // path inside REQUEST_URI and restore the method. Scheme, host, base
        // path and proxy headers carry over, so both resolve the same route.
        $server = clone $request->server;
        $server->set('REQUEST_URI', str_replace(
            '/'.ltrim($request->path(), '/'),
            '/'.ltrim((string) Livewire::originalPath(), '/'),
            (string) $server->get('REQUEST_URI'),
        ));
        $server->set('REQUEST_METHOD', (string) Livewire::originalMethod());

        try {
            $route = Route::getRoutes()->match($request->duplicate(server: $server->all()));
        } catch (HttpExceptionInterface) {
            return 'team';
        }

        return $route->defaults['aura_area'] ?? 'team';
    }

    /** @return list<string> */
    private function settingsPageSlugs(): array
    {
        if (! app()->bound(SettingsRegistry::class)) {
            return [];
        }

        return array_map(fn (SettingsPage $page): string => $page->slug, app(SettingsRegistry::class)->pages());
    }
}
