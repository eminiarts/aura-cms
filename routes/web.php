<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Http\Controllers\Api\FieldsController;
use Aura\Base\Http\Controllers\ImageController;
use Aura\Base\Http\Middleware\EnsureResourceEditorEnabled;
use Aura\Base\Livewire\Attachment\Index as AttachmentIndex;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;
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

            Route::get('/', config('aura.components.dashboard'))->name('dashboard');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/profile', config('aura.components.profile'))->name('profile');

            Route::get('/settings', config('aura.components.settings'))->name('settings');

            Route::get('/plugins', PluginsPage::class)->name('plugins');

            Route::get('/styleguide', Styleguide::class)->name('styleguide');

            Route::get('/img/{path}', ImageController::class)->where('path', '.*')->name('image');

            Route::get('/resources/{slug}/editor', ResourceEditor::class)
                ->middleware(EnsureResourceEditorEnabled::class)
                ->name('resource.editor');

            foreach (Aura::getResources() as $resource) {

                if (! class_exists($resource)) {
                    continue;
                }

                $slug = app($resource)->getSlug();

                // Attachment uses a dedicated media index and deliberately has no
                // generic create/edit/view routes (see AttachmentIndex).
                if ($slug === 'attachment') {
                    continue;
                }

                Route::get("/{$slug}", Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", View::class)->name("{$slug}.view");
            }

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

        });
    });
