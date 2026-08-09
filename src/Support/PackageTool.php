<?php

namespace Aura\Base\Support;

use Composer\InstalledVersions;

final class PackageTool
{
    public static function binary(string $name): ?string
    {
        $packageRoot = dirname(__DIR__, 2);
        $candidates = [$packageRoot.'/vendor/bin/'.$name];

        if (class_exists(InstalledVersions::class)) {
            $pintPath = InstalledVersions::getInstallPath('laravel/pint');

            if ($name === 'pint' && is_string($pintPath)) {
                $candidates[] = $pintPath.'/builds/pint';
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
