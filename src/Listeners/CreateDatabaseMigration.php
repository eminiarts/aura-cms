<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

class CreateDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function handle(SaveFields $event)
    {
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);
        $model = $event->model;
        $tableName = $model->getTable();

        if (! $model::$customTable) {
            return;
        }

        // Detect fields to add
        $fieldsToAdd = $newFields->filter(function ($field) {
            return ! isset($field['_id']);
        });

        // Detect fields to update
        $fieldsToUpdate = $newFields->filter(function ($field) use ($existingFields) {
            if (! isset($field['_id'])) {
                return false;
            }
            $existingField = $existingFields->firstWhere('_id', $field['_id']);

            return $existingField && $existingField != $field;
        })->map(function ($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);

            return ['old' => $oldField, 'new' => $field];
        })->values();

        // Detect fields to delete
        $fieldsToDelete = $existingFields->filter(function ($field) use ($newFields) {
            return ! $newFields->contains('_id', $field['_id']);
        });

        if ($fieldsToAdd->isEmpty() && $fieldsToUpdate->isEmpty() && $fieldsToDelete->isEmpty()) {
            return;
        }

        // Generate migration name
        $timestamp = date('Y_m_d_His');
        $migrationName = "update_{$tableName}_table_{$timestamp}";

        // Create the migration file
        Artisan::call('make:migration', [
            'name' => $migrationName,
            '--table' => $tableName,
        ]);

        $migrationFile = $this->getMigrationPath($migrationName);

        if ($migrationFile === null) {
            throw new \Exception("Unable to find migration file '{$migrationName}'.");
        }

        // Generate schema for additions, updates, and deletions
        $schemaAdditions = $this->generateSchema($fieldsToAdd, 'add');
        $schemaUpdates = $this->generateSchema($fieldsToUpdate, 'update');
        $schemaDeletions = $this->generateSchema($fieldsToDelete, 'delete');

        // Generate down schema for additions, updates, and deletions
        $schemaAdditionsDown = $this->generateDownSchema($fieldsToAdd, 'add');
        $schemaUpdatesDown = $this->generateDownSchema($fieldsToUpdate, 'update');
        $schemaDeletionsDown = $this->generateDownSchema($fieldsToDelete, 'delete');

        // Update the migration file content
        $content = $this->files->get($migrationFile);
        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions, $schemaAdditionsDown, $schemaUpdatesDown, $schemaDeletionsDown);

        // Update the migration file content
        $content = $this->files->get($migrationFile);

        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions, $schemaAdditionsDown, $schemaUpdatesDown, $schemaDeletionsDown);

        // Write the updated content back to the migration file
        $this->files->put($migrationFile, $updatedContent);

        try {
            // Run Pint to format the migration file
            $this->runPint($migrationFile);

            // Run the migration
            Artisan::call('migrate');
        } catch (\Exception $e) {
            // We don't want to throw an exception here, just log it
            Log::error($e->getMessage());
        }

    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $columnType = $fieldInstance->tableColumnType;

        return "\$table->{$columnType}('{$field['slug']}')->nullable();\n";
    }

    protected function generateDownSchema($fields, $action)
    {
        $downSchema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    // For additions in the up method, we need to drop the columns in the down method
                    $downSchema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldType = app($field['old']['type'])->tableColumnType;
                    $newType = app($field['new']['type'])->tableColumnType;

                    if ($oldType !== $newType) {
                        $downSchema .= "\$table->{$oldType}('{$newSlug}')->nullable()->change();\n";
                    }

                    if ($oldSlug !== $newSlug) {
                        $downSchema .= "\$table->renameColumn('{$newSlug}', '{$oldSlug}');\n";
                    }
                    break;
                case 'delete':
                    // For deletions in the up method, we need to re-add the columns in the down method
                    $downSchema .= $this->generateColumn($field);
                    break;
            }
        }

        return $downSchema;
    }

    protected function generateSchema($fields, $action)
    {
        $schema = '';

        foreach ($fields as $field) {

            switch ($action) {
                case 'add':
                    $schema .= $this->generateColumn($field);
                    break;
                case 'update':
                    $oldSlug = $field['old']['slug'];
                    $newSlug = $field['new']['slug'];
                    $oldType = app($field['old']['type'])->tableColumnType;
                    $newType = app($field['new']['type'])->tableColumnType;

                    if ($oldSlug !== $newSlug) {
                        $schema .= "\$table->renameColumn('{$oldSlug}', '{$newSlug}');\n";
                    }

                    if ($oldType !== $newType) {
                        $schema .= "\$table->{$newType}('{$newSlug}')->nullable()->change();\n";
                    }
                    break;
                case 'delete':
                    // Dont Drop ID, Created At, Updated At
                    if (in_array($field['slug'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        break;
                    }

                    $schema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
            }
        }

        return $schema;
    }

    protected function getMigrationPath($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return $file;
            }
        }

    }

    protected function runPint($migrationFile)
    {
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }

    protected function updateMigrationContent($content, $additions, $updates, $deletions, $additionsDown, $updatesDown, $deletionsDown)
    {
        // Up method
        $pattern = '/(public function up\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $replacement = '${1}'.PHP_EOL.$additions.PHP_EOL.$updates.PHP_EOL.$deletions.PHP_EOL.'${3}';
        $updatedContent = preg_replace($pattern, $replacement, $content);

        // Down method
        $downPattern = '/(public function down\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
        $downReplacement = '${1}'.PHP_EOL.$additionsDown.PHP_EOL.$updatesDown.PHP_EOL.$deletionsDown.PHP_EOL.'${3}';
        $updatedContent = preg_replace($downPattern, $downReplacement, $updatedContent);

        return $updatedContent;
    }
}
