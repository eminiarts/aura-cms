<?php

use Illuminate\Support\Facades\Route;
use Eminiarts\Aura\Livewire\User\Profile;
use Eminiarts\Aura\Livewire\ResourceEditor;
use Eminiarts\Aura\Livewire\Resource\Edit;
use Eminiarts\Aura\Livewire\Resource\View;
use Eminiarts\Aura\Livewire\Config;
use Eminiarts\Aura\Livewire\Resource\Index;
use Eminiarts\Aura\Livewire\Resource\Create;
use Eminiarts\Aura\Livewire\TeamSettings;
use Eminiarts\Aura\Http\Controllers\Api\FieldsController;
use Eminiarts\Aura\Livewire\Attachment\Index as AttachmentIndex;

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

            Route::get('/resources/{slug}/editor', ResourceEditor::class)->name('resource.editor');

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

            Route::get('/{slug}', Index::class)->name('resource.index');
            Route::get('/{slug}/create', Create::class)->name('resource.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('resource.edit');
            Route::get('/{slug}/{id}', View::class)->name('resource.view');
        });
    });
