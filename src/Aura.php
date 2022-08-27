<?php

namespace Eminiarts\Aura;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class Aura
{
    protected array $resources = [];

    protected array $config = [];

    /**
     * Register the given resources.
     *
     * @param  array  $resources
     * @return static
     */
    public static function resources()
    {
        $filesystem = app(Filesystem::class);

        $files = collect($filesystem->allFiles(app_path('Aura/Resources')))
        ->map(function (SplFileInfo $file): string {
            return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
        })->filter(fn (string $class): bool => $class != 'Resource');

        return $files;
    }

    public static function taxonomies()
    {
        $filesystem = app(Filesystem::class);

        return collect($filesystem->allFiles(app_path('Aura/Taxonomies')))
        ->map(function (SplFileInfo $file): string {
            return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
        })->filter(fn (string $class): bool => $class != 'Taxonomy')->toArray();
    }

    public static function findResourceBySlug($slug)
    {
        return app('App\Aura\Resources\\'.str($slug)->title);
    }

    public static function findTaxonomyBySlug($slug)
    {
        return app('App\Aura\Taxonomies\\'.str($slug)->title);
    }

    public static function taxonomiesFor($posttype)
    {
        return collect(static::taxonomies())->filter(function ($taxonomy) use ($posttype) {
            return in_array($posttype, static::findTaxonomyBySlug($taxonomy)::$attachTo);
        })->map(fn ($taxonomy) => static::findTaxonomyBySlug($taxonomy));
    }

    public static function options()
    {
        //
    }

    public static function navigation()
    {
        return static::resources()
        ->map(fn ($r) => static::findResourceBySlug($r)->navigation())
        ->groupBy('group')
        ->map(fn ($group) => $group->sortBy('sort'));
    }
}
