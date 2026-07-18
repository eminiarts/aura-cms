<?php

namespace Aura\Base\Traits\Concerns;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Resource\View;

/**
 * Livewire page components backing this resource's admin routes. Route
 * registration (routes/web.php and Aura::registerRoutes()) resolves the
 * component per page type through these hooks, so overriding one swaps in a
 * custom component while keeping the default URI and `aura.{slug}.*` route
 * name — every generated link keeps working. `php artisan aura:customize`
 * writes these overrides for you.
 */
trait AuraResourceComponents
{
    public static function createComponent(): string
    {
        return Create::class;
    }

    public static function editComponent(): string
    {
        return Edit::class;
    }

    public static function indexComponent(): string
    {
        return Index::class;
    }

    public static function viewComponent(): string
    {
        return View::class;
    }
}
