<?php

use Aura\Base\Http\Controllers\Api\FieldsController;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\Config;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Livewire\ResourceEditor;
use Illuminate\Support\Facades\Route;

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

            Route::get('/settings', config('aura.components.team_settings'))->name('team.settings');
            Route::get('/config', config('aura.components.config'))->name('config');

            Route::get('/plugins', PluginsPage::class)->name('plugins');

            Route::get('/resources/{slug}/editor', ResourceEditor::class)->name('resource.editor');

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

            Route::get('/{slug}', Index::class)->name('resource.index');
            Route::get('/{slug}/create', Create::class)->name('resource.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('resource.edit');
            Route::get('/{slug}/{id}', View::class)->name('resource.view');
        });
    });
