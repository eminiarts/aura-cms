<?php

use Illuminate\Support\Facades\Route;
use Eminiarts\Aura\Http\Livewire\Media;
use Eminiarts\Aura\Http\Livewire\Posttype;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\View;
use Eminiarts\Aura\Http\Livewire\AuraConfig;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\TeamSettings;
use Eminiarts\Aura\Http\Controllers\ProfileController;
use Eminiarts\Aura\Http\Controllers\Api\FieldsController;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Create as TaxonomyCreate;
use Eminiarts\Aura\Http\Livewire\Attachment\Index as AttachmentIndex;

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

        Route::get('/test', function () {
            dd(config('aura'));

            return config('aura.path');
        });

        Route::prefix(config('aura.path'))->group(function () {
            Route::get('/', function () {
                return view('aura::dashboard');
            })->name('dashboard');

            // route for api/fields/values which calls Api\FieldsController@values
            Route::post('/api/fields/values', [FieldsController::class, 'values'])->name('api.fields.values');

            Route::get('/posttypes', function () {
                return view('aura::posttypes');
            })->name('posttypes');

            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


            Route::get('/team-settings', TeamSettings::class)->name('team.settings');
            Route::get('/aura-config', AuraConfig::class)->name('config');

            Route::get('/media', Media::class)->name('media.index');

            Route::get('/flows', CreateFlow::class)->name('flows.create');
            Route::get('/flows/{id}', CreateFlow::class)->name('flows.edit');

            Route::get('/posttypes/{slug}', Posttype::class)->name('posttype.edit');
            Route::get('/taxonomies/{slug}', TaxonomyIndex::class)->name('taxonomy.index');
            Route::get('/taxonomies/{slug}/create', TaxonomyCreate::class)->name('taxonomy.create');
            Route::get('/taxonomies/{slug}/{id}/edit', TaxonomyEdit::class)->name('taxonomy.edit');
            // Route::get('/taxonomies/{slug}/{id}', TaxonomyEdit::class)->name('taxonomy.edit');

            Route::get('/Attachment', AttachmentIndex::class)->name('attachment.index');

            ray(' ------ aura routes');

            Route::get('/{slug}', Index::class)->name('post.index');
            Route::get('/{slug}/create', Create::class)->name('post.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('post.edit');
            Route::get('/{slug}/{id}', View::class)->name('post.view');

            // if ($loginPage = config('aura.auth.pages.login')) {
            //     Route::get('/login', $loginPage)->name('auth.login');
            // }

            // Route::middleware(config('aura.middleware.auth'))->group(function (): void {
            //     Route::name('pages.')->group(function (): void {
            //         foreach (Filament::getPages() as $page) {
            //             Route::group([], $page::getRoutes());
            //         }
            //     });

            //     Route::name('resources.')->group(function (): void {
            //         foreach (Filament::getResources() as $resource) {
            //             Route::group([], $resource::getRoutes());
            //         }
            //     });
            // });
        });
    });
