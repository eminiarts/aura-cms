<?php

namespace Aura\Base;

class AuraFake extends Aura
{
    public $model;

    public function __construct()
    {
        $resources = config('aura.resources', []);

        if (! config('aura.teams')) {
            unset($resources['team'], $resources['team-invitation']);
        }

        $this->registerResources(array_values($resources));
        $this->captureBaselineState();
    }

    public function findResourceBySlug($slug)
    {
        if (
            $this->model
            && (
                $slug === null
                || $slug === $this->model::class
                || $slug === $this->model->getSlug()
                || $slug === $this->model->getType()
            )
        ) {
            return $this->model;
        }

        if ($this->model) {
            $configuredResource = parent::findResourceBySlug($slug);

            if (
                $configuredResource
                && in_array($configuredResource::class, array_values(config('aura.resources', [])), true)
            ) {
                return $configuredResource;
            }

            return $this->model;
        }

        return $slug;
    }

    public function flushState(): void
    {
        parent::flushState();

        $this->model = null;
    }

    public function setModel($model)
    {
        $this->model = $model;

        if ($model->fieldsCollection()->contains(function (mixed $field): bool {
            $type = is_array($field) ? ($field['type'] ?? null) : null;

            return is_string($type)
                && (is_a($type, Fields\Image::class, true) || is_a($type, Fields\File::class, true));
        })) {
            $this->registerResources([$model::class]);
        }

        $slug = $model->getSlug();

        Aura::registerRoutes($slug, $model);

        Aura::clearRoutes();
    }
}
