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

        Aura::registerRoutes($slug);
        
        Aura::clearRoutes();
    }
}
