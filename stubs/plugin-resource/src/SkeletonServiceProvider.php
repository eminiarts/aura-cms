<?php

namespace VendorName\Skeleton;

use Eminiarts\Aura\Facades\Aura;
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

        /*
         * Register Aura Resources
         *
         * More info: https://aura-cms.com/docs/resources
         */
        Aura::registerResources([\VendorName\Skeleton\Skeleton::class]);
    }
}
