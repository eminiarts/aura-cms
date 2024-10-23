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

        ray('setModel', $slug);
        
        Route::get("/{$slug}", Index::class)->name("aura.{$slug}.index");
        Route::get("/{$slug}/create", Create::class)->name("aura.{$slug}.create");
        Route::get("/{$slug}/{id}/edit", Edit::class)->name("aura.{$slug}.edit");
        Route::get("/{$slug}/{id}", View::class)->name("aura.{$slug}.view");

        ray(Route::has("aura.{$slug}.create"));
    }
}
