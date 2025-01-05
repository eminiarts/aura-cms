<?php

namespace Aura\Base;

class AuraFake extends Aura
{
    public $model;

    public function findResourceBySlug($slug)
    {
        ray('findResourceBySlug', $this->model)->green(); 

        if ($this->model) {
            ray('should return model');
            return $this->model;
        }

        return $slug;
    }

    public function setModel($model)
    {
        ray('set model');
        $this->model = $model;

        $slug = $model->getSlug();

        Aura::registerRoutes($slug);

        Aura::clearRoutes();
    }
}
