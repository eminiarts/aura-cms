<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Route;

trait AuraResourceUrls
{
    public function createUrl()
    {
        $name = Aura::resourceRouteName($this->getSlug(), 'create');

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

        $name = Aura::resourceRouteName($this->getSlug(), 'edit');

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }

    public function getIndexRoute()
    {
        return route(Aura::resourceRouteName($this->getSlug(), 'index'));
    }

    public function indexUrl()
    {
        $name = Aura::resourceRouteName($this->getSlug(), 'index');

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

        $name = Aura::resourceRouteName($this->getSlug(), 'view');

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }
}
