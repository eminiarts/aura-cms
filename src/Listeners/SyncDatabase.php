<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        // Option: Migration_Run_1:
        // Option: Migration_Run_2:
        // Option: Migration_Run_3:

        // migration: rename_title_to_title2
        // migration: rename_title2_to_title3
        // migration: rename_title3_to_title

        // What if:

        // migration: delete_title
        // migration: add_title

        // The data of Employee 2: title = "Manager" -> deleted

        // Idea: In the deletion migration: Check if the column is still there, if so, skip the deletion
        // Add_Title: Check if the column is already there, if so, skip the addition

        // What if 2:

        // migration: rename_title_to_title2
        // migration: rename_title2_to_title3
        // migration: create_title

        // Option 1: Keep only 1 Migration file and try to sync db -> cons: renaming would not be possible

        // Option 2: Keep all migration files and try to sync db -> cons: multiple migration files, more complex

        return;

        // Change Migration File
        // php artisan aura:db-sync

        // Apply schema changes
        Schema::table($model->getTable(), function (Blueprint $table) use ($fieldsToAdd, $fieldsToUpdate, $fieldsToDelete) {
            // Add new fields
            foreach ($fieldsToAdd as $field) {
                $this->addField($table, $field);
            }

            // Update existing fields
            foreach ($fieldsToUpdate as $field) {
                $this->updateField($table, $field);
            }

            // Remove deleted fields
            foreach ($fieldsToDelete as $field) {
                $table->dropColumn($field['slug']);
            }
        });

        // Handle reordering separately if necessary
        $this->handleReordering($fieldsReordered);

        // Return the new array (new schema) if necessary
        return $newFields->toArray();
    }

    /**
     * Add a new field to the table.
     */
    private function addField(Blueprint $table, array $field)
    {
        // Define field addition logic based on field type
        switch ($field['type']) {
            case 'Aura\Base\Fields\Text':
                $table->string($field['slug'])->nullable();
                break;
                // Add cases for other field types as needed
            default:
                throw new \Exception('Unknown field type: '.$field['type']);
        }
    }

    /**
     * Detect reordered fields.
     */
    private function detectReorderedFields($newFields, $existingFields)
    {
        $reorderedFields = [];

        $newFields->each(function ($field, $index) use ($existingFields, &$reorderedFields) {
            if (isset($field['_id'])) {
                $existingField = $existingFields->firstWhere('_id', $field['_id']);
                if ($existingField && $existingFields->search($existingField) !== $index) {
                    $reorderedFields[] = $field;
                }
            }
        });

        return $reorderedFields;
    }

    /**
     * Handle reordering of fields.
     */
    private function handleReordering($fieldsReordered)
    {
        // Implement logic for handling reordering if necessary
        // This could involve updating metadata or simply noting the changes
        // For this example, we'll just output the reordered fields
        // if (!empty($fieldsReordered)) {
        //     // You can handle reordering in the way your application requires
        //     // This could be updating an order column, metadata, or another mechanism
        // }
    }

    /**
     * Update an existing field in the table.
     */
    private function updateField(Blueprint $table, array $field)
    {
        // Define field update logic based on field type
        switch ($field['type']) {
            case 'Aura\Base\Fields\Text':
                $table->string($field['slug'])->nullable()->change();
                break;
                // Add cases for other field types as needed
            default:
                throw new \Exception('Unknown field type: '.$field['type']);
        }
    }
}
