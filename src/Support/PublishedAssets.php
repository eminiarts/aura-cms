<?php

namespace Aura\Base\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

class PublishedAssets
{
    /**
     * Determine whether the published Aura asset tree matches the package
     * dist and contains every file referenced by the Vite manifest.
     */
    public static function areCurrent(?string $publishedRoot = null, ?string $packageDist = null): bool
    {
        $publishedRoot ??= public_path('vendor/aura');
        $packageDist ??= dirname(__DIR__, 2).'/resources/dist';

        $publishedManifestPath = $publishedRoot.'/manifest.json';
        $packageManifestPath = $packageDist.'/manifest.json';

        if (! File::exists($publishedManifestPath)) {
            throw new RuntimeException('Aura CMS assets are not published. Please run: php artisan aura:publish');
        }

        if (! File::exists($packageManifestPath)) {
            throw new RuntimeException('Aura CMS package dist manifest is missing.');
        }

        $publishedManifest = self::decodeManifest($publishedManifestPath);
        $packageManifest = self::decodeManifest($packageManifestPath);

        if (File::get($publishedManifestPath) !== File::get($packageManifestPath)) {
            return false;
        }

        return self::referencedFilesExist($publishedRoot, $publishedManifest);
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeManifest(string $path): array
    {
        $raw = File::get($path);
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Aura CMS manifest is invalid JSON: {$path}");
        }

        return $decoded;
    }

    /**
     * Collect every relative asset path referenced by a Vite manifest.
     *
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    public static function referencedPaths(array $manifest): array
    {
        $paths = [];

        foreach ($manifest as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (isset($entry['file']) && is_string($entry['file']) && $entry['file'] !== '') {
                $paths[] = $entry['file'];
            }

            foreach (['css', 'assets'] as $listKey) {
                if (! isset($entry[$listKey]) || ! is_array($entry[$listKey])) {
                    continue;
                }

                foreach ($entry[$listKey] as $path) {
                    if (is_string($path) && $path !== '') {
                        $paths[] = $path;
                    }
                }
            }

            if (isset($entry['imports']) && is_array($entry['imports'])) {
                foreach ($entry['imports'] as $importKey) {
                    if (! is_string($importKey) || ! isset($manifest[$importKey]) || ! is_array($manifest[$importKey])) {
                        continue;
                    }

                    $imported = $manifest[$importKey];

                    if (isset($imported['file']) && is_string($imported['file']) && $imported['file'] !== '') {
                        $paths[] = $imported['file'];
                    }

                    foreach (['css', 'assets'] as $listKey) {
                        if (! isset($imported[$listKey]) || ! is_array($imported[$listKey])) {
                            continue;
                        }

                        foreach ($imported[$listKey] as $path) {
                            if (is_string($path) && $path !== '') {
                                $paths[] = $path;
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Verify that every path referenced by a Vite manifest exists under the
     * given published root and cannot escape that directory.
     *
     * @param  array<string, mixed>|null  $manifest
     */
    public static function verify(string $publishedRoot, ?array $manifest = null): bool
    {
        $manifestPath = rtrim($publishedRoot, DIRECTORY_SEPARATOR).'/manifest.json';

        if ($manifest === null) {
            if (! File::exists($manifestPath)) {
                return false;
            }

            $manifest = self::decodeManifest($manifestPath);
        }

        return self::referencedFilesExist($publishedRoot, $manifest);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected static function referencedFilesExist(string $publishedRoot, array $manifest): bool
    {
        $root = realpath($publishedRoot);

        if ($root === false || ! is_dir($root)) {
            return false;
        }

        foreach (self::referencedPaths($manifest) as $relativePath) {
            $normalized = str_replace('\\', '/', $relativePath);
            $normalized = ltrim($normalized, '/');

            if ($normalized === '' || str_contains($normalized, '..')) {
                return false;
            }

            $absolute = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
            $real = realpath($absolute);

            if ($real === false || ! is_file($real)) {
                return false;
            }

            $prefix = $root.DIRECTORY_SEPARATOR;

            if (! str_starts_with($real, $prefix) && $real !== $root) {
                return false;
            }
        }

        return true;
    }
}
