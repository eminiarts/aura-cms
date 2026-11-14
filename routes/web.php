<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Http\Controllers\Api\FieldsController;
use Aura\Base\Http\Controllers\ImageController;
use Aura\Base\Http\Controllers\PublishAssetsController;
use Aura\Base\Http\Middleware\EnsureResourceEditorEnabled;
use Aura\Base\Http\Middleware\RedirectTeamlessGlobalAdmin;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Livewire\Styleguide;
use Illuminate\Support\Facades\Route;

Route::middleware(config('aura-settings.middleware.aura-guest'))->group(function () {
    require __DIR__.'/auth.php';
});

Route::middleware(config('aura-settings.middleware.aura-admin'))->group(function () {
    Route::impersonate();
});

Route::domain(config('aura.domain'))
    ->middleware(config('aura-settings.middleware.aura-admin'))
    ->name('aura.')
    ->group(function () {
        // Route::prefix(config('aura.core_path'))->group(function () {
        //     Route::get('/assets/{file}', AssetController::class)->where('file', '.*')->name('asset');
        // });

        Route::prefix(config('aura.path'))->group(function () {

            Route::get('/', config('aura.components.dashboard'))->middleware(RedirectTeamlessGlobalAdmin::class)->name('dashboard');

            Route::post('/assets/publish', PublishAssetsController::class)->name('assets.publish');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/profile', config('aura.components.profile'))->name('profile');

            Route::get('/settings', config('aura.components.settings'))->name('settings');
            Route::get('/settings/{page}', config('aura.components.settings'))->name('settings.page');

            Route::prefix(config('aura.global_admin.path'))->name('global.')
                ->middleware('can:AuraGlobalAdmin')->group(function () {
                    Route::get('/', fn () => redirect()->route(Aura::resourceRouteName(app(config(config('aura.teams') ? 'aura.resources.team' : 'aura.resources.user'))->getSlug())))
                        ->defaults('aura_area', 'global')->name('index');
                    Route::get('/plugins', PluginsPage::class)->defaults('aura_area', 'global')->name('plugins');
                    Route::get('/resources/{slug}/editor', ResourceEditor::class)
                        ->defaults('aura_area', 'global')->middleware(EnsureResourceEditorEnabled::class)->name('resource.editor');
                });

            Route::get('/styleguide', Styleguide::class)->name('styleguide');
            Route::get('/img/{path}', ImageController::class)->where('path', '.*')->name('image');

        });
    });

foreach (Aura::getResources() as $resource) {
    if (class_exists($resource)) {
        Aura::registerRoutes(app($resource)->getSlug(), $resource);
    }
}
