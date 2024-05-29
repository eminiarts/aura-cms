<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
        $newFields = collect($event->fields);
        $existingFields = collect($event->model->getFields());

        // Detect additions and changes
        $fieldsToAdd = $newFields->diffKeys($existingFields);

        $fieldsToUpdate = $newFields->intersectByKeys($existingFields)->map(function ($item, $key) use ($existingFields) {
            return $item !== $existingFields[$key] ? $item : null;
        })->filter();
        
        // Detect deletions
        $fieldsToDelete = $existingFields->diffKeys($newFields);

        dd($fieldsToAdd, $fieldsToUpdate, $fieldsToDelete);

        // Apply schema changes
        Schema::table('your_table_name', function (Blueprint $table) use ($fieldsToAdd, $fieldsToUpdate, $fieldsToDelete) {
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
                throw new \Exception("Unknown field type: " . $field['type']);
        }
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
                throw new \Exception("Unknown field type: " . $field['type']);
        }
    }
}