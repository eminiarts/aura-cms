<?php

namespace Aura\Base\Listeners;

use Aura\Base\Events\SaveFields;
use Aura\Base\Schema\FieldColumn;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class ModifyDatabaseMigration
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Handle the event.
     */
    public function handle(SaveFields $event)
    {
        $model = $event->model;
        $newFields = collect($event->fields);
        $existingFields = collect($event->oldFields);

        if (! $model::$customTable) {
            return;
        }

        $tableName = $model->getTable();

        $migrationName = "create_{$tableName}_table";

        $schema = $this->generateSchema($newFields);

        $migrationFile = null;
        $migrationCreated = false;
        $originalContent = null;

        try {
            if ($this->migrationExists($migrationName)) {
                $migrationFile = $this->getMigrationPath($migrationName);
            } else {
                $this->createMigration($migrationName, $tableName);
                $migrationFile = $this->getMigrationPath($migrationName);
                $migrationCreated = true;
            }

            if ($migrationFile === null) {
                throw new RuntimeException("Unable to find migration file '{$migrationName}'.");
            }

            $content = $this->files->get($migrationFile);
            $originalContent = $content;

            // Up method
            $pattern = '/(public function up\(\): void[\s\S]*?Schema::create\(.*?\{)([\s\S]*?)(\}\);[\s\S]*?\})/';
            $replacement = '${1}'.$schema.'${3}';
            $replacedContent = preg_replace($pattern, $replacement, $content, -1, $upReplacementCount);

            if ($replacedContent === null || $upReplacementCount !== 1) {
                throw new RuntimeException("Unable to update the up method in migration [{$migrationFile}].");
            }

            // Down method
            $down = "Schema::dropIfExists('{$tableName}');";
            $pattern = '/(public function down\(\): void[\s\S]*?{)[\s\S]*?Schema::table\(.*?function \(Blueprint \$table\) \{[\s\S]*?\/\/[\s\S]*?\}\);[\s\S]*?\}/';
            $replacement = '${1}'.PHP_EOL.'    '.$down.PHP_EOL.'}';
            $updatedContent = preg_replace($pattern, $replacement, $replacedContent);

            if ($updatedContent === null || $this->files->put($migrationFile, $updatedContent) === false) {
                throw new RuntimeException("Unable to update migration [{$migrationFile}].");
            }

            $this->runPint($migrationFile);
            $this->runSchemaUpdate($migrationFile);
        } catch (Throwable $exception) {
            if ($migrationCreated && $migrationFile !== null) {
                $this->files->delete($migrationFile);
            } elseif ($migrationFile !== null && is_string($originalContent) && $this->files->put($migrationFile, $originalContent) === false) {
                throw new RuntimeException("Unable to restore migration [{$migrationFile}] after schema synchronization failed.", previous: $exception);
            }

            throw $exception;
        }
    }

    protected function createMigration(string $migrationName, string $tableName): void
    {
        $exitCode = Artisan::call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Unable to generate migration.');
        }
    }

    protected function generateColumn($field)
    {
        $fieldInstance = app($field['type']);
        $definition = method_exists($fieldInstance, 'columnDefinition')
            ? $fieldInstance->columnDefinition($field)
            : new FieldColumn(
                type: $fieldInstance->tableColumnType,
                nullable: $fieldInstance->tableNullable ?? true,
            );

        return $definition->toMigration($field['slug']).";\n";
    }

    protected function generateSchema($fields)
    {
        $schema = '';

        $schema .= '$table->id();'."\n";

        foreach ($fields as $field) {
            $schema .= $this->generateColumn($field);
        }

        $schema .= '$table->foreignId("user_id");'."\n";
        $schema .= '$table->foreignId("team_id");'."\n";
        $schema .= '$table->timestamps();'."\n";
        $schema .= '$table->softDeletes();'."\n";

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

    protected function migrationExists($name)
    {
        $migrationFiles = $this->files->glob(database_path('migrations/*.php'));
        $name = Str::snake($name);

        foreach ($migrationFiles as $file) {
            if (strpos($file, $name) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function runPint($migrationFile): void
    {
        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        Process::path(base_path())->run($command)->throw();
    }

    protected function runSchemaUpdate(string $migrationFile): void
    {
        $exitCode = Artisan::call('aura:schema-update', [
            'migration' => $migrationFile,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Schema synchronization failed.');
        }
    }
}
