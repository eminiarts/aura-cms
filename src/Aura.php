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
        // Only return if resource exists

        $name = Str::title($slug);

        if (in_array("Eminiarts\Aura\Resources\\".$name, $this->getResources())) {
            return app('Eminiarts\Aura\Resources\\'.$slug);
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

    /**
     * Register the given resources.
     *
     * @param  array  $resources
     * @return static
     */
    // public static function resources()
    // {
    //     return Cache::remember('aura.resources', now()->addHour(), function () {
    //         $filesystem = app(Filesystem::class);

    //         $files = collect($filesystem->allFiles(app_path('Aura/Resources')))
    //         ->map(function (SplFileInfo $file): string {
    //             return (string) Str::of($file->getRelativePathname())
    //             ->replace(['/', '.php'], ['\\', '']);
    //         })->filter(fn (string $class): bool => $class != 'Resource');

    //         return $files;
    //     });
    // }

    public function getResources(): array
    {
        return array_unique($this->resources);
    }

    public function getTaxonomies(): array
    {
        return array_unique($this->taxonomies);
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

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerTaxonomies(array $taxonomies): void
    {
        $this->taxonomies = array_merge($this->taxonomies, $taxonomies);
    }
}
