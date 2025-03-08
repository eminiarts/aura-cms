<?php

namespace Aura\Base;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Aura\Base\Traits\DefaultFields;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class Aura
{
    use DefaultFields;

    // public function __construct()
    // {
    // }

    /**
     * The user model that should be used by Jetstream.
     *
     * @var string
     */
    public static $userModel = User::class;

    protected array $config = [];

    protected array $fields = [];

    protected array $injectViews = [];

    protected array $resources = [];

    protected array $widgets = [];

    /**
     * Determine if Aura's published assets are up-to-date.
     *
     * @return bool
     *
     * @throws \RuntimeException
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

    public static function checkCondition($model, $field, $post = null)
    {
        return ConditionalLogic::shouldDisplayField($model, $field, $post);
    }

    public function clear()
    {
        $this->clearRoutes();

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
        return app('Aura\Base\Templates\\'.str($slug)->title);
    }

    public function getAppFields()
    {
        $path = config('aura.fields.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Field', $namespace = config('aura.fields.namespace'));
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
     * @return static
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
                if (!class_exists($resourceClass)) {
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
        if (isset($this->injectViews[$name])) {
        }

        $hooks = array_map(
            fn (callable $hook): string => (string) app()->call($hook),
            $this->injectViews[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function navigation()
    {
        // Necessary to add TeamIds?

        return Cache::remember('user-'.auth()->id().'-'.auth()->user()->current_team_id.'-navigation', 3600, function () {

            $resources = collect($this->getResources());

            // filter resources by permission and check if user has viewAny permission
            $resources = $resources->filter(function ($resource) {
                if (class_exists($resource)) {
                    $resource = app($resource);
                } else {
                    return false;
                }

                return auth()->user()->can('viewAny', $resource);
            });

            // If a Resource is overriden, we want to remove the original from the navigation
            $keys = $resources->map(function ($resource) {
                return Str::afterLast($resource, '\\');
            })->reverse()->unique()->reverse()->keys();

            $resources = $resources->filter(function ($value, $key) use ($keys) {
                return $keys->contains($key);
            })
                ->map(fn ($r) => app($r)->navigation())
                ->filter(fn ($r) => $r['showInNavigation'] ?? true)
                ->sortBy('sort');

            $resources = app('hook_manager')->applyHooks('navigation', $resources->values());

            $resources = $resources->sortBy('sort')->filter(function ($value, $key) {
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
                    $carry[$item['dropdown']]['group'] = $item['group'];
                    $carry[$item['dropdown']]['dropdown'] = $item['dropdown'];
                    $carry[$item['dropdown']]['items'][] = $item;
                } else {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            return collect($grouped)->groupBy('group');
        });
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

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerRoutes($slug)
    {
        Route::domain(config('aura.domain'))
            ->middleware(config('aura-settings.middleware.aura-admin'))
            ->prefix(config('aura.path')) // This is likely 'admin' from your config
            ->name('aura.')
            ->group(function () use ($slug) {
                Route::get("/{$slug}", Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", View::class)->name("{$slug}.view");
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

    // public function setOption($key, $value)
    // {
    //     $option = $this->getGlobalOptions();

    //     if ($option && is_string($option->value)) {
    //         $settings = json_decode($option->value, true);
    //     } else {
    //         $settings = [];
    //     }

    //     $settings[$key] = $value;

    //     $option->value = json_encode($settings);
    //     $option->save();

    //     Cache::forget('aura-settings');
    // }

    public function updateOption($key, $value)
    {
        if (config('aura.teams')) {
            auth()->user()->currentTeam->updateOption($key, $value);
        } else {
            Option::withoutGlobalScopes([TeamScope::class])->updateOrCreate(['name' => $key], ['value' => $value]);
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
}
