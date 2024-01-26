<?php

namespace Eminiarts\Aura\Providers;

use Eminiarts\Aura\Facades\DynamicFunctions;
use Eminiarts\Aura\Navigation\Navigation;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
      Navigation::add(array_filter([
        config('aura.features.create_resource') ? [
            "icon" => "<x-aura::icon icon='collection' />",
            "name" => "Create Resource",
            "slug" => "create_resource",
            "group" => "settings",
            "sort" => 300,
            "onclick" => "Livewire.emit('openModal', 'aura::create-posttype')",
            "route" => false,
            "conditional_logic" => DynamicFunctions::add(function() {
              return auth()->user()->resource->isSuperAdmin();
            })
        ] : null,

        config('aura.features.theme_options') ? [
            "icon" => "<x-aura::icon icon='brush' />",
            "name" => "Theme Options",
            "slug" => "theme_options",
            "group" => "settings",
            "sort" => 300,
            "route" => 'aura.team.settings',
            "conditional_logic" => DynamicFunctions::add(function() {
              return auth()->user()->resource->isSuperAdmin();
            })
        ] : null,
        config('aura.features.global_config') ? [
            "icon" => "<x-aura::icon icon='adjustments' />",
            "name" => "Global Config",
            "slug" => "global_config",
            "group" => "settings",
            "sort" => 300,
            "route" => "aura.config",
            "conditional_logic" => DynamicFunctions::add(function() {
              return auth()->user()->resource->isSuperAdmin();
            })
        ] : null,
    ]));

    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
