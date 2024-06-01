<?php

namespace Aura\Base\Providers;

use Aura\Base\Events\SaveFields;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Listeners\SyncDatabase;
use Aura\Base\Navigation\Navigation;
use Illuminate\Support\Facades\Event;
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
                'icon' => "<x-aura::icon icon='collection' />",
                'name' => 'Create Resource',
                'slug' => 'create_resource',
                'group' => 'settings',
                'sort' => 300,
                'onclick' => "Livewire.dispatch('openModal', { component : 'aura::create-resource' })",
                'route' => false,
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->resource->isSuperAdmin();
                }),
            ] : null,
            config('aura.features.theme_options') ? [
                'icon' => "<x-aura::icon icon='brush' />",
                'name' => 'Theme Options',
                'slug' => 'theme_options',
                'group' => 'settings',
                'sort' => 300,
                'route' => 'aura.team.settings',
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->resource->isSuperAdmin();
                }),
            ] : null,
            config('aura.features.global_config') ? [
                'icon' => "<x-aura::icon icon='config' />",
                'name' => 'Global Config',
                'slug' => 'global_config',
                'group' => 'settings',
                'sort' => 300,
                'route' => 'aura.config',
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->resource->isSuperAdmin();
                }),
            ] : null,
        ]));

        // Register event and listener
        // Event::listen(SaveFields::class, SyncDatabase::class);
        Event::listen(SaveFields::class, ModifyDatabaseMigration::class);

        // Create New Migrations every time a new field is saved
        // Event::listen(SaveFields::class, CreateDatabaseMigration::class);

    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }
}
