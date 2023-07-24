<?php

use Eminiarts\Aura\Http\Controllers\Api\FieldsController;
use Eminiarts\Aura\Livewire\Attachment\Index as AttachmentIndex;
use Eminiarts\Aura\Livewire\AuraConfig;
use Eminiarts\Aura\Livewire\Post\Create;
use Eminiarts\Aura\Livewire\Post\Edit;
use Eminiarts\Aura\Livewire\Post\Index;
use Eminiarts\Aura\Livewire\Post\View;
use Eminiarts\Aura\Livewire\Posttype;
use Eminiarts\Aura\Livewire\Taxonomy\Create as TaxonomyCreate;
use Eminiarts\Aura\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Livewire\Taxonomy\Index as TaxonomyIndex;
use Eminiarts\Aura\Livewire\Taxonomy\View as TaxonomyView;
use Eminiarts\Aura\Livewire\TeamSettings;
use Eminiarts\Aura\Livewire\User\Profile;
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
            Route::get('/', function () {
                return view('aura::dashboard');
            })->name('dashboard');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/posttypes', function () {
                return view('aura::posttypes');
            })->name('posttypes');

            Route::get('/profile', Profile::class)->name('profile');

            Route::get('/team-settings', TeamSettings::class)->name('team.settings');
            Route::get('/aura-config', AuraConfig::class)->name('config');

            Route::get('/posttypes/{slug}', Posttype::class)->name('posttype.edit');
            Route::get('/taxonomies/{slug}', TaxonomyIndex::class)->name('taxonomy.index');
            Route::get('/taxonomies/{slug}/create', TaxonomyCreate::class)->name('taxonomy.create');
            Route::get('/taxonomies/{slug}/{id}/edit', TaxonomyEdit::class)->name('taxonomy.edit');
            Route::get('/taxonomies/{slug}/{id}', TaxonomyView::class)->name('taxonomy.view');

            Route::get('/attachment', AttachmentIndex::class)->name('attachment.index');

            Route::get('/{slug}', Index::class)->name('post.index');
            Route::get('/{slug}/create', Create::class)->name('post.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('post.edit');
            Route::get('/{slug}/{id}', View::class)->name('post.view');
        });
    });
