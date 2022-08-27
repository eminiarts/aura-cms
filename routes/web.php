<?php


use Eminiarts\Aura\Http\Livewire\Media;
use Eminiarts\Aura\Http\Livewire\Posttype;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Illuminate\Support\Facades\Route;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;

Route::get('auro', function () {
    return 'testtt';
})->name('home');


Route::middleware(['auth'])->prefix('aura')->group(function () {
    Route::get('/posttypes/{slug}', Posttype::class)->name('posttype.edit');
    Route::get('/taxonomies/{slug}', TaxonomyIndex::class)->name('taxonomy.index');
    Route::get('/taxonomies/{slug}/{id}/edit', TaxonomyEdit::class)->name('taxonomy.edit');
    // Route::get('/taxonomies/{slug}/{id}', TaxonomyEdit::class)->name('taxonomy.edit');
    Route::get('/{slug}', Index::class)->name('post.index');
    Route::get('/{slug}/create', Create::class)->name('post.create');
    Route::get('/{slug}/{id}/edit', Edit::class)->name('post.edit');
});
