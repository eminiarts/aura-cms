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
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);
        $model = $event->model;

        // Detect changes, additions, deletions, and reordering
        $fieldsToAdd = $newFields->diffKeys($existingFields);

        // Detect updates
        $fieldsToUpdate = $newFields->filter(function ($field) use ($existingFields) {
            $existingField = $existingFields->firstWhere('_id', $field['_id']);

            return isset($field['_id']) && $existingField && $existingField != $field;
        })->map(function ($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);

            return [
                'old' => $oldField,
                'new' => $field,
            ];
        })->values();

        $fieldsToDelete = $existingFields->diffKeys($newFields);

        // $fieldsReordered = $this->detectReorderedFields($newFields, $existingFields);

        ray('modify', $model, get_class($model));

        Artisan::call('aura:create-resource-migration', [
            'resource' => get_class($model),
        ]);
        // run aura:create-resource-migration {resourceClass}

    }
}
