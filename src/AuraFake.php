<?php

namespace Aura\Base;

use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View;
use Illuminate\Support\Facades\Route;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\Create;

class AuraFake extends Aura
{
    public $model;

    public function findResourceBySlug($slug)
    {
        if ($this->model) {
            return $this->model;
        }

        return $slug;
    }

    public function setModel($model)
    {
        $this->model = $model;

        $slug = $model->getSlug();

        Route::domain(config('aura.domain'))
            ->middleware(config('aura.middleware.aura-admin'))
            ->prefix(config('aura.path')) // This is likely 'admin' from your config
            ->name('aura.')
            ->group(function () use ($slug) {
                Route::get("/{$slug}", Index::class)->name("{$slug}.index");
                Route::get("/{$slug}/create", Create::class)->name("{$slug}.create");
                Route::get("/{$slug}/{id}/edit", Edit::class)->name("{$slug}.edit");
                Route::get("/{$slug}/{id}", View::class)->name("{$slug}.view");
            });

        Aura::clearRoutes();
    }
}
