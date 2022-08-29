<?php

use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Posttype;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;
use Illuminate\Support\Facades\Route;

Route::domain(config('aura.domain'))
    ->middleware(config('aura.middleware.admin'))
    ->name('aura.')
    ->group(function () {
        // Route::prefix(config('aura.core_path'))->group(function () {
        //     Route::get('/assets/{file}', AssetController::class)->where('file', '.*')->name('asset');
        // });

        Route::prefix(config('aura.path'))->group(function () {
            Route::get('/', function () {
                return 'dashboard coming soon';
            });

            Route::get('/posttypes/{slug}', Posttype::class)->name('posttype.edit');
            Route::get('/taxonomies/{slug}', TaxonomyIndex::class)->name('taxonomy.index');
            Route::get('/taxonomies/{slug}/{id}/edit', TaxonomyEdit::class)->name('taxonomy.edit');
            // Route::get('/taxonomies/{slug}/{id}', TaxonomyEdit::class)->name('taxonomy.edit');
            Route::get('/{slug}', Index::class)->name('post.index');
            Route::get('/{slug}/create', Create::class)->name('post.create');
            Route::get('/{slug}/{id}/edit', Edit::class)->name('post.edit');

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
