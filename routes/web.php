<?php

use Illuminate\Support\Facades\Route;
use Eminiarts\Aura\Livewire\User\Profile;
use Eminiarts\Aura\Livewire\Posttype;
use Eminiarts\Aura\Livewire\Post\Edit;
use Eminiarts\Aura\Livewire\Post\View;
use Eminiarts\Aura\Livewire\AuraConfig;
use Eminiarts\Aura\Livewire\Post\Index;
use Eminiarts\Aura\Livewire\Post\Create;
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

            Route::get('/', config('aura.dashboard_component'))->name('dashboard');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/posttypes', function () {
                return view('aura::posttypes');
            })->name('posttypes');

            Route::get('/profile', Profile::class)->name('profile');

            Route::get('/team-settings', TeamSettings::class)->name('team.settings');
            Route::get('/aura-config', AuraConfig::class)->name('config');

            Route::get('/posttypes/{slug}', Posttype::class)->name('posttype.edit');

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

            Route::get('/{slug}', Index::class)->name('post.index');
            Route::get('/{slug}/create', Create::class)->name('post.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('post.edit');
            Route::get('/{slug}/{id}', View::class)->name('post.view');
        });
    });
