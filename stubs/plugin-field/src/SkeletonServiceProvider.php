<?php

namespace VendorName\Skeleton;

use Aura\Base\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SkeletonServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('skeleton')
            ->hasViews(':vendor_slug-skeleton');
    }

    public function registeringPackage(): void
    {
        Aura::registerFieldSource(
            key: ':vendor_slug-:package_slug',
            namespace: 'VendorName\\Skeleton',
            path: __DIR__,
        );
    }
}
