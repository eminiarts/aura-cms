<?php

namespace Eminiarts\Aura;

use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Traits\DefaultFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class Aura
{
    use DefaultFields;

    protected array $config = [];

    protected array $resources = [];

    protected array $taxonomies = [];

    public static function checkCondition($model, $field)
    {
        return ConditionalLogic::checkCondition($model, $field);
    }

    public function findResourceBySlug($slug)
    {
        $name = Str::title($slug);

        $resources = collect($this->getResources())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        })->toArray();

        if (in_array($name, $resources)) {
            $index = array_search($name, $resources);

            return app($this->getResources()[$index]);
        }
    }

    public static function findTaxonomyBySlug($slug)
    {
        return app('Eminiarts\Aura\Taxonomies\\'.str($slug)->title);
    }

    public static function findTemplateBySlug($slug)
    {
        return app('Eminiarts\Aura\Templates\\'.str($slug)->title);
    }

    /**
     * Register the given resources from App.
     *
     * @param  array  $resources
     * @return static
     */
    public static function getAppResources()
    {
        $path = config('aura.resources.path');

        if (! app(Filesystem::class)->exists($path)) {
            return [];
        }

        return collect(app(Filesystem::class)->allFiles($path))
            ->map(function (SplFileInfo $file): string {
                return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => $class != 'Resource')
            ->map(function ($item) {
                return config('aura.resources.namespace').'\\'.$item;
            })
            ->unique()->toArray();
    }

    public function getOption($name)
    {
        return Cache::remember(auth()->user()->current_team_id.'.aura.team-settings', now()->addHour(), function () {
            $option = Option::where('name', 'team-settings')->first();

            if ($option && is_string($option->value)) {
                $settings = json_decode($option->value, true);
            } else {
                return [];
            }

            return $settings;
        });
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

    public function navigation()
    {
        $resources = collect($this->getResources())->map(fn ($r) => app($r)->navigation())->sortBy('sort');

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

        // dd(collect($grouped)->groupBy('group'));

        return collect($grouped)->groupBy('group');

        return static::resources()
        ->map(fn ($r) => static::findResourceBySlug($r)->navigation())
        ->filter(fn ($r) => $r['showInNavigation'])
        // groupBy dropdown only if dropdown is set
        ->groupBy('group')
        ->map(fn ($group) => $group->sortBy('sort'))->dd();
    }

    public static function options()
    {
        //
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerTaxonomies(array $taxonomies): void
    {
        $this->taxonomies = array_merge($this->taxonomies, $taxonomies);
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

    public static function taxonomiesFor($posttype)
    {
        return collect(static::taxonomies())->filter(function ($taxonomy) use ($posttype) {
            return in_array($posttype, static::findTaxonomyBySlug($taxonomy)::$attachTo);
        })->map(fn ($taxonomy) => static::findTaxonomyBySlug($taxonomy));
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
