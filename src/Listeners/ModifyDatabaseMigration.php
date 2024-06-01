<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\Artisan;

class ModifyDatabaseMigration
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SaveFields $event)
    {
        $model = $event->model;

        Artisan::call('aura:create-resource-migration', [
            'resource' => get_class($model),
        ]);
    }

}
