<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Support\Facades\Route;

trait AuraResourceUrls
{
    public function createUrl()
    {
        $name = 'aura.'.$this->getSlug().'.create';

        if (! Route::has($name)) {
            return;
        }

        return route($name);
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

    /**
     * Named destination used by global search after policy authorization.
     *
     * @return array{route: string, parameters: array<string, mixed>}
     */
    public function globalSearchDestination()
    {
        return [
            'route' => 'aura.'.$this->getSlug().'.view',
            'parameters' => ['id' => $this->getKey()],
        ];
    }

    /**
     * Legacy extension point. Global search validates overrides as a same-origin
     * GET route; new resources should override globalSearchDestination().
     */
    public function globalSearchUrl()
    {
        return $this->viewUrl();
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
}
