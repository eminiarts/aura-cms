<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\RouteTarget;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;

trait AuraResourceUrls
{
    /**
     * Named route used by the create page's return link.
     */
    public function createReturnRoute(): RouteTarget|string
    {
        return 'aura.'.$this->getSlug().'.index';
    }

    public function createReturnUrl(): ?string
    {
        return $this->resolveRouteTarget($this->createReturnRoute());
    }

    public function createUrl()
    {
        $name = 'aura.'.$this->getSlug().'.create';

        if (! Route::has($name)) {
            return;
        }

        return route($name);
    }

    /**
     * Named route used by the edit page's return link.
     */
    public function editReturnRoute(): RouteTarget|string
    {
        return 'aura.'.$this->getSlug().'.index';
    }

    public function editReturnUrl(): ?string
    {
        return $this->resolveRouteTarget($this->editReturnRoute());
    }

    public function editUrl()
    {
        if (! $this->getType() || ! $this->id) {
            return;
        }

        $name = 'aura.'.$this->getSlug().'.edit';

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }

    public function getIndexRoute()
    {
        return route('aura.'.$this->getSlug().'.index');
    }

    public function indexUrl()
    {
        $name = 'aura.'.$this->getSlug().'.index';

        if (! Route::has($name)) {
            return;
        }

        return route($name);
    }

    public function viewUrl()
    {
        if (! $this->getType() || ! $this->id) {
            return;
        }

        $name = 'aura.'.$this->getSlug().'.view';

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }

    protected function resolveRouteTarget(RouteTarget|string $target): ?string
    {
        $name = $target instanceof RouteTarget ? $target->name : $target;
        $parameters = $target instanceof RouteTarget ? $target->parameters : [];

        if ($name === '' || ! Route::has($name)) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (UrlGenerationException) {
            return null;
        }
    }
}
