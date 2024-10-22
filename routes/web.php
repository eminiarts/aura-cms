<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View;
use Illuminate\Support\Facades\Route;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Http\Controllers\Api\FieldsController;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;

Route::middleware(config('aura.middleware.aura-guest'))->group(function () {
    require __DIR__.'/auth.php';
});

Route::middleware(config('aura.middleware.aura-admin'))->group(function () {
    Route::impersonate();
});

Route::domain(config('aura.domain'))
    ->middleware(config('aura.middleware.aura-admin'))
    ->name('aura.')
    ->group(function () {
        // Route::prefix(config('aura.core_path'))->group(function () {
        //     Route::get('/assets/{file}', AssetController::class)->where('file', '.*')->name('asset');
        // });

        Route::prefix(config('aura.path'))->group(function () {

            Route::get('/', config('aura.components.dashboard'))->name('dashboard');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/profile', config('aura.components.profile'))->name('profile');

            Route::get('/settings', config('aura.components.settings'))->name('settings');

            Route::get('/plugins', PluginsPage::class)->name('plugins');

            Route::get('/resources/{slug}/editor', ResourceEditor::class)->name('resource.editor');

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

            foreach (Aura::getResources() as $resource) {
                $slug = app($resource)->getSlug();
                Route::get("/{$slug}", Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", View::class)->name("{$slug}.view");
            }

        });
    });
