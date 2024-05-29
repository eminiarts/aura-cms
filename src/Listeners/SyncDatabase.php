<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncDatabase
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
    public function handle(SaveFields $event): void
    {
         // Dump and die the fields property
        dd($event->fields, $event->model->getFields());
    }
}
