<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class CreateDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function handle(SaveFields $event)
    {
        ray('handle create database');

        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);
        $model = $event->model;
        $tableName = $model->getTable();
        
        // Detect changes, additions, deletions
        $fieldsToAdd = $newFields->diffKeys($existingFields);
        $fieldsToUpdate = $newFields->filter(function($field) use ($existingFields) {
            if (!isset($field['_id'])) {
                return false;
            }
            $existingField = $existingFields->firstWhere('_id', $field['_id']);
            return $existingField && $existingField != $field;
        })->map(function($field) use ($existingFields) {
            $oldField = $existingFields->firstWhere('_id', $field['_id']);
            return ['old' => $oldField, 'new' => $field];
        })->values();
        $fieldsToDelete = $existingFields->diffKeys($newFields);

                ray('sync',$fieldsToAdd, $fieldsToUpdate, $fieldsToDelete, $model, $model->getTable());


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
        $schemaAdditionsDown = $this->generateDownSchema($schemaAdditions, 'add');
        $schemaUpdatesDown = $this->generateDownSchema($schemaUpdates, 'update');
        $schemaDeletionsDown = $this->generateDownSchema($schemaDeletions, 'delete');

        // Update the migration file content
        $content = $this->files->get($migrationFile);
        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions, $schemaAdditionsDown, $schemaUpdatesDown, $schemaDeletionsDown);


        ray($schemaAdditions, $schemaUpdates, $schemaDeletions);

        // Update the migration file content
        $content = $this->files->get($migrationFile);
        $updatedContent = $this->updateMigrationContent($content, $schemaAdditions, $schemaUpdates, $schemaDeletions);

        ray('updatedContent', $updatedContent)->blue();

        // Write the updated content back to the migration file
        $this->files->put($migrationFile, $updatedContent);
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

        return null;
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
                    $schema .= "\$table->renameColumn('{$oldSlug}', '{$newSlug}');\n";
                    break;
                case 'delete':
                    $schema .= "\$table->dropColumn('{$field['slug']}');\n";
                    break;
            }
        }

        return $schema;
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
                // For updates in the up method, we need to rename the columns back to their original names in the down method
                $downSchema .= "\$table->renameColumn('{$newSlug}', '{$oldSlug}');\n";
                break;
            case 'delete':
                // For deletions in the up method, we need to re-add the columns in the down method
                $downSchema .= $this->generateColumn($field['old']);
                break;
        }
    }

    return $downSchema;
}

    

    protected function generateColumn($field)
    {
        $type = $field['type'];
        $slug = $field['slug'];

        return match ($type) {
            'Aura\\Base\\Fields\\ID' => "\$table->id();\n",
            'Aura\\Base\\Fields\\ForeignId' => "\$table->foreignId('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Timestamps' => "\$table->timestamps();\n",
            'Aura\\Base\\Fields\\Text' => "\$table->string('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Slug' => "\$table->string('{$slug}')->unique();\n",
            'Aura\\Base\\Fields\\Image' => "\$table->text('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Password' => "\$table->string('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Number' => "\$table->integer('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Date' => "\$table->date('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Textarea' => "\$table->text('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Color' => "\$table->string('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\BelongsTo' => "\$table->foreignId('{$slug}')->nullable();\n",
            'Aura\\Base\\Fields\\Boolean' => "\$table->boolean('{$slug}')->nullable();\n",
            'Aura\Base\Fields\HasMany' => '', // No need to add anything to the schema for HasMany relationships
            default => "\$table->text('{$slug}')->nullable(); // Custom field type for '{$slug}' with type '{$type}'\n",
        };
    }

    protected function updateMigrationContent($content, $additions, $updates, $deletions, $additionsDown, $updatesDown, $deletionsDown)
{
    // Up method
    $pattern = '/(public function up\(\): void[\s\S]*?Schema::table\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
    $replacement = '${1}' . PHP_EOL . $additions . PHP_EOL . $updates . PHP_EOL . $deletions . PHP_EOL . '${3}';
    $updatedContent = preg_replace($pattern, $replacement, $content);

    // Down method
    $downPattern = '/(public function down\(\): void[\s\S]*?{)([\s\S]*?)(\})/';
    $downReplacement = '${1}' . PHP_EOL . $additionsDown . PHP_EOL . $updatesDown . PHP_EOL . $deletionsDown . PHP_EOL . '${3}';
    $updatedContent = preg_replace($downPattern, $downReplacement, $updatedContent);

    return $updatedContent;
}
}
