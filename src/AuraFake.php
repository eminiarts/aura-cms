<?php

namespace Aura\Base;

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
