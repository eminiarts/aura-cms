<?php

namespace Eminiarts\Aura;

use Closure;
use RuntimeException;
use Illuminate\Support\Str;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\HtmlString;
use Eminiarts\Aura\Resources\Option;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Traits\DefaultFields;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Contracts\Support\Htmlable;
use Eminiarts\Aura\Models\Scopes\TeamScope;

class Aura
{
    use DefaultFields;

    // public function __construct()
    // {
    //     ray('construct');
    // }

    /**
     * The user model that should be used by Jetstream.
     *
     * @var string
     */
    public static $userModel = User::class;

    protected array $config = [];

    protected array $fields = [];

    protected array $resources = [];

    protected array $taxonomies = [];

    protected array $widgets = [];

    protected array $injectViews = [];

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

    public static function checkCondition($model, $field)
    {
        return ConditionalLogic::shouldDisplayField($model, $field);
    }

    public function clearConditionsCache()
    {
        return ConditionalLogic::clearConditionsCache();
    }

    public function findResourceBySlug($slug)
    {
        if (in_array($slug, $this->getResources())) {
            return app($slug);
        }

        $resources = collect($this->getResources())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        });

        $index = $resources->search(function ($item) use ($slug) {
            return Str::slug($item) == Str::slug($slug);
        });

        if ($index !== false) {
            return app($this->getResources()[$index]);
        }
    }

    public function findTaxonomyBySlug($slug)
    {
        $taxonomies = collect($this->getTaxonomies())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        });

        $index = $taxonomies->search(function ($item) use ($slug) {
            return Str::slug($item) == Str::slug($slug);
        });

        if ($index !== false) {
            return app($this->getTaxonomies()[$index]);
        }
    }

    public static function findTemplateBySlug($slug)
    {
        return app('Eminiarts\Aura\Templates\\'.str($slug)->title);
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
        $path = config('aura.resources.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Resource', $namespace = config('aura.resources.namespace'));
    }

    /**
     * Register the App taxonomies
     *
     * @param  array  $resources
     * @return static
     */
    public function getAppTaxonomies()
    {
        $path = config('aura.taxonomies.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Taxonomy', $namespace = config('aura.taxonomies.namespace'));
    }

    public function getAppWidgets()
    {
        $path = config('aura.widgets.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Widget', $namespace = config('aura.widgets.namespace'));
    }

    public function getFields(): array
    {
        return array_unique($this->fields);
    }

    public function getGlobalOptions()
    {
        $valueString =
        [
            'app_name' => 'Aura CMS',
            'app_description' => 'Aura CMS',
            'app_url' => 'http://aura.test',
            'app_locale' => 'en',
            'app_timezone' => 'UTC',

            'team_registration' => true,
            'user_invitations' => true,

            'media' => [
                'disk' => 'public',
                'path' => 'media',
                'max_file_size' => 10000,
                'generate_thumbnails' => true,
                'thumbnails' => [
                    [
                        'name' => 'thumbnail',
                        'width' => 600,
                        'height' => 600,
                    ],
                    [
                        'name' => 'medium',
                        'width' => 1200,
                        'height' => 1200,
                    ],
                    [
                        'name' => 'large',
                        'width' => 2000,
                        'height' => 2000,
                    ],
                ],
            ],
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'features' => [
                'teams' => true,
                'users' => true,
                'media' => true,
                'notifications' => true,
                'settings' => true,
                'pages' => true,
                'posts' => true,
                'categories' => true,
                'tags' => true,
                'comments' => true,
                'menus' => true,
                'roles' => true,
                'permissions' => true,
                'activity' => true,
                'backups' => true,
                'updates' => true,
                'support' => true,
                'documentation' => true,
            ],
        ];

        if (config('aura.teams')) {
            return Option::withoutGlobalScopes([TeamScope::class])->firstOrCreate([
                'name' => 'aura-settings',
                'team_id' => 0,
            ], [
                'value' => json_encode($valueString),
                'team_id' => 0,
            ]);
        } else {
            return Option::firstOrCreate([
                'name' => 'aura-settings',
            ], [
                'value' => json_encode($valueString),
            ]);
        }
    }

    public function getOption($name)
    {
        if (config('aura.teams')) {
            return Cache::remember(optional(auth()->user())->current_team_id.'.aura.' . $name, now()->addHour(), function () use ($name) {
                $option = Option::where('name', $name)->first();

                if ($option && is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    return [];
                }

                return $settings;
            });
        } else {
            return Cache::remember('aura.' . $name, now()->addHour(), function () use ($name) {
                $option = Option::where('name', $name)->first();

                if ($option && is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    return [];
                }

                return $settings;
            });
        }
    }

    public static function getPath($id)
    {
        return Attachment::find($id)->url;
    }

    public function getResources(): array
    {
        return array_unique($this->resources);
    }

    public function getTaxonomies(): array
    {
        return array_unique($this->taxonomies);
    }

    public function getWidgets(): array
    {
        return array_unique($this->widgets);
    }

    public function navigation()
    {
        $resources = collect($this->getResources())->merge($this->getTaxonomies());

        // filter resources by permission and check if user has viewAny permission
        $resources = $resources->filter(function ($resource) {
            $resource = app($resource);

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

        $grouped = array_reduce(collect($resources)->toArray(), function ($carry, $item) {
            if ($item['dropdown'] !== false) {
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
    }

    public function option($key)
    {
        return $this->options()[$key] ?? null;
    }

    public function options()
    {
        return Cache::rememberForever('aura-settings', function () {
            $option = Option::withoutGlobalScopes([TeamScope::class])
                ->where('name', 'aura-settings')
                ->when(config('aura.teams'), function ($query, string $role) {
                    $query->where('team_id', 0);
                })
                ->first();

            if ($option && is_string($option->value)) {
                return json_decode($option->value, true);
            } else {
                return [];
            }
        });
    }

    public function registerFields(array $fields): void
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerTaxonomies(array $taxonomies): void
    {
        $this->taxonomies = array_merge($this->taxonomies, $taxonomies);
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    public function registerInjectView(string $name, Closure $callback): void
    {
        $this->injectViews[$name][] = $callback;
    }

    public function injectView(string $name): Htmlable
    {
        $hooks = array_map(
            fn (callable $hook): string => (string) app()->call($hook),
            $this->injectViews[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function setOption($key, $value)
    {
        $option = $this->getGlobalOptions();

        if ($option && is_string($option->value)) {
            $settings = json_decode($option->value, true);
        } else {
            $settings = [];
        }

        $settings[$key] = $value;

        $option->value = json_encode($settings);
        $option->save();

        Cache::forget('aura-settings');
    }

    public function taxonomies()
    {
        return $this->getTaxonomies();

        return Cache::remember('aura.taxonomies', now()->addHour(), function () {
            $filesystem = app(Filesystem::class);

            return collect($filesystem->allFiles(app_path('Aura/Taxonomies')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Taxonomy')->toArray();
        });
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

        return new static();
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
}
