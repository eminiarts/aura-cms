<?php

namespace Aura\Base\Providers;

use Aura\Base\Events\SaveFields;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Listeners\SyncDatabase;
use Aura\Base\Navigation\Navigation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Navigation::add(array_filter([
            config('aura.features.dashboard') ? [
                'icon' => "<x-aura::icon icon='dashboard' />",
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'sort' => 0,
                'group' => 'Aura',
                'route' => 'aura.dashboard',
            ] : null,
            config('aura.features.create_resource') ? [
                'icon' => "<x-aura::icon icon='collection' />",
                'name' => 'Create Resource',
                'slug' => 'create_resource',
                'group' => 'settings',
                'sort' => 300,
                'onclick' => "Livewire.dispatch('openModal', { component : 'aura::create-resource' })",
                'route' => false,
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->isSuperAdmin();
                }),
            ] : null,
            config('aura.features.settings') ? [
                'icon' => "<x-aura::icon icon='config' />",
                'name' => 'Settings',
                'slug' => 'settings',
                'group' => 'settings',
                'sort' => 300,
                'route' => 'aura.settings',
                'conditional_logic' => DynamicFunctions::add(function () {
                    return auth()->user()->isSuperAdmin();
                }),
            ] : null,
        ]));

        // Validator::extend('json', function ($attribute, $value, $parameters, $validator) {
        //     json_decode($value);
        //     dd('here');
        //     return json_last_error() === JSON_ERROR_NONE;
        // });

        // Register event and listener
        // Event::listen(SaveFields::class, SyncDatabase::class);

        $customTableMigrations = config('aura.features.custom_tables_for_resources');

        if ($customTableMigrations === 'multiple') {
            // Create New Migrations every time a new field is saved
            Event::listen(SaveFields::class, CreateDatabaseMigration::class);
        } elseif ($customTableMigrations === true || $customTableMigrations === 'single') {
            // Modify Existing Migration every time a new field is saved, syncs the database
            Event::listen(SaveFields::class, ModifyDatabaseMigration::class);
        }

    }

    /**
     * Register any application services.
     */
    public function register(): void {}
}
